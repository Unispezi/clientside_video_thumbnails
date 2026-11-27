<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Unispezi
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\ClientsideVideoThumbnails\Settings;

use OCA\ClientsideVideoThumbnails\AppInfo\Application;
use OCA\ClientsideVideoThumbnails\Service\Preferences;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IUserSession;
use OCP\Settings\ISettings;
use OCP\Util;

/**
 * Page for finding all of the user's videos without a preview and rendering them in the browser.
 */
class Personal implements ISettings {
	public function __construct(
		private IInitialState $initialState,
		private IUserSession $userSession,
		private Preferences $preferences,
	) {
	}

	public function getForm(): TemplateResponse {
		$user = $this->userSession->getUser();
		if ($user !== null) {
			$this->initialState->provideInitialState('preferences', $this->preferences->get($user->getUID()));
		}
		// Translations for the scripts; templates get theirs anyway
		Util::addTranslations(Application::APP_ID);
		Util::addScript(Application::APP_ID, 'core');
		Util::addScript(Application::APP_ID, 'personal');
		Util::addStyle(Application::APP_ID, 'personal');
		return new TemplateResponse(Application::APP_ID, 'personal');
	}

	public function getSection(): string {
		return Application::APP_ID;
	}

	public function getPriority(): int {
		return 50;
	}
}
