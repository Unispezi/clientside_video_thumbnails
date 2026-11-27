<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Vinzenz Hersche, silversong88
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\ClientsideVideoThumbnails\AppInfo;

use OCA\ClientsideVideoThumbnails\Listener\CarryThumbnails;
use OCA\ClientsideVideoThumbnails\Listener\LoadFilesScript;
use OCA\ClientsideVideoThumbnails\Preview\PreviewProvider;
use OCA\Files\Event\LoadAdditionalScriptsEvent;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\Files\Events\Node\BeforeNodeRenamedEvent;
use OCP\Files\Events\Node\NodeCopiedEvent;
use OCP\Files\Events\Node\NodeRenamedEvent;

class Application extends App implements IBootstrap {
	public const APP_ID = 'clientside_video_thumbnails';

	public function __construct(array $urlParams = []) {
		parent::__construct(self::APP_ID, $urlParams);
	}

	public function register(IRegistrationContext $context): void {
		$context->registerPreviewProvider(PreviewProvider::class, PreviewProvider::MIME_REGEX);
		$context->registerEventListener(LoadAdditionalScriptsEvent::class, LoadFilesScript::class);
		$context->registerEventListener(NodeCopiedEvent::class, CarryThumbnails::class);
		$context->registerEventListener(BeforeNodeRenamedEvent::class, CarryThumbnails::class);
		$context->registerEventListener(NodeRenamedEvent::class, CarryThumbnails::class);
	}

	public function boot(IBootContext $context): void {
	}
}
