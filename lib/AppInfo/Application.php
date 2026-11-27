<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Vinzenz Hersche, silversong88
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\ClientsideVideoThumbnails\AppInfo;

use OCA\ClientsideVideoThumbnails\Listener\LoadFilesScript;
use OCA\ClientsideVideoThumbnails\Preview\PreviewProvider;
use OCA\Files\Event\LoadAdditionalScriptsEvent;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;

class Application extends App implements IBootstrap {
	public const APP_ID = 'clientside_video_thumbnails';

	public function __construct(array $urlParams = []) {
		parent::__construct(self::APP_ID, $urlParams);
	}

	public function register(IRegistrationContext $context): void {
		$context->registerPreviewProvider(PreviewProvider::class, PreviewProvider::MIME_REGEX);
		$context->registerEventListener(LoadAdditionalScriptsEvent::class, LoadFilesScript::class);
	}

	public function boot(IBootContext $context): void {
	}
}
