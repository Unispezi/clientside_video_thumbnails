<?php
/**
 * SPDX-FileCopyrightText: 2026 silversong88
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * @var array{secretSet: bool, secretUrl: string, apiBase: string} $_
 * @var \OCP\IL10N $l
 */
?>
<div id="clientside-video-thumbnails-admin" class="section" data-secret-url="<?php p($_['secretUrl']); ?>">
	<h2><?php p($l->t('Clientside Video Thumbnails')); ?></h2>
	<p class="settings-hint">
		<?php p($l->t('Video previews are rendered in the browser of whoever opens a folder in Files, and uploaded to this server. Videos the browser can\'t play can be handled by an optional worker on your own machine. Set the same secret in the worker\'s NC_SECRET setting so that, apart from signed-in browser sessions, only your worker can upload thumbnails.')); ?>
	</p>

	<p>
		<?php p($l->t('API endpoint:')); ?>
		<code><?php p($_['apiBase']); ?></code>
	</p>

	<p id="vtr-status">
		<?php if ($_['secretSet']): ?>
			<?php p($l->t('A secret is set. API requests without it are rejected, except from signed-in browser sessions.')); ?>
		<?php else: ?>
			<?php p($l->t('No secret is set. Any signed-in user or app password can upload thumbnails for their own videos.')); ?>
		<?php endif; ?>
	</p>

	<div id="vtr-new-secret" class="vtr-new-secret hidden">
		<p><?php p($l->t('Copy this secret into the worker\'s NC_SECRET now. It won\'t be shown again.')); ?></p>
		<input id="vtr-secret-value" type="text" readonly aria-label="<?php p($l->t('New secret')); ?>">
	</div>

	<button id="vtr-generate" class="button primary" type="button"><?php p($l->t('Generate new secret')); ?></button>
	<button id="vtr-clear" class="button" type="button" <?php if (!$_['secretSet']): ?>disabled<?php endif; ?>><?php p($l->t('Remove secret')); ?></button>
	<span id="vtr-message" class="msg" aria-live="polite"></span>
</div>
