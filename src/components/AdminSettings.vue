<template>
	<div id="wire_prefs" class="section">
		<h2>
			<WireIcon class="icon" />
			{{ t('integration_wire', 'Wire integration') }}
		</h2>
		<div id="wire-content">
			<div class="line">
				<label for="wire-instance">
					<EarthIcon :size="20" class="icon" />
					{{ t('integration_wire', 'Default Wire instance address') }}
				</label>
				<input id="wire-instance"
					v-model="state.url"
					type="text"
					:placeholder="t('integration_wire', 'Wire instance address')"
					@input="onInput">
			</div>
			<br>
			<p class="settings-hint">
				<InformationOutlineIcon :size="20" class="icon" />
				{{ t('integration_wire', 'Leave this empty to use https://wire.com') }}
			</p>
		</div>
	</div>
</template>

<script>
import InformationOutlineIcon from 'vue-material-design-icons/InformationOutline.vue'
import EarthIcon from 'vue-material-design-icons/Earth.vue'

import WireIcon from './icons/WireIcon.vue'

import { loadState } from '@nextcloud/initial-state'
import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import { showSuccess, showError } from '@nextcloud/dialogs'

import { delay } from '../utils.js'

export default {
	name: 'AdminSettings',

	components: {
		WireIcon,
		InformationOutlineIcon,
		EarthIcon,
	},

	props: [],

	data() {
		return {
			state: loadState('integration_wire', 'admin-config'),
		}
	},

	watch: {
	},

	mounted() {
	},

	methods: {
		onInput() {
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
			const url = generateUrl('/apps/integration_wire/admin-config')
			axios.put(url, req).then((response) => {
				showSuccess(t('integration_wire', 'Wire administration options saved'))
			}).catch((error) => {
				showError(
					t('integration_wire', 'Failed to save Wire administration options')
					+ ': ' + (error.response?.request?.responseText ?? '')
				)
				console.debug(error)
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
