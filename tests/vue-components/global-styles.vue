<template>
	<div>Global Styles Component. Added a pseudo element to body</div>
</template>

<style>
	body:after {
		content: 'From global-styles component';
	}
</style>