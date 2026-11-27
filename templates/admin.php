<?php
/**
 * SPDX-FileCopyrightText: 2026 silversong88, Unispezi
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * @var array{deleteAllUrl: string} $_
 * @var \OCP\IL10N $l
 */
?>
<div id="clientside-video-thumbnails-admin" data-delete-all-url="<?php p($_['deleteAllUrl']); ?>">
<div class="section">
	<h2><?php p($l->t('Clientside Video Thumbnails')); ?></h2>
	<p class="settings-hint">
		<?php p($l->t('Video previews are rendered in the browser of whoever opens a folder in Files, and uploaded to this server. Videos the browser can\'t play stay without a preview.')); ?>
	</p>
</div>

<div class="section">
	<h2><?php p($l->t('Start over')); ?></h2>
	<p class="settings-hint">
		<?php p($l->t('Deletes every video thumbnail of all users, and the previews Nextcloud made from them. Browsers render them again when the videos show up in Files. Meant for testing.')); ?>
	</p>
	<button id="cvt-delete-all" class="button" type="button"><?php p($l->t('Delete all video thumbnails')); ?></button>
	<span id="cvt-delete-all-message" class="msg" aria-live="polite"></span>
</div>
</div>
