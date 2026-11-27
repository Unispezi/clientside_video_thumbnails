<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Unispezi
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * Stub for psalm: Nextcloud's internal preview watcher, not part of nextcloud/ocp.
 */

namespace OC\Preview;

use OCP\Files\Node;

class Watcher {
	public function postWrite(Node $node): void {
	}
}
