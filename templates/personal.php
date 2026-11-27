<?php
/**
 * SPDX-FileCopyrightText: 2026 Unispezi
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * @var \OCP\IL10N $l
 */
?>
<div id="clientside-video-thumbnails-personal" class="section">
	<h2><?php p($l->t('Find and fix videos without thumbnail')); ?></h2>
	<p class="settings-hint">
		<?php p($l->t('Searches all your files for videos without a preview and renders one in this browser. Keep this page open until it is done. Each video costs a few MB of data.')); ?>
	</p>

	<p>
		<input id="cvt-retry" type="checkbox" class="checkbox">
		<label for="cvt-retry"><?php p($l->t('Also retry videos this browser could not render before')); ?></label>
	</p>

	<button id="cvt-start" class="button primary" type="button"><?php p($l->t('Find and fix')); ?></button>
	<button id="cvt-stop" class="button hidden" type="button"><?php p($l->t('Stop')); ?></button>

	<ul id="cvt-warnings" class="cvt-warnings hidden"></ul>
	<p id="cvt-status" aria-live="polite"></p>
	<progress id="cvt-progress" class="hidden" max="1" value="0"></progress>
	<p id="cvt-current" class="cvt-current"></p>

	<details id="cvt-failures" class="hidden">
		<summary id="cvt-failures-title"></summary>
		<ul id="cvt-failures-list"></ul>
	</details>

	<h3><?php p($l->t('When to render in Files')); ?></h3>
	<p class="settings-hint">
		<?php p($l->t('Applies to your account on all your devices, for the thumbnails rendered while you browse your files. Browsers that can\'t tell their network or battery state, such as Safari and Firefox, render anyway.')); ?>
	</p>
	<p>
		<input id="cvt-wifi-only" type="checkbox" class="checkbox">
		<label for="cvt-wifi-only"><?php p($l->t('Only on Wi-Fi or Ethernet')); ?></label>
	</p>
	<p>
		<input id="cvt-charging-only" type="checkbox" class="checkbox">
		<label for="cvt-charging-only"><?php p($l->t('Only while charging')); ?></label>
	</p>
	<p id="cvt-device-state" class="cvt-current" aria-live="polite"></p>

	<h3><?php p($l->t('Delete my thumbnails')); ?></h3>
	<p class="settings-hint">
		<?php p($l->t('Deletes the thumbnails of all your own videos. Videos others shared with you keep theirs. Your videos get new ones the next time they show up in Files or here.')); ?>
	</p>
	<button id="cvt-delete-mine" class="button" type="button"><?php p($l->t('Delete all my thumbnails')); ?></button>
	<span id="cvt-delete-mine-status" aria-live="polite"></span>
</div>
