/**
 * SPDX-FileCopyrightText: 2026 silversong88, Unispezi
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
(function() {
	'use strict'

	const translate = (text, vars) => (typeof window.t === 'function' ? window.t('clientside_video_thumbnails', text, vars, undefined, { escape: false }) : text.replace(/{(\w+)}/g, (m, k) => (vars && k in vars ? vars[k] : m)))

	function init() {
		const root = document.getElementById('clientside-video-thumbnails-admin')
		if (!root) {
			return
		}
		const deleteAll = document.getElementById('cvt-delete-all')
		const message = document.getElementById('cvt-delete-all-message')

		deleteAll.addEventListener('click', async () => {
			if (!window.confirm(translate('Delete all video thumbnails of all users?'))) {
				return
			}
			deleteAll.disabled = true
			message.textContent = ''
			message.classList.remove('ok')
			try {
				const response = await fetch(root.dataset.deleteAllUrl, {
					method: 'POST',
					headers: { requesttoken: document.head.dataset.requesttoken, Accept: 'application/json' },
				})
				if (!response.ok) {
					throw new Error(translate('Request failed ({status})', { status: response.status }))
				}
				// This browser's list of videos it could not render, so that it tries them again too
				localStorage.removeItem('clientside_video_thumbnails.failed')
				message.textContent = translate('Deleted.')
				message.classList.add('ok')
			} catch (e) {
				message.textContent = e.message
			} finally {
				deleteAll.disabled = false
			}
		})
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init)
	} else {
		init()
	}
})()
