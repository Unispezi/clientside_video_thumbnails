<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Vinzenz Hersche, silversong88
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\ClientsideVideoThumbnails\Controller;

use OCA\ClientsideVideoThumbnails\Service\ThumbnailStore;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

class ThumbnailController extends Controller {
	private const MAX_UPLOAD_BYTES = 16 * 1024 * 1024;

	public function __construct(
		string $appName,
		IRequest $request,
		private IRootFolder $rootFolder,
		private IUserSession $userSession,
		private ThumbnailStore $store,
		private LoggerInterface $logger,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Stores the thumbnail for one of the signed-in user's videos. Browsers send their request
	 * token; Nextcloud's usual CSRF check applies.
	 */
	#[NoAdminRequired]
	public function upload(string $path): JSONResponse {
		$file = $this->resolveVideo($path);
		if ($file === null) {
			return $this->error('Not a video file you can access: ' . $path, Http::STATUS_NOT_FOUND);
		}

		$upload = $this->request->getUploadedFile('thumbnail');
		if (!is_array($upload) || ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($upload['tmp_name'] ?? '')) {
			return $this->error('No thumbnail uploaded', Http::STATUS_BAD_REQUEST);
		}
		if (($upload['size'] ?? 0) > self::MAX_UPLOAD_BYTES) {
			return $this->error('Thumbnail larger than 16 MB', Http::STATUS_REQUEST_ENTITY_TOO_LARGE);
		}
		$info = @getimagesize($upload['tmp_name']);
		if ($info === false || $info[2] !== IMAGETYPE_JPEG) {
			return $this->error('Thumbnail must be a JPEG image', Http::STATUS_UNSUPPORTED_MEDIA_TYPE);
		}

		$handle = fopen($upload['tmp_name'], 'rb');
		if ($handle === false) {
			return $this->error('Could not read uploaded thumbnail', Http::STATUS_INTERNAL_SERVER_ERROR);
		}
		try {
			$this->store->put($file->getId(), $handle);
		} catch (\Throwable $e) {
			$this->logger->error('Storing thumbnail failed', ['exception' => $e]);
			return $this->error('Storing thumbnail failed', Http::STATUS_INTERNAL_SERVER_ERROR);
		} finally {
			// newFile() closes the stream itself, so only close it if it is still open
			/** @psalm-suppress RedundantCondition */
			if (is_resource($handle)) {
				fclose($handle);
			}
		}

		return new JSONResponse(['status' => 'success', 'fileId' => $file->getId()]);
	}

	/**
	 * Deletes the thumbnails of the signed-in user's own videos, not of videos others shared with them.
	 */
	#[NoAdminRequired]
	public function deleteMine(): JSONResponse {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return $this->error('Not signed in', Http::STATUS_UNAUTHORIZED);
		}
		$deleted = 0;
		foreach ($this->rootFolder->getUserFolder($user->getUID())->searchByMime('video') as $node) {
			if ($node->getOwner()?->getUID() === $user->getUID() && $this->store->exists($node->getId())) {
				$this->store->delete($node->getId());
				$deleted++;
			}
		}
		return new JSONResponse(['status' => 'success', 'deleted' => $deleted]);
	}

	/**
	 * Admin only (no NoAdminRequired): deletes all thumbnails, for testing.
	 */
	public function deleteAll(): JSONResponse {
		$this->store->deleteAll();
		return new JSONResponse(['status' => 'success']);
	}

	private function error(string $message, int $status): JSONResponse {
		return new JSONResponse(['status' => 'error', 'message' => $message], $status);
	}

	/**
	 * Resolves a path relative to the current user's home to a video file, or null.
	 */
	private function resolveVideo(string $path): ?File {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return null;
		}
		try {
			$node = $this->rootFolder->getUserFolder($user->getUID())->get(ltrim($path, '/'));
		} catch (NotFoundException) {
			return null;
		}
		if (!$node instanceof File || !str_starts_with($node->getMimetype(), 'video/')) {
			return null;
		}
		return $node;
	}
}
