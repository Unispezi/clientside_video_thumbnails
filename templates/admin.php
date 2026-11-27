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
	<h2><?php p($l->t('Clientside Video Thumbnails')); ?> <a target="_blank" rel="noreferrer noopener" class="icon-info" title="<?php p($l->t('Open documentation')); ?>" href="https://unispezi.github.io/clientside_video_thumbnails/admin.html"></a></h2>
	<p class="settings-hint">
		<?php p($l->t('Video previews are rendered in the browser of whoever opens a folder in Files, and uploaded to this server. Videos the browser can\'t play stay without a preview.')); ?>
	</p>
</div>

<div class="section">
	<h2><?php p($l->t('Delete ALL thumbnails of all users')); ?></h2>
	<p class="settings-hint">
		<?php p($l->t('Deletes ALL video thumbnails of all users. They will be regenerated the next time a video is listed.')); ?>
	</p>
	<button id="cvt-delete-all" class="button" type="button"><?php p($l->t('Delete all thumbnails of all users')); ?></button>
	<span id="cvt-delete-all-message" class="msg" aria-live="polite"></span>
</div>
</div>
