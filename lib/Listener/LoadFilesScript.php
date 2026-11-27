<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Unispezi
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\ClientsideVideoThumbnails\Listener;

use OCA\ClientsideVideoThumbnails\AppInfo\Application;
use OCA\ClientsideVideoThumbnails\Service\Preferences;
use OCA\Files\Event\LoadAdditionalScriptsEvent;
use OCP\AppFramework\Services\IInitialState;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IUserSession;
use OCP\Util;

/**
 * Loads the script that renders missing video previews in the browser into the Files app.
 *
 * @template-implements IEventListener<LoadAdditionalScriptsEvent>
 */
class LoadFilesScript implements IEventListener {
	public function __construct(
		private IInitialState $initialState,
		private IUserSession $userSession,
		private Preferences $preferences,
	) {
	}

	public function handle(Event $event): void {
		if (!$event instanceof LoadAdditionalScriptsEvent) {
			return;
		}
		$user = $this->userSession->getUser();
		if ($user !== null) {
			$this->initialState->provideInitialState('preferences', $this->preferences->get($user->getUID()));
		}
		Util::addScript(Application::APP_ID, 'core');
		Util::addScript(Application::APP_ID, 'files-thumbs');
	}
}
