<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Unispezi
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\ClientsideVideoThumbnails\Settings;

use OCA\ClientsideVideoThumbnails\AppInfo\Application;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Settings\ISettings;
use OCP\Util;

/**
 * Page for finding all of the user's videos without a preview and rendering them in the browser.
 */
class Personal implements ISettings {
	public function getForm(): TemplateResponse {
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
