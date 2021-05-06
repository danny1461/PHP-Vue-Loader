<?php

/**
 * VueLoader by Daniel Flynn
 * https://github.com/danny1461/PHP-Vue-Loader
 */

require_once __DIR__ . '/vendor/autoload.php';

class VueLoader {
	private const DEEP_REGEX_SELECTOR = '/\\s*(?:>>>|\\/deep\\/|::v-deep)/';
	
	private static $utilsLoaded = false;
	private static $usedHashes = [];
	private static $loadedComponents = [];

    private static function LoadComponent($vueFile) {
        $fileName = pathinfo($vueFile, PATHINFO_FILENAME);
		$fileHtml = file_get_contents($vueFile);
		$dom = new \DiDom\Document('<html><body>' . $fileHtml . '</body></html>');

		$meta = [
			'styles' => []
		];

		$template = null;
		$componentDefinition = null;
		$scopedStyles = [];
		
		foreach ($dom->find('body')[0]->children() as $topLevelNode) {
			if (!$topLevelNode->isTextNode()) {
				switch ($topLevelNode->tag) {
					case 'template':
						$template = $topLevelNode;
						break;
					case 'script':
						$componentDefinition = preg_replace('/export\\s+default/', 'return', $topLevelNode->text());
						break;
					case 'style':
						$style = trim($topLevelNode->text());
						if (!$style) {
							continue 2;
						}

						$type = $topLevelNode->hasAttribute('scoped')
							? 'scoped'
							: 'global';

						if ($type == 'scoped') {
							$scopedStyles[] = count($meta['styles']);
						}

						$meta['styles'][] = $style;
						break;
				}
			}
		}

		if (is_null($componentDefinition)) {
			$componentDefinition = 'return {}';
		}

		$componentDefinition = '(function(){' . $componentDefinition . '})()';
		
		if (count($scopedStyles)) {
			$hash = self::getHash($fileName);
			self::$usedHashes[$hash] = true;
			$attr = 'data-v-' . $hash;

			foreach ($scopedStyles as $ndx) {
				$meta['styles'][$ndx] = self::addAttributeToStyle($meta['styles'][$ndx], $attr);
			}
			
			if ($template) {
				self::addAttributeToTemplate($template, $attr);
			}
			else {
				$componentDefinition = '(function(){var obj=' . $componentDefinition . ';obj.render=phpVueLoader.render("' . $hash . '", obj.render);return obj})()';
				$meta['utilsNeeded'] = true;
			}
		}
		
		if ($template) {
			if ($template->hasAttribute('functional')) {
				$componentDefinition = '(function(){var obj=' . $componentDefinition . ';obj.functional=true;obj.render=phpVueLoader.makeFunctionalRenderer(' . json_encode($template->innerHtml()) . ');return obj})()';
				$meta['utilsNeeded'] = true;
			}
			else {
				$componentDefinition = '(function(){var obj=' . $componentDefinition . ';obj.template=' . json_encode($template->innerHtml()) . ';return obj})()';
			}
		}

		$meta['script'] = '(function(){var obj=' . $componentDefinition . ';Vue.component(obj.name || "' . $fileName . '", obj)})();';
		self::$loadedComponents[$vueFile] = $meta;
    }

    private static function getHash($input) {
        $hash = '';
        while (true) {
            if ($hash) {
                $input .= ' ';
            }

            $hash = substr(md5($input), 0, 8);
            if (!isset(self::$usedHashes[$hash])) {
                break;
            }
        }

        return $hash;
    }

    private static function addAttributeToTemplate($node, $attr) {
        if (!$node->isTextNode() && !$node->isCommentNode()) {
            if ($node->tag != 'slot') {
                $node->setAttribute($attr, null);
            }

            foreach ($node->children() as $child) {
                self::addAttributeToTemplate($child, $attr);
            }
        }
    }

    private static function addAttributeToStyle($style, $attr) {
        $attr = '[' . $attr . ']';

        $parser = new \Sabberworm\CSS\Parser($style);
        $doc = $parser->parse();

        foreach($doc->getAllDeclarationBlocks() as $block) {
            foreach($block->getSelectors() as $selector) {
                $selStr = $selector->getSelector();
                $deepSelStr = preg_replace(self::DEEP_REGEX_SELECTOR, $attr, $selStr);
                
                if ($deepSelStr != $selStr) {
                    $selector->setSelector($deepSelStr);
                }
                else {
					// TODO: Really really bad fix
					// Should actually parse the selector and append at the right location
                    $selector->setSelector(preg_replace('/::?(?:after|before)|$/', "{$attr}$0", $selStr, 1));
                }
            }
        }

        return $doc->render();
	}
	
	private static function loadUtilCode() {
		if (self::$utilsLoaded) {
			return '';
		}

		self::$utilsLoaded = true;

		return "var phpVueLoader = (function(){
	var result = {};

	function cloneOptions(val) {
		if (typeof val != 'object') {
			return val;
		}

		var result;
		if (Array.isArray(val)) {
			result = [];
			for (var i = 0; i < val.length; i++) {
				result.push(cloneOptions(val[i]));
			}
		}
		else {
			result = {};
			for (var i in val) {
				result[i] = cloneOptions(val[i]);
			}
		}
		
		return result;
	}

	result.render = function (hash, renderFn) {
		return function(h, context) {
			return renderFn.call(this, function(a, b, c) {
				if (!c) {
					c = b;
					b = {};
				}
				else {
					b = cloneOptions(b);
				}

				if (!b.attrs) {
					b.attrs = {};
				}

				b.attrs['data-v-' + hash] = '';
				return h(a, b, c);
			}, context);
		};
	};

	function makeNewContext(context) {
		var result = {};

		for (var i in context) {
			(function(i) {
				Object.defineProperty(result, i, {
					get: function() {
						return context[i]
					}
				});
			})(i);
		}

		for (var i = 0; i < makeNewContext.props.length; i++) {
			for (var j in context[makeNewContext.props[i]]) {
				(function(i, j) {
					Object.defineProperty(result, j, {
						get: function() {
							return context[makeNewContext.props[i]][j];
						}
					});
				})(i, j);
			}
		}

		return result;
	}
	makeNewContext.props = ['data', 'props', 'injections'];

	result.makeFunctionalRenderer = function (template) {
		if (!Vue.compile) {
			console.error('PHP Vue Loader: functional components defined with the template tag requires the full vue.js script');
		}
		
		var render = Vue.compile(template).render;

		return function(h, context) {
			return render.call(makeNewContext(context));
		};
	};

	return result;
})();";
	}

	private static function processInput($vueFiles) {
		if (!$vueFiles) {
			return;
		}

		if (!is_array($vueFiles)) {
			$vueFiles = [$vueFiles];
		}

		foreach ($vueFiles as $file) {
			if (!isset(self::$loadedComponents[$file])) {
				self::LoadComponent($file);
			}
		}

		return array_intersect_key(self::$loadedComponents, array_flip($vueFiles));
	}

    public static function render($vueFiles, string $handleElement = '') {
		$componentsToRender = self::processInput($vueFiles);
		if (count($componentsToRender)) {
			echo '<style>';
			foreach ($componentsToRender as $meta) {
				echo implode('', $meta['styles']);
			}
			echo '</style>';

			echo '<script type="text/javascript">';
			foreach ($componentsToRender as $meta) {
				if (isset($meta['utilsNeeded'])) {
					echo self::loadUtilCode();
				}

				echo $meta['script'];
			}
			echo '</script>';
		}

		if ($handleElement) {
			echo "
<script>
	(function() {
		function start() {
			new Vue({
				el: '{$handleElement}',
				mounted: function() {
					this.\$el.className += ' vue-initialized';
				}
			});
		}

		if (document.readyState != 'loading'){
			start();
		} else {
			document.addEventListener('DOMContentLoaded', start);
		}
	})();
</script>";
		}
	}

	public static function getStyles($vueFiles) {
		$result = '';
		$componentsToRender = self::processInput($vueFiles);
		if (count($componentsToRender)) {
			foreach ($componentsToRender as $meta) {
				$result .= implode('', $meta['styles']);
			}
		}

		return $result;
	}

	public static function getScripts($vueFiles) {
		$result = '';
		$componentsToRender = self::processInput($vueFiles);
		if (count($componentsToRender)) {
			foreach ($componentsToRender as $meta) {
				if (isset($meta['utilsNeeded'])) {
					$result .= self::loadUtilCode();
				}

				$result .= $meta['script'];
			}
		}

		return $result;
	}

	public static function ResetFlags() {
		self::$utilsLoaded = false;
	}
}