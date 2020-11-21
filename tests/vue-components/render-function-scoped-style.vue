<script>
export default {
	functional: true,

	props: ['msg'],

	render(h, context) {
		return h('div', { class: 'render-fn' }, 'Render function scoped styles');
	}
}
</script>

<style scoped>
	div {
		color: green;
	}
</style>