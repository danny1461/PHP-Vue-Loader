<template>
	<div>
		Deep Selector: Loading in the &lt;render-function-scoped-style&gt; component and changing color to blue
		<render-function-scoped-style></render-function-scoped-style>
	</div>
</template>

<style scoped>
	/* Can use '>>>' or '/deep/' or '::v-deep' */
	div >>> .render-fn {
		margin-left: 20px;
		color: blue;
	}
</style>