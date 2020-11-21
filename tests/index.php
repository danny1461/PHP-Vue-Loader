<html>
	<head>
		<title>Vue Loader Tests - By Daniel Flynn</title>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/vue/2.6.12/vue.js"></script>
		<style>
			#vue-app {
				transition: opacity 500ms;
				opacity: 0;
			}

			#vue-app.vue-initialized {
				opacity: 1;
			}
		</style>
	</head>
	<body>
		<div id="vue-app">
			<different-name-than-file-slightly></different-name-than-file-slightly>
			<functional-component-with-template msg="Hello"></functional-component-with-template>
			<functional-component-with-render msg="World!"></functional-component-with-render>
			<functional-component-with-template-scoped-styles msg="Vue is"></functional-component-with-template-scoped-styles>
			<functional-component-with-render-scoped-styles msg="Cool!"></functional-component-with-render-scoped-styles>
			<global-styles></global-styles>
			<scoped-styles></scoped-styles>
			<render-function-scoped-style></render-function-scoped-style>
			<deep-selector></deep-selector>
		</div>

		<?php
			require __DIR__ . '/../src/VueLoader.php';

			$vueFiles = glob(__DIR__ . '/vue-components/*.vue');
			VueLoader::Render($vueFiles, '#vue-app');
		?>
	</body>
</html>