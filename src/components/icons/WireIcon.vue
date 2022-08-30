<template>
	<span :aria-hidden="!title"
		:aria-label="title"
		class="material-design-icon wire-icon"
		role="img"
		v-bind="$attrs"
		@click="$emit('click', $event)">
		<svg :fill="fillColor"
			:width="size"
			:height="size"
			enable-background="new 0 0 26.883 26.883"
			version="1.1"
			viewBox="0 0 26.883 26.883"
			xml:space="preserve"
			xmlns="http://www.w3.org/2000/svg">
			<g transform="translate(0 3)">
				<path d="m18.614 20.43c3.7059 0 6.6874-2.9931 6.6874-6.6724h1e-4v-12.953h1.5813v12.952c0 4.5448-3.6993 8.2425-8.2771 8.2425-1.9408 0-3.6992-0.6696-5.1144-1.7817-1.4152 1.1121-3.2152 1.7817-5.156 1.7817-4.5778 0-8.3353-3.6977-8.3353-8.2425v-12.952h1.5813v12.953c0 3.6792 3.0314 6.6724 6.7373 6.6724 1.4928 0 2.8852-0.4915 4.0018-1.312-1.2484-1.4428-2.0417-3.3136-2.0417-5.3604l0.0027-10.618c0-1.7314 1.4188-3.14 3.1627-3.14s3.1626 1.4087 3.1626 3.14l-0.0027 10.618c0 2.0467-0.7433 3.9176-1.9918 5.3604 1.1166 0.8205 2.5092 1.312 4.0018 1.312zm-6.754-6.6725c0 1.6334 0.5962 3.1297 1.5813 4.2908 0.9851-1.1611 1.5813-2.6574 1.5813-4.2907l0.0028-10.618c0-0.86587-0.7094-1.57-1.5813-1.57s-1.5813 0.7041-1.5813 1.57z" clip-rule="evenodd" fill-rule="evenodd" />
			</g>
		</svg>
	</span>
</template>

<script>
export default {
	name: 'WireIcon',
	props: {
		title: {
			type: String,
			default: '',
		},
		fillColor: {
			type: String,
			default: 'currentColor',
		},
		size: {
			type: Number,
			default: 24,
		},
	},
}
</script>
