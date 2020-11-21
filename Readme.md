# PHP Vue Loader

Has basic functionality to process *.vue files and output them as batched `<style>` and `<script>` tags.

## Caveats
 - Do not use `import` at all or unless you know what you're doing, because they will not be evaluated by this plugin
 - Because components will all become global components through the use of `Vue.component`, there is no need or point to importing components for local registration