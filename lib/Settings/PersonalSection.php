<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Unispezi
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\ClientsideVideoThumbnails\Settings;

use OCA\ClientsideVideoThumbnails\AppInfo\Application;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Settings\IIconSection;

/**
 * Own section in the personal settings: the built-in "Additional settings" section
 * only shows up once two apps use it.
 */
class PersonalSection implements IIconSection {
	public function __construct(
		private IL10N $l,
		private IURLGenerator $urlGenerator,
	) {
	}

	public function getID(): string {
		return Application::APP_ID;
	}

	public function getName(): string {
		return $this->l->t('Video thumbnails');
	}

	public function getPriority(): int {
		return 90;
	}

	public function getIcon(): string {
		return $this->urlGenerator->imagePath(Application::APP_ID, 'app.svg');
	}
}
