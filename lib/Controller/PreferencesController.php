<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Unispezi
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\ClientsideVideoThumbnails\Controller;

use OCA\ClientsideVideoThumbnails\Service\Preferences;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IUserSession;

class PreferencesController extends Controller {
	public function __construct(
		string $appName,
		IRequest $request,
		private IUserSession $userSession,
		private Preferences $preferences,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Sets one of the signed-in user's own switches.
	 */
	#[NoAdminRequired]
	public function set(string $key, bool $value): JSONResponse {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return new JSONResponse(['status' => 'error', 'message' => 'Not signed in'], Http::STATUS_UNAUTHORIZED);
		}
		if (!in_array($key, Preferences::KEYS, true)) {
			return new JSONResponse(['status' => 'error', 'message' => 'Unknown preference'], Http::STATUS_BAD_REQUEST);
		}
		$this->preferences->set($user->getUID(), $key, $value);
		return new JSONResponse(['status' => 'success', 'preferences' => $this->preferences->get($user->getUID())]);
	}
}
