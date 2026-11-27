/**
 * SPDX-FileCopyrightText: 2026 Unispezi
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * Personal settings page: finds all of the user's videos without a preview with one DAV
 * SEARCH, then renders and uploads a frame for each, one at a time. Needs core.js loaded first.
 */
(function() {
	'use strict'

	const core = window.ClientsideVideoThumbnails
	if (!core) {
		return
	}
	const { APP_ID, isFailed, markFailed, clearFailed, currentUser, requestToken, webroot, davUrl, renderFrame, upload } = core

	const translate = (text, vars) => (typeof window.t === 'function' ? window.t(APP_ID, text, vars) : text.replace(/{(\w+)}/g, (m, k) => (vars && k in vars ? vars[k] : m)))
	const translatePlural = (singular, plural, count, vars) => (typeof window.n === 'function'
		? window.n(APP_ID, singular, plural, count, vars)
		: translate(count === 1 ? singular : plural, { count, ...vars }))

	const NS = {
		d: 'DAV:',
		oc: 'http://owncloud.org/ns',
		nc: 'http://nextcloud.org/ns',
	}

	function escapeXml(text) {
		return text.replace(/[<>&'"]/g, (c) => ({ '<': '&lt;', '>': '&gt;', '&': '&amp;', "'": '&apos;', '"': '&quot;' })[c])
	}

	/**
	 * All videos of the user, with whether the server has a preview for them.
	 * has-preview can be selected but not filtered on, so the filtering happens here.
	 *
	 * @return {Promise<Array<{fileid: string, path: string, hasPreview: boolean}>>}
	 */
	async function findVideos(uid) {
		const body = '<?xml version="1.0" encoding="UTF-8"?>'
			+ '<d:searchrequest xmlns:d="DAV:" xmlns:oc="http://owncloud.org/ns" xmlns:nc="http://nextcloud.org/ns">'
			+ '<d:basicsearch>'
			+ '<d:select><d:prop><oc:fileid/><nc:has-preview/></d:prop></d:select>'
			+ '<d:from><d:scope><d:href>/files/' + escapeXml(uid) + '</d:href><d:depth>infinity</d:depth></d:scope></d:from>'
			+ '<d:where><d:like><d:prop><d:getcontenttype/></d:prop><d:literal>video/%</d:literal></d:like></d:where>'
			+ '<d:orderby/>'
			+ '</d:basicsearch>'
			+ '</d:searchrequest>'
		const response = await fetch(webroot() + '/remote.php/dav/', {
			method: 'SEARCH',
			headers: { 'Content-Type': 'text/xml; charset=utf-8', requesttoken: requestToken() },
			body,
		})
		if (response.status !== 207) {
			throw new Error(translate('Search failed ({status})', { status: response.status }))
		}

		const xml = new DOMParser().parseFromString(await response.text(), 'application/xml')
		const prefix = '/remote.php/dav/files/' + encodeURIComponent(uid) + '/'
		const videos = []
		for (const entry of xml.getElementsByTagNameNS(NS.d, 'response')) {
			const href = entry.getElementsByTagNameNS(NS.d, 'href')[0]?.textContent || ''
			const at = href.indexOf(prefix)
			if (at === -1) {
				continue
			}
			const path = '/' + href.slice(at + prefix.length).split('/').filter(Boolean).map(decodeURIComponent).join('/')
			const fileid = entry.getElementsByTagNameNS(NS.oc, 'fileid')[0]?.textContent || ''
			const hasPreview = entry.getElementsByTagNameNS(NS.nc, 'has-preview')[0]?.textContent === 'true'
			if (fileid) {
				videos.push({ fileid, path, hasPreview })
			}
		}
		return videos
	}

	function init() {
		const root = document.getElementById('clientside-video-thumbnails-personal')
		if (!root) {
			return
		}
		const el = (id) => document.getElementById(id)
		const start = el('cvt-start')
		const stop = el('cvt-stop')
		const retry = el('cvt-retry')
		const status = el('cvt-status')
		const progress = el('cvt-progress')
		const current = el('cvt-current')
		const failures = el('cvt-failures')
		const failuresTitle = el('cvt-failures-title')
		const failuresList = el('cvt-failures-list')

		let stopRequested = false

		function addFailure(path, reason) {
			const item = document.createElement('li')
			item.textContent = path + ' – ' + reason
			failuresList.appendChild(item)
			const count = failuresList.children.length
			failuresTitle.textContent = translatePlural('{count} video without thumbnail', '{count} videos without thumbnail', count, { count })
			failures.classList.remove('hidden')
		}

		function setRunning(running) {
			start.disabled = running
			retry.disabled = running
			stop.disabled = false
			stop.classList.toggle('hidden', !running)
			progress.classList.toggle('hidden', !running)
			if (!running) {
				current.textContent = ''
			}
		}

		async function run() {
			const uid = currentUser()
			if (!uid) {
				status.textContent = translate('Not signed in.')
				return
			}
			stopRequested = false
			failuresList.replaceChildren()
			failures.classList.add('hidden')
			setRunning(true)

			// Keep a phone from going to sleep while it works; not every browser allows it
			let wakeLock = null
			try {
				wakeLock = await navigator.wakeLock?.request('screen')
			} catch {
			}

			try {
				status.textContent = translate('Searching for videos…')
				if (retry.checked) {
					clearFailed()
				}
				const videos = await findVideos(uid)
				const missing = videos.filter((v) => !v.hasPreview)
				const todo = missing.filter((v) => !isFailed(v.fileid))
				const skipped = missing.length - todo.length

				let done = 0
				let failed = 0
				const report = () => {
					status.textContent = translate('{videos} videos, {missing} without thumbnail. Done: {done} of {todo}, failed: {failed}.', {
						videos: videos.length, missing: missing.length, done, todo: todo.length, failed,
					}) + (skipped > 0 ? ' ' + translatePlural('{count} skipped because this browser could not render it before.', '{count} skipped because this browser could not render them before.', skipped, { count: skipped }) : '')
					progress.max = Math.max(1, todo.length)
					progress.value = done + failed
				}
				report()

				for (const video of todo) {
					if (stopRequested) {
						break
					}
					current.textContent = video.path
					let blob
					try {
						blob = await renderFrame(davUrl(uid, video.path))
					} catch (e) {
						markFailed(video.fileid)
						failed++
						addFailure(video.path, translate('cannot render: {reason}', { reason: e.message }))
						report()
						continue
					}
					try {
						await upload(video.path, blob)
						done++
					} catch (e) {
						failed++
						addFailure(video.path, translate('upload failed: {reason}', { reason: e.message }))
					}
					report()
				}
				if (stopRequested) {
					status.textContent += ' ' + translate('Stopped.')
				}
			} catch (e) {
				status.textContent = e.message
			} finally {
				wakeLock?.release().catch(() => {})
				setRunning(false)
			}
		}

		start.addEventListener('click', run)
		stop.addEventListener('click', () => {
			// Takes effect after the video being rendered now
			stopRequested = true
			stop.disabled = true
		})
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init)
	} else {
		init()
	}
})()
