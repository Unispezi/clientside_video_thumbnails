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
 * Switch off for this browser: localStorage.setItem('clientside_video_thumbnails.disabled', '1')
 */
(function() {
	'use strict'

	const APP_ID = 'clientside_video_thumbnails'
	const PREFIX = APP_ID + '.'
	const FAIL_KEY = PREFIX + 'failed'
	const FAIL_RETRY_MS = 30 * 24 * 60 * 60 * 1000
	const FRAME_TIMEOUT_MS = 20000
	const MAX_EDGE = 1024
	const JPEG_QUALITY = 0.85

	if (localStorage.getItem(PREFIX + 'disabled') === '1') {
		return
	}

	/** fileid -> object URL of a frame rendered in this page session */
	const rendered = new Map()
	/** fileid -> { fileid, path, davUrl } waiting to be rendered, in the order they were seen */
	const queue = new Map()
	let busy = false
	let scanScheduled = false

	// ---- failure cache: videos this browser can't decode are not retried for a while ----

	function loadFailed() {
		try {
			return JSON.parse(localStorage.getItem(FAIL_KEY) || '{}')
		} catch {
			return {}
		}
	}

	function isFailed(fileid) {
		const at = loadFailed()[fileid]
		return typeof at === 'number' && Date.now() - at < FAIL_RETRY_MS
	}

	function markFailed(fileid) {
		const failed = loadFailed()
		const now = Date.now()
		for (const [id, at] of Object.entries(failed)) {
			if (now - at >= FAIL_RETRY_MS) {
				delete failed[id]
			}
		}
		failed[fileid] = now
		localStorage.setItem(FAIL_KEY, JSON.stringify(failed))
	}

	// ---- context: user, current folder ----

	function currentUser() {
		return window.OC?.getCurrentUser?.()?.uid || document.head.dataset.user || null
	}

	function requestToken() {
		return window.OC?.requestToken || document.head.dataset.requesttoken || ''
	}

	function webroot() {
		return window.OC?.webroot ?? ''
	}

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

	function davUrl(uid, path) {
		const encoded = path.split('/').filter(Boolean).map(encodeURIComponent).join('/')
		return webroot() + '/remote.php/dav/files/' + encodeURIComponent(uid) + '/' + encoded
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
			if (!uid || dir === null || !name || isFailed(fileid)) {
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

	function show(img, fileid, url) {
		img.dataset.cvtOriginal = img.getAttribute('src')
		img.dataset.cvtFileid = fileid
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

	function renderFrame(src) {
		return new Promise((resolve, reject) => {
			const video = document.createElement('video')
			video.muted = true
			video.playsInline = true
			video.preload = 'metadata'

			let timer = null
			let done = false
			const cleanup = () => {
				done = true
				clearTimeout(timer)
				video.removeAttribute('src')
				video.load()
			}
			const fail = (message) => {
				if (done) {
					return
				}
				cleanup()
				reject(new Error(message))
			}
			timer = setTimeout(() => fail('timeout'), FRAME_TIMEOUT_MS)

			video.addEventListener('error', () => fail('cannot decode (' + (video.error?.code ?? '?') + ')'), { once: true })
			video.addEventListener('loadedmetadata', () => {
				if (!video.videoWidth || !video.videoHeight) {
					fail('no video track')
					return
				}
				const duration = Number.isFinite(video.duration) ? video.duration : 0
				video.currentTime = Math.min(1, duration * 0.1) || 0.1
			}, { once: true })
			video.addEventListener('seeked', () => {
				if (done) {
					return
				}
				const scale = Math.min(1, MAX_EDGE / Math.max(video.videoWidth, video.videoHeight))
				const canvas = document.createElement('canvas')
				canvas.width = Math.round(video.videoWidth * scale)
				canvas.height = Math.round(video.videoHeight * scale)
				try {
					canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height)
				} catch (e) {
					fail('cannot draw frame: ' + e.message)
					return
				}
				cleanup()
				canvas.toBlob((blob) => (blob ? resolve(blob) : reject(new Error('cannot encode JPEG'))), 'image/jpeg', JPEG_QUALITY)
			}, { once: true })

			video.src = src
		})
	}

	async function upload(path, blob) {
		const body = new FormData()
		body.append('path', path)
		body.append('thumbnail', blob, 'thumbnail.jpg')
		const response = await fetch(webroot() + '/index.php/apps/' + APP_ID + '/thumbnail/upload', {
			method: 'POST',
			headers: { requesttoken: requestToken(), Accept: 'application/json' },
			body,
		})
		if (!response.ok) {
			let message = 'HTTP ' + response.status
			try {
				message += ' ' + ((await response.json()).message || '')
			} catch {
			}
			throw new Error(message.trim())
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
