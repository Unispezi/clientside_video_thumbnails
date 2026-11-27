<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Unispezi
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\ClientsideVideoThumbnails\Listener;

use OCA\ClientsideVideoThumbnails\Service\ThumbnailStore;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\Events\Node\NodeCopiedEvent;
use OCP\Files\File;
use OCP\Files\Folder;
use Psr\Log\LoggerInterface;

/**
 * Thumbnails are stored per file ID, and a copy gets a new one. This gives the copy the thumbnail
 * of the original, also for every video inside a copied folder.
 *
 * Moves need nothing: Nextcloud keeps the file ID, even when moving to another storage.
 *
 * @template-implements IEventListener<NodeCopiedEvent>
 */
class CarryThumbnails implements IEventListener {
	public function __construct(
		private ThumbnailStore $store,
		private LoggerInterface $logger,
	) {
	}

	public function handle(Event $event): void {
		if (!$event instanceof NodeCopiedEvent) {
			return;
		}
		try {
			$source = $event->getSource();
			$target = $event->getTarget();
			if ($source instanceof File) {
				$this->store->copy($source->getId(), $target->getId());
				return;
			}
			if (!$source instanceof Folder || !$target instanceof Folder) {
				return;
			}
			foreach ($source->searchByMime('video') as $video) {
				if ($video instanceof File && $this->store->exists($video->getId())) {
					$copy = $target->get(ltrim(substr($video->getPath(), strlen($source->getPath())), '/'));
					$this->store->copy($video->getId(), $copy->getId());
				}
			}
		} catch (\Throwable $e) {
			// Never get in the way of the copy; the browser renders a new thumbnail if need be
			$this->logger->warning('Could not copy video thumbnails along', ['exception' => $e]);
		}
	}
}
