<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 silversong88
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\ClientsideVideoThumbnails\Migration;

use OCA\ClientsideVideoThumbnails\AppInfo\Application;
use OCA\ClientsideVideoThumbnails\Service\ThumbnailStore;
use OCP\IAppConfig;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

/**
 * Removes all stored thumbnails and app settings when the app is uninstalled.
 */
class UninstallCleanup implements IRepairStep {
	public function __construct(
		private ThumbnailStore $store,
		private IAppConfig $appConfig,
	) {
	}

	public function getName(): string {
		return 'Remove Clientside Video Thumbnails thumbnails and settings';
	}

	public function run(IOutput $output): void {
		$this->store->deleteAll();
		$this->appConfig->deleteApp(Application::APP_ID);
	}
}
