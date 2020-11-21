<script>
export default {
	functional: true,

	props: ['msg'],

	render: function(h, context) {
		return h('div', ['Functional component with render scoped styles. Prop: ', context.props.msg]);
	}
}
</script>

<style scoped>
	div {
		color: orange;
	}
</style>