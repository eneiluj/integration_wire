<template>
	<div class="wire-modal-container">
		<Modal v-if="show"
			size="small"
			@close="closeModal">
			<div class="wire-modal-content">
				<h2 class="modal-title">
					<WireIcon class="icon" />
					<span>
						{{ t('integration_wire', 'Connect to {wireUrl}', { wireUrl }) }}
					</span>
				</h2>
				<div class="field">
					<label for="wire-login">
						<AccountIcon :size="20" class="icon" />
						{{ t('integration_wire', 'Login') }}
					</label>
					<input id="wire-login"
						v-model="login"
						type="text"
						:placeholder="t('integration_wire', 'Wire login (usually email address)')"
						@keyup.enter="onConnectClick">
				</div>
				<div class="field">
					<label for="wire-password">
						<LockIcon :size="20" class="icon" />
						{{ t('integration_wire', 'Password') }}
					</label>
					<input id="wire-password"
						v-model="password"
						type="password"
						:placeholder="t('integration_wire', 'Password')"
						@keyup.enter="onConnectClick">
				</div>
				<div class="wire-footer">
					<div class="spacer" />
					<NcButton
						@click="closeModal">
						{{ t('integration_wire', 'Cancel') }}
					</NcButton>
					<NcButton type="primary"
						:disabled="!canValidate"
						@click="onConnectClick">
						<template #icon>
							<LoginVariantIcon />
						</template>
						{{ t('integration_wire', 'Connect') }}
					</NcButton>
				</div>
			</div>
		</Modal>
	</div>
</template>

<script>
import Modal from '@nextcloud/vue/dist/Components/Modal.js'
import NcButton from '@nextcloud/vue/dist/Components/Button.js'

import LockIcon from 'vue-material-design-icons/Lock.vue'
import AccountIcon from 'vue-material-design-icons/Account.vue'
import LoginVariantIcon from 'vue-material-design-icons/LoginVariant.vue'

import WireIcon from './icons/WireIcon.vue'

export default {
	name: 'LoginModal',

	components: {
		WireIcon,
		Modal,
		NcButton,
		LoginVariantIcon,
		LockIcon,
		AccountIcon,
	},

	props: {
		wireUrl: {
			type: String,
			required: true,
		},
	},

	data() {
		return {
			show: false,
			login: '',
			password: '',
		}
	},

	computed: {
		canValidate() {
			return !!this.login && !!this.password
		},
	},

	watch: {
	},

	mounted() {
		this.reset()
	},

	methods: {
		reset() {
			this.login = ''
			this.password = ''
		},
		showModal() {
			this.show = true
		},
		closeModal() {
			this.show = false
			this.$emit('closed')
			this.reset()
		},
		onConnectClick() {
			this.$emit('validate', {
				login: this.login,
				password: this.password,
			})
			this.show = false
			this.reset()
		},
	},
}
</script>

<style scoped lang="scss">
.wire-modal-content {
	width: 100%;
	padding: 16px;
	display: flex;
	flex-direction: column;

	.spacer {
		flex-grow: 1;
	}

	h2,
	.field {
		display: flex;
		flex-wrap: wrap;
		label {
			display: flex;
			align-items: center;
			width: 200px;
			margin: 8px 0 8px 0;
		}
		input {
			width: 100%;
		}
		.icon {
			margin-right: 4px;
		}
	}

	h2 .icon {
		margin-right: 8px;
	}

	.wire-footer {
		display: flex;
		margin-top: 20px;
		> * {
			margin-left: 8px;
		}
	}
}
</style>
