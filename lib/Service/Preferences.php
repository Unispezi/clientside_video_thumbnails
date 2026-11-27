<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Unispezi
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\ClientsideVideoThumbnails\Service;

use OCA\ClientsideVideoThumbnails\AppInfo\Application;
use OCP\IConfig;

/**
 * Per-account switches for when the Files app may render thumbnails. The browser enforces them;
 * the server only stores them.
 *
 * IConfig instead of IUserConfig, which only exists from Nextcloud 32 on.
 */
class Preferences {
	public const KEYS = ['wifi_only', 'charging_only'];

	public function __construct(
		private IConfig $config,
	) {
	}

	/**
	 * @return array<string, bool>
	 */
	public function get(string $uid): array {
		$values = [];
		foreach (self::KEYS as $key) {
			$values[$key] = $this->config->getUserValue($uid, Application::APP_ID, $key, '0') === '1';
		}
		return $values;
	}

	public function set(string $uid, string $key, bool $value): void {
		if (!in_array($key, self::KEYS, true)) {
			throw new \InvalidArgumentException('Unknown preference ' . $key);
		}
		$this->config->setUserValue($uid, Application::APP_ID, $key, $value ? '1' : '0');
	}

	public function deleteAll(): void {
		$this->config->deleteAppFromAllUsers(Application::APP_ID);
	}
}
