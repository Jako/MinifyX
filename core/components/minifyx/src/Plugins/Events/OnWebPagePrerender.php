<?php
/**
 * @package minifyx
 * @subpackage plugin
 */

namespace TreehillStudio\MinifyX\Plugins\Events;

use Assetic\Asset\AssetCollection;
use Assetic\Asset\FileAsset;
use Assetic\Filter\CoffeeScriptFilter;
use Assetic\Filter\JavaScriptMinifierFilter;
use Assetic\Filter\LessphpFilter;
use Assetic\Filter\ScssphpFilter;
use Assetic\Filter\StylesheetMinifyFilter;
use Exception;
use Minifier\TinyMinify;
use modChunk;
use TreehillStudio\MinifyX\Plugins\Plugin;
use xPDO;

class OnWebPagePrerender extends Plugin
{
    /**
     * @var array[]
     */
    private $registeredScripts = [
        'head' => [],
        'body' => []
    ];

    /**
     * @return false|void
     */
    public function process()
    {
        $minify = false;
        $time = microtime(true);

        // Process registered scripts and styles
        if ($this->minifyx->getOption('process_registered', null, false, true)) {
            $this->modx->resource->_output = $this->processRegistered($this->modx->resource->_output);
            $minify = true;
        }
        // Process images
        if ($this->minifyx->getOption('process_images', null, false, true)) {
            $this->modx->resource->_output = $this->processImages($this->modx->resource->_output);
            $minify = true;
        }
        // Process HTML Minify the page content
        if ($this->minifyx->getOption('minifyHtml', null, false)) {
            $this->modx->resource->_output = $this->processHtml($this->modx->resource->_output);
            $minify = true;
        }

        if ($minify) {
            $this->modx->log(xPDO::LOG_LEVEL_INFO, '[MinifyX] Total time for page "' . $this->modx->resource->id . '" = ' . (microtime(true) - $time));
        }
    }

    /**
     * @param string $code
     * @return string
     */
    private function processRegistered($code)
    {
        // Get registered scripts
        $clientStartupScripts = $this->modx->getRegisteredClientStartupScripts();
        $clientScripts = $this->modx->getRegisteredClientScripts();

        // Remove inserted registered scripts in the current code
        if ($clientStartupScripts) {
            $code = str_replace($clientStartupScripts . "\n", '', $code);
        }
        if ($clientScripts) {
            $code = str_replace($clientScripts . "\n", '', $code);
        }

        // Any cached minified scripts?
        $minifiedScripts = $this->modx->cacheManager->get('mfr_' . md5($clientStartupScripts . $clientScripts));

        // If minified scripts are not cached, collect them
        if (!is_array($minifiedScripts) || empty($minifiedScripts)) {
            $startupScripts = ($clientStartupScripts) ? explode("\n", $clientStartupScripts) : [];
            $scripts = ($clientScripts) ? explode("\n", $clientScripts) : [];

            // Collect the registered scripts
            $this->collectRegisted($startupScripts, 'head');
            $this->collectRegisted($scripts, 'body');

            // Prepare the output of the registered blocks
            $minifiedScripts = [
                'head' => [],
                'body' => []
            ];
            $minifiedScripts['head'][] = $this->registerBlock($this->registeredScripts['head']['cssexternal'], '<link href="[[+script]]" rel="stylesheet" type="text/css">');
            $minifiedScripts['head'][] = $this->registerMinBlock($this->registeredScripts['head']['cssmin'], '<link href="[[+minPath]]?f=[[+scripts]]" rel="stylesheet" type="text/css">');
            $minifiedScripts['head'][] = $this->registerBlock($this->registeredScripts['head']['jsexternal'], '<script src="[[+script]]" type="text/javascript"></script>');
            $minifiedScripts['head'][] = $this->registerMinBlock($this->registeredScripts['head']['jsmin'], '<script src="[[+minPath]]?f=[[+scripts]]" type="text/javascript"></script>');
            $minifiedScripts['head'][] = $this->registerBlock($this->registeredScripts['head']['nomin'], '<script src="[[+script]]" type="text/javascript"></script>');
            $minifiedScripts['head'][] = $this->registerBlock($this->registeredScripts['head']['untouched'], '[[+script]]');
            $minifiedScripts['body'][] = $this->registerBlock($this->registeredScripts['body']['jsexternal'], '<script src="[[+script]]" type="text/javascript"></script>');
            $minifiedScripts['body'][] = $this->registerMinBlock($this->registeredScripts['body']['jsmin'], '<script src="[[+minPath]]?f=[[+scripts]]" type="text/javascript"></script>');
            $minifiedScripts['body'][] = $this->registerBlock($this->registeredScripts['body']['nomin'], '<script src="[[+script]]" type="text/javascript"></script>');
            $minifiedScripts['body'][] = $this->registerBlock($this->registeredScripts['body']['untouched'], '[[+script]]');

            $minifiedScripts['head'] = array_filter($minifiedScripts['head']);
            $minifiedScripts['body'] = array_filter($minifiedScripts['body']);

            // Cache the result
            $this->modx->cacheManager->set('mfr_' . md5($clientStartupScripts . $clientScripts), $minifiedScripts);
        }

        // Insert minified scripts
        if ($minifiedScripts['head']) {
            $code = str_replace('</head>', implode("\r\n", $minifiedScripts['head']) . '</head>', $code);
        }
        if ($minifiedScripts['body']) {
            $code = str_replace('</body>', implode("\r\n", $minifiedScripts['body']) . '</body>', $code);
        }
        return $code;
    }

    /**
     * Collect the registered scripts into sections
     *
     * @param array $scripts
     * @param string $section
     */
    private function collectRegisted($scripts, $section)
    {
        $conditional = false;
        $this->registeredScripts[$section] = [
            'cssexternal' => [],
            'cssmin' => [],
            'jsexternal' => [],
            'jsmin' => [],
            'untouched' => [],
        ];
        foreach ($scripts as $scriptSrc) {
            if (preg_match('/<!--\[if /', trim($scriptSrc), $tag) || $conditional) {
                // don't touch conditional css/scripts
                $this->registeredScripts[$section]['untouched'][] = $scriptSrc;
                $conditional = true;
                if (preg_match('/endif]-->/', trim($scriptSrc), $tag)) {
                    $conditional = false;
                }
            } else {
                preg_match('/^<(script|link)[^>]+>/', trim($scriptSrc), $tag);
                if ($tag && preg_match('/(src|href)=\"(.*?)(\?v=.*?)?"/', $tag[0], $src)) {
                    // if there is a filename referenced in the registered line
                    if (
                        substr(trim($src[2]), -strlen('js')) == '.js' ||
                        substr(trim($src[2]), -strlen('js')) == '.coffee'
                    ) {
                        // the registered chunk is a separate javascript
                        if (strpos('http', $src[2]) === 0 || strpos('//', $src[2]) === 0) {
                            // do not minify scripts with an external url
                            $this->registeredScripts[$section]['jsexternal'][] = $src[2];
                        } elseif (!empty($this->minifyx->getOption('exclude_registered')) && preg_match($this->minifyx->getOption('exclude_registered'), $src[2])) {
                            // do not minify scripts matched with excludeJs
                            $this->registeredScripts[$section]['jsnomin'][] = $src[2];
                        } elseif (strpos($this->minifyx->getOption('cacheFolder'), $src[2]) === 0) {
                            // do not minify scripts in the MinifyX cache folder (added by the MinifyX script)
                            $this->registeredScripts[$section]['jsnomin'][] = $src[2];
                        } else {
                            // minify scripts
                            $this->registeredScripts[$section]['jsmin'][] = $src[2];
                        }
                    } elseif (
                        substr(trim($src[2]), -strlen('.css')) == '.css' ||
                        substr(trim($src[2]), -strlen('.scss')) == '.scss' ||
                        substr(trim($src[2]), -strlen('.less')) == '.less'
                    ) {
                        if (strpos('http', $src[2]) === 0 || strpos('//', $src[2]) === 0) {
                            // do not minify css with an external url
                            $this->registeredScripts[$section]['cssexternal'][] = $src[2];
                        } elseif (strpos($this->minifyx->getOption('cacheFolder'), $src[2]) === 0) {
                            // do not minify css in the MinifyX cache folder (added by the MinifyX script)
                            $this->registeredScripts[$section]['cssnomin'][] = $src[2];
                        } else {
                            // minify css
                            $this->registeredScripts[$section]['cssmin'][] = $src[2];
                        }
                    } else {
                        // do not minify any other file
                        $this->registeredScripts[$section]['untouched'][] = $scriptSrc;
                    }
                } else {
                    // if there is no filename referenced in the registered line leave it alone
                    $this->registeredScripts[$section]['untouched'][] = $scriptSrc;
                }
            }
        }
        foreach ($this->registeredScripts[$section] as &$scriptSection) {
            $scriptSection = array_unique($scriptSection);
        }
    }

    /**
     * @param array $scripts
     * @param string $template
     * @return string
     */
    private function registerBlock($scripts, $template)
    {
        $block = [];
        foreach ($scripts as $script) {
            /** @var modChunk $chunk */
            $chunk = $this->modx->newObject('modChunk', array('name' => 'block' . uniqid()));
            $chunk->setCacheable(false);
            $block[] = $chunk->process([
                'script' => $script,
            ], $template);
            break;
        }
        return implode("\r\n", $block);
    }

    /**
     * @param array $scripts
     * @param string $template
     * @return string
     */
    private function registerMinBlock($scripts, $template)
    {
        if ($scripts) {
            try {
                $collection = new AssetCollection();
                foreach ($scripts as $file) {
                    $file = MODX_BASE_PATH . ltrim($file, '/');
                    $extension = pathinfo($file, PATHINFO_EXTENSION);
                    switch ($extension) {
                        case 'js':
                        case 'css':
                            $collection->add(new FileAsset($file));
                            break;
                        case 'coffee':
                            $collection->add(new FileAsset($file, array(new CoffeeScriptFilter())));
                            break;
                        case 'scss':
                            $collection->add(new FileAsset($file, array(new ScssphpFilter())));
                            break;
                        case 'less':
                            $collection->add(new FileAsset($file, array(new LessphpFilter())));
                            break;
                    }
                }

                    if ($type === 'js') {
                        return $collection->dump(new JavaScriptMinifierFilter());
                    } elseif ($type === 'css') {
                        return $collection->dump(new StylesheetMinifyFilter());
                    }

            } catch (Exception $e) {
                $this->modx->log(xPDO::LOG_LEVEL_ERROR, $e->getMessage());
                return '';
            }
            /** @var modChunk $chunk */
            $chunk = $this->modx->newObject('modChunk', array('name' => 'block' . uniqid()));
            $chunk->setCacheable(false);
            return $chunk->process([
                'scripts' => implode(',', $scripts),
            ], $template);
        } else {
            return '';
        }
    }

    /**
     * @param string $code
     */
    private function processImages($code)
    {
        if (!$this->modx->getService('minifyx', 'MinifyX', MODX_CORE_PATH . 'components/minifyx/model/minifyx/')) {
            return false;
        }

        $connector = $this->modx->getOption('minifyx.connector', null, '/assets/components/minifyx/munee.php', true);
        $exclude = $this->modx->getOption('minifyx.exclude_images');
        $replace = ['from' => [], 'to' => []];
        $site_url = $this->modx->getOption('site_url');
        $default = $this->modx->getOption('minifyx.images_filters', null, '', true);

        preg_match_all('/<img.*?>/i', $code, $tags);
        foreach ($tags[0] as $tag) {
            if (preg_match($exclude, $tag)) {
                continue;
            } elseif (preg_match_all('/(src|height|width|filters)=[\'|"](.*?)[\'|"]/i', $tag, $properties)) {
                if (count($properties[0]) >= 2) {
                    $file = $connector . '?files=';
                    $resize = '';
                    $filters = '';
                    $tmp = ['from' => [], 'to' => []];

                    foreach ($properties[1] as $k => $v) {
                        if ($v == 'src') {
                            $src = $properties[2][$k];
                            if (strpos($src, '://') !== false) {
                                if (strpos($src, $site_url) !== false) {
                                    $src = str_replace($site_url, '', $src);
                                } else {
                                    // Image from 3rd party domain
                                    continue;
                                }
                            }
                            $file .= $src;
                            $tmp['from']['src'] = $properties[2][$k];
                        } elseif ($v == 'height' || $v == 'width') {
                            $resize .= $v[0] . '[' . $properties[2][$k] . ']';
                        } elseif ($v == 'filters') {
                            $filters .= $properties[2][$k];
                            $tmp['from']['filters'] = $properties[0][$k];
                            $tmp['to']['filters'] = '';
                        }
                    }

                    if (!empty($tmp['from']['src'])) {
                        $resize .= isset($tmp['from']['filters'])
                            ? $filters
                            : $default;
                        $tmp['to']['src'] = $file . '?resize=' . $resize;

                        ksort($tmp['from']);
                        ksort($tmp['to']);

                        $replace['from'][] = $tag;
                        $replace['to'][] = str_replace($tmp['from'], $tmp['to'], $tag);
                    }
                }
            }
        }

        if (!empty($replace)) {
            $code = str_replace(
                $replace['from'],
                $replace['to'],
                $code
            );
        }
        return $code;
    }

    /**
     * @param string $code
     * @return string
     */
    private function processHtml($code)
    {
        return TinyMinify::html($code);
    }
}
