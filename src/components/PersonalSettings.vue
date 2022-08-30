<template>
	<div id="wire_prefs" class="section">
		<h2>
			<WireIcon class="icon" />
			{{ t('integration_wire', 'Wire integration') }}
		</h2>
		<CheckboxRadioSwitch
			class="top-element"
			:checked.sync="state.navigation_enabled"
			@update:checked="onCheckboxChanged($event, 'navigation_enabled')">
			{{ t('integration_wire', 'Enable navigation link') }}
		</CheckboxRadioSwitch>
		<br>
		<div id="wire-content">
			<div id="mattermost-connect-block">
				<div class="line">
					<label for="wire-url">
						<EarthIcon :size="20" class="icon" />
						{{ t('integration_wire', 'Wire instance address') }}
					</label>
					<input id="wire-url"
						v-model="state.url"
						type="text"
						:disabled="connected === true"
						:placeholder="t('integration_wire', 'https://wire.com')"
						@input="onInput">
				</div>
				<div v-show="showLoginPassword"
					class="line">
					<label
						for="wire-login">
						<AccountIcon :size="20" class="icon" />
						{{ t('integration_wire', 'Login') }}
					</label>
					<input id="wire-login"
						v-model="login"
						type="text"
						:placeholder="t('integration_wire', 'Wire login (usually email address)')"
						@keyup.enter="onConnectClick">
				</div>
				<div v-show="showLoginPassword"
					class="line">
					<label
						for="wire-password">
						<LockIcon :size="20" class="icon" />
						{{ t('integration_wire', 'Password') }}
					</label>
					<input id="wire-password"
						v-model="password"
						type="password"
						:placeholder="t('integration_wire', 'Password')"
						@keyup.enter="onConnectClick">
				</div>
				<NcButton v-if="!connected"
					:disabled="loading === true || !(login && password)"
					:class="{ loading }"
					@click="onConnectClick">
					<template #icon>
						<OpenInNewIcon />
					</template>
					{{ t('integration_wire', 'Connect to Wire') }}
				</NcButton>
				<div v-if="connected" class="line">
					<label>
						<CheckIcon :size="20" class="icon" />
						{{ t('integration_wire', 'Connected as {user}', { user: connectedDisplayName }) }}
					</label>
					<NcButton @click="onLogoutClick">
						<template #icon>
							<CloseIcon />
						</template>
						{{ t('integration_wire', 'Disconnect from Wire') }}
					</NcButton>
				</div>
			</div>
		</div>
	</div>
</template>

<script>
import LockIcon from 'vue-material-design-icons/Lock.vue'
import AccountIcon from 'vue-material-design-icons/Account.vue'
import OpenInNewIcon from 'vue-material-design-icons/OpenInNew.vue'
import CloseIcon from 'vue-material-design-icons/Close.vue'
// import InformationOutlineIcon from 'vue-material-design-icons/InformationOutline.vue'
import EarthIcon from 'vue-material-design-icons/Earth.vue'
import CheckIcon from 'vue-material-design-icons/Check.vue'

import WireIcon from './icons/WireIcon.vue'

import NcButton from '@nextcloud/vue/dist/Components/Button.js'
import { loadState } from '@nextcloud/initial-state'
import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import { delay } from '../utils.js'
import { showSuccess, showError } from '@nextcloud/dialogs'
import CheckboxRadioSwitch from '@nextcloud/vue/dist/Components/CheckboxRadioSwitch.js'

export default {
	name: 'PersonalSettings',

	components: {
		WireIcon,
		CheckboxRadioSwitch,
		NcButton,
		OpenInNewIcon,
		CloseIcon,
		// InformationOutlineIcon,
		EarthIcon,
		CheckIcon,
		LockIcon,
		AccountIcon,
	},

	props: [],

	data() {
		return {
			state: loadState('integration_wire', 'user-config'),
			loading: false,
			login: '',
			password: '',
		}
	},

	computed: {
		connected() {
			return !!this.state.token
				&& !!this.state.user_name
		},
		connectedDisplayName() {
			return this.state.user_displayname + ' (' + this.state.user_name + ')'
		},
		showLoginPassword() {
			return !this.connected && !this.state.token
		},
	},

	watch: {
	},

	mounted() {
	},

	methods: {
		onLogoutClick() {
			this.state.token = ''
			this.login = ''
			this.password = ''
			this.saveOptions({ token: '' })
		},
		onCheckboxChanged(newValue, key) {
			this.saveOptions({ [key]: newValue ? '1' : '0' })
		},
		onInput() {
			this.loading = true
			delay(() => {
				this.saveOptions({
					url: this.state.url,
				})
			}, 2000)()
		},
		saveOptions(values) {
			const req = {
				values,
			}
			const url = generateUrl('/apps/integration_wire/config')
			axios.put(url, req).then((response) => {
				if (response.data.user_name !== undefined) {
					this.state.user_name = response.data.user_name
					if (this.login && this.password && response.data.user_name === '') {
						showError(t('integration_wire', 'Invalid login/password'))
					} else if (response.data.user_name) {
						showSuccess(t('integration_wire', 'Successfully connected to Mattermost!'))
						this.state.user_id = response.data.user_id
						this.state.user_name = response.data.user_name
						this.state.user_displayname = response.data.user_displayname
						this.state.token = 'dumdum'
					}
				} else {
					showSuccess(t('integration_wire', 'Wire options saved'))
				}
			}).catch((error) => {
				showError(
					t('integration_wire', 'Failed to save Mattermost options')
					+ ': ' + (error.response?.request?.responseText ?? '')
				)
				console.error(error)
			}).then(() => {
				this.loading = false
			})
		},
		onConnectClick() {
			if (this.login && this.password) {
				this.connectWithCredentials()
			}
		},
		connectWithCredentials() {
			this.loading = true
			this.saveOptions({
				login: this.login,
				password: this.password,
				url: this.state.url,
			})
		},
	},
}
</script>

<style scoped lang="scss">
#wire_prefs {
	#wire-content{
		margin-left: 40px;
	}

	h2,
	.line,
	.settings-hint {
		display: flex;
		align-items: center;
		.icon {
			margin-right: 4px;
		}
	}

	h2 .icon {
		margin-right: 8px;
	}

	.line {
		> label {
			width: 300px;
			display: flex;
			align-items: center;
		}
		> input {
			width: 250px;
		}
	}
}
</style>
