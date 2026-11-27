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
use OCP\Files\Events\Node\BeforeNodeRenamedEvent;
use OCP\Files\Events\Node\NodeCopiedEvent;
use OCP\Files\Events\Node\NodeRenamedEvent;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\Node;
use Psr\Log\LoggerInterface;

/**
 * Thumbnails are stored per file ID. A copy gets a new ID, and so does a file moved to another
 * storage (external storage, a folder someone shared). This hands the thumbnail on to the new ID,
 * also for every video inside a copied or moved folder.
 *
 * After a move the old path is gone, so the IDs are taken down before it, keyed by the old path.
 *
 * @template-implements IEventListener<Event>
 */
class CarryThumbnails implements IEventListener {
	/** @var array<string, array<string, int>> old path => (path inside it => file ID) */
	private static array $beforeMove = [];

	public function __construct(
		private ThumbnailStore $store,
		private LoggerInterface $logger,
	) {
	}

	public function handle(Event $event): void {
		try {
			if ($event instanceof BeforeNodeRenamedEvent) {
				$source = $event->getSource();
				if (!$this->staysOnStorage($source, $event->getTarget())) {
					self::$beforeMove[$source->getPath()] = $this->videosWithThumbnail($source);
				}
			} elseif ($event instanceof NodeRenamedEvent) {
				$from = self::$beforeMove[$event->getSource()->getPath()] ?? [];
				unset(self::$beforeMove[$event->getSource()->getPath()]);
				$this->carry($from, $event->getTarget(), true);
			} elseif ($event instanceof NodeCopiedEvent) {
				$this->carry($this->videosWithThumbnail($event->getSource()), $event->getTarget(), false);
			}
		} catch (\Throwable $e) {
			// Never get in the way of the file operation; the browser renders a new one if need be
			$this->logger->warning('Could not carry video thumbnails along', ['exception' => $e]);
		}
	}

	/**
	 * Within one storage a move keeps the file IDs, so there is nothing to do.
	 */
	private function staysOnStorage(Node $source, Node $target): bool {
		try {
			return $source->getStorage()->getId() === $target->getParent()->getStorage()->getId();
		} catch (\Throwable) {
			return false;
		}
	}

	/**
	 * @return array<string, int> path relative to $node ('' for $node itself) => file ID
	 */
	private function videosWithThumbnail(Node $node): array {
		$nodes = $node instanceof Folder ? $node->searchByMime('video') : [$node];
		$found = [];
		foreach ($nodes as $video) {
			if ($video instanceof File && str_starts_with($video->getMimetype(), 'video/') && $this->store->exists($video->getId())) {
				$found[substr($video->getPath(), strlen($node->getPath()))] = $video->getId();
			}
		}
		return $found;
	}

	/**
	 * @param array<string, int> $from path relative to the old node => old file ID
	 */
	private function carry(array $from, Node $target, bool $move): void {
		foreach ($from as $relative => $oldId) {
			$node = $relative === '' ? $target : ($target instanceof Folder ? $target->get(ltrim($relative, '/')) : null);
			if ($node === null || $node->getId() === $oldId) {
				continue;
			}
			$this->store->copy($oldId, $node->getId());
			if ($move) {
				$this->store->delete($oldId);
			}
		}
	}
}
