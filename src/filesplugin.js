/*
 * Copyright (c) 2022 Julien Veyssier <eneiluj@posteo.net>
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */
import SendFilesModal from './components/SendFilesModal.vue'

import axios from '@nextcloud/axios'
import moment from '@nextcloud/moment'
import { generateUrl } from '@nextcloud/router'
import { showSuccess, showError } from '@nextcloud/dialogs'
import { translate as t, translatePlural as n } from '@nextcloud/l10n'

import Vue from 'vue'
import './bootstrap.js'

const DEBUG = false

function openConversationSelector(files) {
	OCA.Wire.filesToSend = files
	const modalVue = OCA.Wire.WireSendModalVue
	modalVue.updateConversations()
	modalVue.setFiles([...files])
	modalVue.showModal()
}

(function() {
	if (!OCA.Wire) {
		/**
		 * @namespace
		 */
		OCA.Wire = {
			filesToSend: [],
		}
	}

	/**
	 * @namespace
	 */
	OCA.Wire.FilesPlugin = {
		ignoreLists: [
			'trashbin',
			'files.public',
		],

		attach(fileList) {
			if (DEBUG) console.debug('[Wire] begin of attach')
			if (this.ignoreLists.indexOf(fileList.id) >= 0) {
				return
			}

			fileList.registerMultiSelectFileAction({
				name: 'WireSendMulti',
				displayName: (context) => {
					if (OCA.Wire.wireConnected) {
						return t('integration_wire', 'Send files to Wire')
					}
					return ''
				},
				iconClass: () => {
					if (OCA.Wire.wireConnected) {
						return 'icon-wire'
					}
				},
				order: -2,
				action: (selectedFiles) => {
					const filesToSend = selectedFiles.map((f) => {
						return {
							id: f.id,
							name: f.name,
							type: f.type,
							size: f.size,
						}
					})
					if (OCA.Wire.wireConnected) {
						openConversationSelector(filesToSend)
					} else {
						this.connectToWire(filesToSend)
					}
				},
			})

			fileList.fileActions.registerAction({
				name: 'wireSendSingle',
				displayName: (context) => {
					if (OCA.Wire.wireConnected) {
						return t('integration_wire', 'Send to Wire')
					}
					return ''
				},
				mime: 'all',
				order: -139,
				iconClass: (fileName, context) => {
					if (OCA.Wire.wireConnected) {
						return 'icon-wire'
					}
				},
				permissions: OC.PERMISSION_READ,
				actionHandler: (fileName, context) => {
					const filesToSend = [
						{
							id: context.fileInfoModel.attributes.id,
							name: context.fileInfoModel.attributes.name,
							type: context.fileInfoModel.attributes.type,
							size: context.fileInfoModel.attributes.size,
						},
					]
					if (OCA.Wire.wireConnected) {
						openConversationSelector(filesToSend)
					} else {
						this.connectToWire(filesToSend)
					}
				},
			})
		},

		connectToWire: (selectedFiles = []) => {
			console.debug('[WIRE] connect')
		},
	}

})()

function sendLinks(conversationId, conversationName, comment, permission, expirationDate, password) {
	const req = {
		fileIds: OCA.Wire.filesToSend.map((f) => f.id),
		conversationId,
		conversationName,
		comment,
		permission,
		expirationDate: expirationDate ? moment(expirationDate).format('YYYY-MM-DD') : undefined,
		password,
	}
	const url = generateUrl('apps/integration_wire/sendLinks')
	axios.post(url, req).then((response) => {
		const number = OCA.Wire.filesToSend.length
		showSuccess(
			n(
				'integration_wire',
				'A link to {fileName} was sent to {conversationName}',
				'{number} links were sent to {conversationName}',
				number,
				{
					fileName: OCA.Wire.filesToSend[0].name,
					conversationName,
					number,
				}
			)
		)
		OCA.Wire.WireSendModalVue.success()
	}).catch((error) => {
		console.error(error)
		OCA.Wire.WireSendModalVue.failure()
		OCA.Wire.filesToSend = []
		showError(
			t('integration_wire', 'Failed to send links to Wire')
			+ ' ' + error.response?.request?.responseText
		)
	})
}

function sendFileLoop(conversationId, conversationName, count = 0) {
	if (OCA.Wire.filesToSend.length === 0) {
		showSuccess(
			n(
				'integration_wire',
				'{count} file was sent to {conversationName}',
				'{count} files were sent to {conversationName}',
				count,
				{
					conversationName,
					count,
				}
			)
		)
		OCA.Wire.WireSendModalVue.success()
		return
	}

	const file = OCA.Wire.filesToSend.shift()
	// skip directories
	if (file.type === 'dir') {
		sendFileLoop(conversationId, conversationName, count)
		return
	}
	OCA.Wire.WireSendModalVue.fileStarted(file.id)
	const req = {
		fileId: file.id,
		conversationId,
	}
	const url = generateUrl('apps/integration_wire/sendFile')
	axios.post(url, req).then((response) => {
		// finished
		if (OCA.Wire.filesToSend.length === 0) {
			showSuccess(
				n(
					'integration_wire',
					'{fileName} was sent to {conversationName}',
					'{count} files were sent to {conversationName}',
					count + 1,
					{
						fileName: file.name,
						conversationName,
						count: count + 1,
					}
				)
			)
			OCA.Wire.WireSendModalVue.success()
		} else {
			// not finished
			OCA.Wire.WireSendModalVue.fileFinished(file.id)
			sendFileLoop(conversationId, conversationName, count + 1)
		}
	}).catch((error) => {
		console.error(error)
		OCA.Wire.WireSendModalVue.failure()
		OCA.Wire.filesToSend = []
		showError(
			t('integration_wire', 'Failed to send {name} to Wire', { name: file.name })
			+ ' ' + error.response?.request?.responseText
		)
	})
}

function sendMessage(conversationId, message) {
	const req = {
		message,
		conversationId,
	}
	const url = generateUrl('apps/integration_wire/sendMessage')
	return axios.post(url, req)
}

// send file modal
const modalId = 'wireSendModal'
const modalElement = document.createElement('div')
modalElement.id = modalId
document.body.append(modalElement)

const View = Vue.extend(SendFilesModal)
OCA.Wire.WireSendModalVue = new View().$mount(modalElement)

OCA.Wire.WireSendModalVue.$on('closed', () => {
	if (DEBUG) console.debug('[Wire] modal closed')
})
OCA.Wire.WireSendModalVue.$on('validate', ({ filesToSend, conversationId, conversationName, type, comment, permission, expirationDate, password }) => {
	OCA.Wire.filesToSend = filesToSend
	if (type === 'link') {
		sendLinks(conversationId, conversationName, comment, permission, expirationDate, password)
	} else {
		sendMessage(conversationId, comment).then((response) => {
			sendFileLoop(conversationId, conversationName)
		}).catch((error) => {
			console.error(error)
			OCA.Wire.WireSendModalVue.failure()
			OCA.Wire.filesToSend = []
			showError(
				t('integration_wire', 'Failed to send files to Wire')
				+ ': ' + error.response?.request?.responseText
			)
		})
	}
})

// get Wire state
const urlCheckConnection = generateUrl('/apps/integration_wire/is-connected')
axios.get(urlCheckConnection).then((response) => {
	OCA.Wire.wireConnected = response.data.connected
	OCA.Wire.wireUrl = response.data.url
	if (DEBUG) console.debug('[Wire] OCA.Wire', OCA.Wire)
	OC.Plugins.register('OCA.Files.FileList', OCA.Wire.FilesPlugin)
}).catch((error) => {
	console.error(error)
})
