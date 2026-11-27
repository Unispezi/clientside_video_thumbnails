<?php
/**
 * SPDX-FileCopyrightText: 2026 Unispezi
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * @var \OCP\IL10N $l
 */
// Help pages exist in English and German
$docs = 'https://unispezi.github.io/clientside_video_thumbnails/' . (str_starts_with($l->getLanguageCode(), 'de') ? 'de/' : '');
?>
<div id="clientside-video-thumbnails-personal">
<div class="section">
	<h2><?php p($l->t('Find and fix videos without thumbnail')); ?> <a target="_blank" rel="noreferrer noopener" class="icon-info" title="<?php p($l->t('Open documentation')); ?>" href="<?php p($docs); ?>user.html"></a></h2>
	<p class="settings-hint">
		<?php p($l->t('Searches all your files for videos without a preview and renders one in this browser. Keep this page open until it is done. Each video costs a few MB of data.')); ?>
	</p>

	<p>
		<input id="cvt-retry" type="checkbox" class="checkbox">
		<label for="cvt-retry"><?php p($l->t('Retry videos with errors')); ?></label>
	</p>

	<button id="cvt-start" class="button primary" type="button"><?php p($l->t('Create missing thumbnails')); ?></button>
	<button id="cvt-stop" class="button hidden" type="button"><?php p($l->t('Stop')); ?></button>

	<ul id="cvt-warnings" class="cvt-warnings hidden"></ul>
	<p id="cvt-status" aria-live="polite"></p>
	<progress id="cvt-progress" class="hidden" max="1" value="0"></progress>
	<p id="cvt-current" class="cvt-current"></p>

	<details id="cvt-failures" class="hidden">
		<summary id="cvt-failures-title"></summary>
		<ul id="cvt-failures-list"></ul>
	</details>
</div>

<div class="section">
	<h2><?php p($l->t('Automatically generate video thumbnails')); ?></h2>
	<p class="settings-hint">
		<?php p($l->t('Generate video thumbnails when videos are listed. This will download a part of the video and upload the thumbnail, so it uses some battery and network traffic.')); ?>
	</p>
	<p>
		<input id="cvt-wifi-only" type="checkbox" class="checkbox">
		<label for="cvt-wifi-only"><?php p($l->t('Mobile device: only on Wi-Fi')); ?></label>
	</p>
	<p>
		<input id="cvt-charging-only" type="checkbox" class="checkbox">
		<label for="cvt-charging-only"><?php p($l->t('Mobile device: only while charging')); ?></label>
	</p>
	<p id="cvt-device-state" class="cvt-current" aria-live="polite"></p>
</div>

<div class="section">
	<h2><?php p($l->t('Delete ALL my thumbnails')); ?></h2>
	<p class="settings-hint">
		<?php p($l->t('Deletes ALL thumbnails of your videos. They will be regenerated the next time a video is listed.')); ?>
	</p>
	<button id="cvt-delete-mine" class="button" type="button"><?php p($l->t('Delete all my thumbnails')); ?></button>
	<span id="cvt-delete-mine-status" aria-live="polite"></span>
</div>
</div>
