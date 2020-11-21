<script>
export default {
	functional: true,

	props: ['msg'],

	render: function(h, context) {
		return h('div', 'Render function scoped styles');
	}
}
</script>

<style scoped>
	div {
		color: green;
	}
</style>