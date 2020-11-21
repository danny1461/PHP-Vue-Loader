# PHP Vue Loader

Has basic functionality to process *.vue files and output them as batched `<style>` and `<script>` tags.

## Caveats
 - Use `v-on` syntax in favor of `@`. The `@` is not a valid attribute character and will not be read by the parser
 - Use `v-slot` syntax in favor of `#`. Just like the `@`, `#` is skipped by the parser
 - Do not use `import` at all or unless you know what you're doing, because they will not be evaluated by this plugin
 - Because components will all become global components through the use of `Vue.component`, there is no need or point to importing components for local registration