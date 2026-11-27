/**
 * SPDX-FileCopyrightText: 2026 Unispezi
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * Shared by the Files list script (files-thumbs.js) and the personal settings page
 * (personal.js): grab one frame of a video with <video> + canvas, upload it, and
 * remember videos this browser can't decode.
 */
(function() {
	'use strict'

	const APP_ID = 'clientside_video_thumbnails'
	const PREFIX = APP_ID + '.'
	const FAIL_KEY = PREFIX + 'failed'
	const FAIL_RETRY_MS = 30 * 24 * 60 * 60 * 1000
	const FRAME_TIMEOUT_MS = 20000
	/** iOS Safari often ignores preload: if no metadata arrives by then, start muted playback to make it load */
	const METADATA_KICK_MS = 3000
	/** How long to wait for the decoded frame after seeking before drawing anyway */
	const FRAME_CALLBACK_MS = 1000
	// Same frame and size as Nextcloud's own video previews (lib/private/Preview/Movie.php): the frame at
	// 5 s, or 1 s, or the first one for shorter videos; full resolution up to the largest preview size
	// (preview_max_x/y, 4096 by default). The server scales it down for every smaller size.
	const MAX_EDGE = 4096
	const JPEG_QUALITY = 0.9

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

	function clearFailed() {
		localStorage.removeItem(FAIL_KEY)
	}

	// ---- context ----

	function currentUser() {
		return window.OC?.getCurrentUser?.()?.uid || document.head.dataset.user || null
	}

	function requestToken() {
		return window.OC?.requestToken || document.head.dataset.requesttoken || ''
	}

	function webroot() {
		return window.OC?.webroot ?? ''
	}

	/** The user asked the browser to save mobile data (Chrome's Data Saver / Android data saver). */
	function saveData() {
		return navigator.connection?.saveData === true
	}

	// ---- the account's switches, and what this device can tell about its network and battery ----

	const DEFAULT_PREFERENCES = { wifi_only: false, charging_only: false }
	let preferences = null

	/** The account's switches, as the server handed them to the page and as changed since. */
	function getPreferences() {
		if (preferences === null) {
			try {
				preferences = { ...DEFAULT_PREFERENCES, ...window.OCP?.InitialState?.loadState(APP_ID, 'preferences') }
			} catch {
				preferences = { ...DEFAULT_PREFERENCES }
			}
		}
		return preferences
	}

	async function setPreference(key, value) {
		const response = await fetch(webroot() + '/index.php/apps/' + APP_ID + '/preferences', {
			method: 'PUT',
			headers: { requesttoken: requestToken(), Accept: 'application/json', 'Content-Type': 'application/json' },
			body: JSON.stringify({ key, value }),
		})
		if (!response.ok) {
			throw new Error('HTTP ' + response.status)
		}
		preferences = { ...DEFAULT_PREFERENCES, ...(await response.json()).preferences }
		return preferences
	}

	/**
	 * true on Wi-Fi or Ethernet, false on mobile data and the like, null where the browser doesn't
	 * say (Safari, Firefox, desktop Chrome often says "unknown").
	 */
	function onWifi() {
		const type = navigator.connection?.type
		if (!type || type === 'unknown' || type === 'other' || type === 'none') {
			return null
		}
		return type === 'wifi' || type === 'ethernet'
	}

	/** true while charging, false on battery, null where the browser doesn't say (Safari, Firefox). */
	async function isCharging() {
		try {
			return typeof navigator.getBattery === 'function' ? (await navigator.getBattery()).charging : null
		} catch {
			return null
		}
	}

	/** Calls back whenever the network or the charging state changes. */
	async function onConditionsChange(callback) {
		navigator.connection?.addEventListener?.('change', callback)
		try {
			(await navigator.getBattery?.())?.addEventListener('chargingchange', callback)
		} catch {
		}
	}

	/** Whether the account's switches allow rendering on this device right now. Unknown counts as allowed. */
	async function conditionsAllow() {
		const { wifi_only: wifiOnly, charging_only: chargingOnly } = getPreferences()
		return !(wifiOnly && onWifi() === false) && !(chargingOnly && (await isCharging()) === false)
	}

	function davUrl(uid, path) {
		const encoded = path.split('/').filter(Boolean).map(encodeURIComponent).join('/')
		return webroot() + '/remote.php/dav/files/' + encodeURIComponent(uid) + '/' + encoded
	}

	// ---- rendering and uploading ----

	function renderFrame(src) {
		return new Promise((resolve, reject) => {
			const video = document.createElement('video')
			video.muted = true
			video.defaultMuted = true
			video.playsInline = true
			video.setAttribute('muted', '')
			video.setAttribute('playsinline', '')
			video.preload = 'metadata'
			// In the DOM but invisible: mobile browsers decode and present frames more reliably for attached elements
			video.setAttribute('aria-hidden', 'true')
			video.style.cssText = 'position:fixed;left:0;top:0;width:2px;height:2px;opacity:0.01;pointer-events:none;z-index:-1'

			const timers = []
			let done = false
			const cleanup = () => {
				done = true
				timers.forEach(clearTimeout)
				video.pause()
				video.removeAttribute('src')
				video.load()
				video.remove()
			}
			const fail = (message) => {
				if (done) {
					return
				}
				cleanup()
				reject(new Error(message))
			}
			timers.push(setTimeout(() => fail('timeout'), FRAME_TIMEOUT_MS))
			timers.push(setTimeout(() => {
				if (!done && video.readyState < HTMLMediaElement.HAVE_METADATA) {
					video.play()?.catch(() => {})
				}
			}, METADATA_KICK_MS))

			const draw = () => {
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
			}

			video.addEventListener('error', () => fail('cannot decode (' + (video.error?.code ?? '?') + ')'), { once: true })
			video.addEventListener('loadedmetadata', () => {
				if (!video.videoWidth || !video.videoHeight) {
					fail('no video track')
					return
				}
				video.pause()
				const duration = Number.isFinite(video.duration) ? video.duration : 0
				video.currentTime = duration > 5 ? 5 : duration > 1 ? 1 : 0
			}, { once: true })
			video.addEventListener('seeked', () => {
				if (done) {
					return
				}
				// 'seeked' can come before the frame is decoded (black image on some mobile browsers).
				// Wait for the frame to be presented where the browser can tell us, but not forever.
				if (typeof video.requestVideoFrameCallback === 'function') {
					video.requestVideoFrameCallback(() => draw())
					timers.push(setTimeout(draw, FRAME_CALLBACK_MS))
				} else {
					draw()
				}
			}, { once: true })

			document.body.appendChild(video)
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

	window.ClientsideVideoThumbnails = {
		APP_ID,
		PREFIX,
		isFailed,
		markFailed,
		clearFailed,
		currentUser,
		requestToken,
		webroot,
		saveData,
		getPreferences,
		setPreference,
		onWifi,
		isCharging,
		onConditionsChange,
		conditionsAllow,
		davUrl,
		renderFrame,
		upload,
	}
})()
