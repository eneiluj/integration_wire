import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import { showError, showSuccess } from '@nextcloud/dialogs'

let mytimer = 0
export function delay(callback, ms) {
	return function() {
		const context = this
		const args = arguments
		clearTimeout(mytimer)
		mytimer = setTimeout(function() {
			callback.apply(context, args)
		}, ms || 0)
	}
}

export function doLogin(login, password) {
	return new Promise((resolve, reject) => {
		const req = {
			values: {
				login,
				password,
			},
		}
		const url = generateUrl('/apps/integration_wire/config')
		axios.put(url, req).then((response) => {
			if (response.data?.user_name) {
				showSuccess(t('integration_wire', 'Successfully connected to Wire!'))
				resolve(response.data?.user_name)
				return
			}
			showError(t('integration_wire', 'Invalid login/password'))
			resolve(null)
		}).catch((error) => {
			showError(
				t('integration_wire', 'Failed to connect to Wire')
				+ ': ' + (error.response?.request?.responseText ?? '')
			)
			console.error(error)
			reject(error)
		})
	})
}

export function connectConfirmDialog(wireUrl) {
	return new Promise((resolve, reject) => {
		const settingsLink = generateUrl('/settings/user/connected-accounts')
		const linkText = t('integration_wire', 'Connected accounts')
		const settingsHtmlLink = `<a href="${settingsLink}" class="external">${linkText}</a>`
		OC.dialogs.message(
			t('integration_wire', 'You need to connect before using the Wire integration.')
			+ '<br><br>'
			+ t('integration_wire', 'Do you want to connect to {wireUrl}?', { wireUrl })
			+ '<br><br>'
			+ t(
				'integration_wire',
				'You can choose another Wire server in the {settingsHtmlLink} section of your personal settings.',
				{ settingsHtmlLink },
				null,
				{ escape: false }
			),
			t('integration_wire', 'Connect to Wire'),
			'none',
			{
				type: OC.dialogs.YES_NO_BUTTONS,
				confirm: t('integration_wire', 'Connect'),
				confirmClasses: 'success',
				cancel: t('integration_wire', 'Cancel'),
			},
			(result) => {
				resolve(result)
			},
			true,
			true,
		)
	})
}

export function humanFileSize(bytes, approx = false, si = false, dp = 1) {
	const thresh = si ? 1000 : 1024

	if (Math.abs(bytes) < thresh) {
		return bytes + ' B'
	}

	const units = si
		? ['kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB']
		: ['KiB', 'MiB', 'GiB', 'TiB', 'PiB', 'EiB', 'ZiB', 'YiB']
	let u = -1
	const r = 10 ** dp

	do {
		bytes /= thresh
		++u
	} while (Math.round(Math.abs(bytes) * r) / r >= thresh && u < units.length - 1)

	if (approx) {
		return Math.floor(bytes) + ' ' + units[u]
	} else {
		return bytes.toFixed(dp) + ' ' + units[u]
	}
}
