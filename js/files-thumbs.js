/**
 * SPDX-FileCopyrightText: 2026 Unispezi
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * Renders previews for videos in the Files list that have none, right in the browser:
 * one frame via <video> + canvas, shown immediately in the row and uploaded so the
 * server serves it as a regular preview from then on.
 *
 * Works only off markup that is the same in Nextcloud 31 to 34: list rows carry
 * data-cy-files-list-row-fileid / -name, and a file without a server preview shows
 * an <img> pointing at /core/mimeicon?mime=<mime>.
 *
 * Needs core.js loaded first.
 *
 * Switch off for this browser: localStorage.setItem('clientside_video_thumbnails.disabled', '1')
 */
(function() {
	'use strict'

	const core = window.ClientsideVideoThumbnails
	if (!core) {
		return
	}
	const { APP_ID, PREFIX, isFailed, markFailed, currentUser, requestToken, saveData, davUrl, renderFrame, upload } = core

	if (localStorage.getItem(PREFIX + 'disabled') === '1') {
		return
	}

	/** fileid -> object URL of a frame rendered in this page session */
	const rendered = new Map()
	/** fileid -> { fileid, path, davUrl } waiting to be rendered, in the order they were seen */
	const queue = new Map()
	let busy = false
	let scanScheduled = false

	// ---- context: user, current folder ----

	/**
	 * Folder of the listed files, or null where rows don't all live in one folder
	 * (favorites, recent, shares, search, …). Only the plain "All files" view qualifies.
	 */
	function currentDir() {
		const path = window.location.pathname
		if (!/\/apps\/files\/files(\/|$)/.test(path) && !/\/apps\/files\/?$/.test(path)) {
			return null
		}
		const params = new URLSearchParams(window.location.search)
		if (params.has('query')) {
			return null
		}
		return params.get('dir') || '/'
	}

	function joinPath(dir, name) {
		return (dir.endsWith('/') ? dir : dir + '/') + name
	}

	// ---- reading the list ----

	/** Video mimetype of a mime icon URL, or null. */
	function videoMimeFromIcon(src) {
		if (!src || !src.includes('/core/mimeicon')) {
			return null
		}
		try {
			const mime = new URL(src, window.location.origin).searchParams.get('mime') || ''
			return mime.startsWith('video/') ? mime : null
		} catch {
			return null
		}
	}

	function scheduleScan() {
		if (scanScheduled) {
			return
		}
		scanScheduled = true
		requestAnimationFrame(() => {
			scanScheduled = false
			scan()
		})
	}

	/**
	 * Walks the rows currently in the DOM. The list is virtual, so these are roughly the
	 * visible ones. Rows are recycled for other files while scrolling, so every image we
	 * touched remembers which file it shows.
	 */
	function scan() {
		const uid = currentUser()
		const dir = currentDir()
		const seen = new Set()

		for (const row of document.querySelectorAll('[data-cy-files-list-row-fileid]')) {
			const fileid = row.getAttribute('data-cy-files-list-row-fileid')
			const name = row.getAttribute('data-cy-files-list-row-name')
			const img = row.querySelector('img.files-list__row-icon-preview')
			if (!fileid || !img) {
				continue
			}

			// Recycled row: our frame belongs to another file. Give Vue's icon back, unless Vue
			// has already put a new image there itself.
			if (img.dataset.cvtFileid && img.dataset.cvtFileid !== fileid) {
				if (img.getAttribute('src')?.startsWith('blob:')) {
					img.src = img.dataset.cvtOriginal
				}
				img.style.objectFit = ''
				delete img.dataset.cvtFileid
				delete img.dataset.cvtOriginal
			}
			if (img.dataset.cvtFileid === fileid) {
				continue
			}
			if (!videoMimeFromIcon(img.getAttribute('src'))) {
				continue
			}

			if (rendered.has(fileid)) {
				show(img, fileid, rendered.get(fileid))
				continue
			}
			if (!uid || dir === null || !name || isFailed(fileid) || saveData()) {
				continue
			}
			seen.add(fileid)
			if (!queue.has(fileid)) {
				const path = joinPath(dir, name)
				queue.set(fileid, { fileid, path, davUrl: davUrl(uid, path) })
			}
		}

		// Rows scrolled away or a folder change: forget what's no longer on screen
		for (const fileid of queue.keys()) {
			if (!seen.has(fileid)) {
				queue.delete(fileid)
			}
		}
		pump()
	}

	/**
	 * Whether Files asks the server for square-cropped previews (the user setting "Crop image
	 * previews", on by default). Our frame keeps its aspect ratio, so it is cropped by CSS instead.
	 */
	function cropPreviews() {
		try {
			return window.OCP?.InitialState?.loadState('files', 'config')?.crop_image_previews !== false
		} catch {
			return true
		}
	}

	function show(img, fileid, url) {
		img.dataset.cvtOriginal = img.getAttribute('src')
		img.dataset.cvtFileid = fileid
		img.style.objectFit = cropPreviews() ? 'cover' : ''
		img.src = url
	}

	function showEverywhere(fileid, url) {
		for (const row of document.querySelectorAll('[data-cy-files-list-row-fileid="' + CSS.escape(fileid) + '"]')) {
			const img = row.querySelector('img.files-list__row-icon-preview')
			if (img && img.dataset.cvtFileid !== fileid && videoMimeFromIcon(img.getAttribute('src'))) {
				show(img, fileid, url)
			}
		}
	}

	// ---- rendering and uploading, one video at a time ----

	async function pump() {
		if (busy) {
			return
		}
		busy = true
		try {
			while (queue.size > 0) {
				const [fileid, item] = queue.entries().next().value
				queue.delete(fileid)
				await process(item)
			}
		} finally {
			busy = false
		}
	}

	async function process(item) {
		// Right after a folder change the URL may already name the new folder while the old
		// rows are still shown, so the guessed path can be wrong. Check before blaming the codec.
		if (!(await isVideoAt(item.davUrl))) {
			return
		}

		let blob
		try {
			blob = await renderFrame(item.davUrl)
		} catch (e) {
			console.info('[' + APP_ID + '] no frame for ' + item.path + ': ' + e.message)
			markFailed(item.fileid)
			return
		}

		const url = URL.createObjectURL(blob)
		rendered.set(item.fileid, url)
		showEverywhere(item.fileid, url)

		try {
			await upload(item.path, blob)
		} catch (e) {
			// Shown for now; the next visit tries again
			console.warn('[' + APP_ID + '] upload failed for ' + item.path + ': ' + e.message)
		}
	}

	async function isVideoAt(url) {
		try {
			const response = await fetch(url, { method: 'HEAD', headers: { requesttoken: requestToken() } })
			return response.ok && (response.headers.get('Content-Type') || '').startsWith('video/')
		} catch {
			return false
		}
	}

	// ---- wiring ----

	function init() {
		new MutationObserver(scheduleScan).observe(document.body, {
			childList: true,
			subtree: true,
			attributes: true,
			attributeFilter: ['src', 'data-cy-files-list-row-fileid'],
		})
		window.addEventListener('popstate', scheduleScan)
		scheduleScan()
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init)
	} else {
		init()
	}
})()
