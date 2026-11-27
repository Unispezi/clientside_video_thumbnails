<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Vinzenz Hersche, silversong88
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\ClientsideVideoThumbnails\Service;

use OCA\ClientsideVideoThumbnails\AppInfo\Application;
use OCP\Files\Folder;
use OCP\Files\IAppData;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\Files\SimpleFS\ISimpleFolder;
use OCP\Server;
use Psr\Log\LoggerInterface;

/**
 * Stores one JPEG per file ID in app data, sharded as thumbs/{id % 100}/{(id / 100) % 100}/{id}.jpg
 * so that no single folder grows too large.
 */
class ThumbnailStore {
	private const ROOT = 'thumbs';

	public function __construct(
		private IAppData $appData,
		private IRootFolder $rootFolder,
		private LoggerInterface $logger,
	) {
	}

	public function exists(int $fileId): bool {
		return $this->get($fileId) !== null;
	}

	public function get(int $fileId): ?ISimpleFile {
		try {
			return $this->getShardFolder($fileId, false)->getFile($this->fileName($fileId));
		} catch (NotFoundException) {
			return null;
		}
	}

	/**
	 * @param resource $data
	 */
	public function put(int $fileId, $data): void {
		$folder = $this->getShardFolder($fileId, true);
		$name = $this->fileName($fileId);
		if ($folder->fileExists($name)) {
			$folder->getFile($name)->putContent($data);
		} else {
			$folder->newFile($name, $data);
		}
		$this->forgetServerPreviews($fileId);
	}

	public function delete(int $fileId): void {
		$this->get($fileId)?->delete();
	}

	/**
	 * Removes every stored thumbnail and the previews Nextcloud made from them.
	 */
	public function deleteAll(): void {
		foreach (iterator_to_array($this->allFileIds(), false) as $fileId) {
			$this->forgetServerPreviews($fileId);
		}
		try {
			$this->appData->getFolder(self::ROOT)->delete();
		} catch (NotFoundException) {
		}
	}

	/**
	 * Yields the file ID of every stored thumbnail.
	 *
	 * ISimpleFolder listings only contain files, so the shard tree is walked as regular nodes.
	 *
	 * @return \Generator<int>
	 */
	public function allFileIds(): \Generator {
		try {
			$root = $this->rootFolder->get($this->rootFolder->getAppDataDirectoryName() . '/' . Application::APP_ID . '/' . self::ROOT);
		} catch (NotFoundException) {
			return;
		}
		if (!$root instanceof Folder) {
			return;
		}
		foreach ($root->getDirectoryListing() as $shard1) {
			if (!$shard1 instanceof Folder) {
				continue;
			}
			foreach ($shard1->getDirectoryListing() as $shard2) {
				if (!$shard2 instanceof Folder) {
					continue;
				}
				foreach ($shard2->getDirectoryListing() as $file) {
					if (preg_match('/^(\d+)\.jpg$/', $file->getName(), $m)) {
						yield (int)$m[1];
					}
				}
			}
		}
	}

	/**
	 * Nextcloud keeps its own copies of the previews it made from a thumbnail and would go on serving
	 * them. Its preview watcher drops them, as it does when a file changes. Internal API, the same in
	 * Nextcloud 31 to 34; should it change, a replaced thumbnail just shows up late.
	 */
	private function forgetServerPreviews(int $fileId): void {
		try {
			$node = $this->rootFolder->getFirstNodeById($fileId);
			if ($node !== null) {
				Server::get(\OC\Preview\Watcher::class)->postWrite($node);
			}
		} catch (\Throwable $e) {
			$this->logger->warning('Could not drop cached previews of file ' . $fileId, ['exception' => $e]);
		}
	}

	private function fileName(int $fileId): string {
		return $fileId . '.jpg';
	}

	/**
	 * @throws NotFoundException when $create is false and a folder is missing
	 */
	private function getShardFolder(int $fileId, bool $create): ISimpleFolder {
		$folder = $this->getOrCreate(null, self::ROOT, $create);
		$folder = $this->getOrCreate($folder, sprintf('%02d', $fileId % 100), $create);
		return $this->getOrCreate($folder, sprintf('%02d', intdiv($fileId, 100) % 100), $create);
	}

	private function getOrCreate(?ISimpleFolder $parent, string $name, bool $create): ISimpleFolder {
		try {
			return $parent === null ? $this->appData->getFolder($name) : $parent->getFolder($name);
		} catch (NotFoundException $e) {
			if (!$create) {
				throw $e;
			}
			return $parent === null ? $this->appData->newFolder($name) : $parent->newFolder($name);
		}
	}
}
