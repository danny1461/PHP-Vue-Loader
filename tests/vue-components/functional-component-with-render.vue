<script>
export default {
	functional: true,

	props: ['msg'],

	render: function(h, context) {
		return h('div', ['Functional component with render. Prop: ', context.props.msg]);
	}
}
</script>