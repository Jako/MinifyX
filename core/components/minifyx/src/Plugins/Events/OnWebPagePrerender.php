<?php
/**
 * @package minifyx
 * @subpackage plugin
 */

namespace TreehillStudio\MinifyX\Plugins\Events;

use Assetic\Asset\AssetCollection;
use Assetic\Asset\FileAsset;
use Assetic\Filter\JavaScriptMinifierFilter;
use Assetic\Filter\LessphpFilter;
use Assetic\Filter\ScssphpFilter;
use Assetic\Filter\StylesheetMinifyFilter;
use Exception;
use Minifier\TinyMinify;
use TreehillStudio\MinifyX\Plugins\Plugin;
use xPDO;

class OnWebPagePrerender extends Plugin
{
    /**
     * {@inheritDoc}
     */
    public function process()
    {
        $minify = false;
        $time = microtime(true);

        // Process registered assets
        if ($this->minifyx->getOption('processRegistered', [], false)) {
            $this->modx->resource->_output = $this->processRegistered($this->modx->resource->_output);
            $minify = true;
        }
        // Process images
        // @TODO
        if ($this->minifyx->getOption('processImages', [], false)) {
            //$this->modx->resource->_output = $this->processImages($this->modx->resource->_output);
            //$minify = true;
        }
        // Process HTML Minify the page content
        if ($this->minifyx->getOption('minifyHtml', [], false)) {
            $this->modx->resource->_output = $this->processHtml($this->modx->resource->_output);
            $minify = true;
        }

        if ($this->minifyx->getOption('debug') && $minify) {
            $this->modx->log(xPDO::LOG_LEVEL_INFO, 'Total time for page "' . $this->modx->resource->id . '" = ' . (microtime(true) - $time), '', $this->minifyx->packageName);
        }
    }

    /**
     * Process the registered assets
     *
     * @param string $code
     * @return string
     */
    private function processRegistered(string $code): string
    {
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
        $cacheManager = $this->modx->getCacheManager();
        $registeredScripts = $cacheManager->get('minifyx_' . md5($clientStartupScripts . $clientScripts), $this->minifyx->cacheOptions);
        if (empty($registeredScripts)) {

            // Collect the registered scripts
            $registeredHeadScripts = $this->collectRegistered(($clientStartupScripts) ? explode("\n", $clientStartupScripts) : []);
            $registeredBodyScripts = $this->collectRegistered(($clientScripts) ? explode("\n", $clientScripts) : []);
            $registeredScripts = $this->prepareRegistered($registeredHeadScripts, $registeredBodyScripts);
            $cacheManager->set('minifyx_' . md5($clientStartupScripts . $clientScripts), $registeredScripts, 0, $this->minifyx->cacheOptions);
        }

        // Insert minified scripts
        if ($registeredScripts['head']) {
            $code = str_replace('</head>', implode("\r\n", $registeredScripts['head']) . '</head>', $code);
        }
        if ($registeredScripts['body']) {
            $code = str_replace('</body>', implode("\r\n", $registeredScripts['body']) . '</body>', $code);
        }
        return $code;
    }

    /**
     * Prepare the output of the registered blocks
     *
     * @param array $registeredHeadScripts
     * @param array $registeredBodyScripts
     * @return array|array[]
     */
    private function prepareRegistered(array $registeredHeadScripts, array $registeredBodyScripts): array
    {
        $registeredScripts = [
            'head' => [],
            'body' => []
        ];
        $registeredScripts['head'][] = $this->registerBlock($registeredHeadScripts['cssexternal'], $this->minifyx->getOption('cssTpl'));
        $registeredScripts['head'][] = $this->registerMinBlock($registeredHeadScripts['cssmin'], '_h', $this->minifyx->getOption('cssTpl'));
        $registeredScripts['head'][] = $this->registerBlock($registeredHeadScripts['jsexternal'], $this->minifyx->getOption('jsTpl'));
        $registeredScripts['head'][] = $this->registerMinBlock($registeredHeadScripts['jsmin'], '_h', $this->minifyx->getOption('jsTpl'));
        $registeredScripts['head'][] = $this->registerMinBlock($registeredHeadScripts['jsnomin'], '_h', $this->minifyx->getOption('jsTpl'));
        $registeredScripts['head'][] = $this->registerBlock($registeredHeadScripts['untouched'], '[[+file]]');
        $registeredScripts['body'][] = $this->registerBlock($registeredBodyScripts['jsexternal'], $this->minifyx->getOption('jsTpl'));
        $registeredScripts['body'][] = $this->registerMinBlock($registeredBodyScripts['jsmin'], '_b', $this->minifyx->getOption('jsTpl'));
        $registeredScripts['body'][] = $this->registerMinBlock($registeredBodyScripts['jsnomin'], '_b', $this->minifyx->getOption('jsTpl'));
        $registeredScripts['body'][] = $this->registerBlock($registeredBodyScripts['untouched'], '[[+file]]');

        $registeredScripts['head'] = array_filter($registeredScripts['head']);
        $registeredScripts['body'] = array_filter($registeredScripts['body']);
        return $registeredScripts;
    }

    /**
     * Collect and sort the registered asssets into sections
     *
     * @param array $scripts
     * @return array
     */
    private function collectRegistered(array $scripts): array
    {
        $conditional = false;
        $registeredScripts = [
            'cssexternal' => [],
            'cssmin' => [],
            'jsexternal' => [],
            'jsmin' => [],
            'jsnomin' => [],
            'untouched' => [],
        ];
        foreach ($scripts as $scriptSrc) {
            if (preg_match('/<!--\[if /', trim($scriptSrc), $tag) || $conditional) {
                // don't touch conditional css/scripts
                $registeredScripts['untouched'][] = $scriptSrc;
                $conditional = true;
                if (preg_match('/endif]-->/', trim($scriptSrc), $tag)) {
                    $conditional = false;
                }
            } else {
                preg_match('/^<(script|link)[^>]+>/', trim($scriptSrc), $tag);
                if ($tag && preg_match('/(src|href)=\"(.*?)(\?v=.*?)?"/', $tag[0], $src)) {
                    // if there is a filename referenced in the registered line
                    if (
                        substr(trim($src[2]), -strlen('.js')) == '.js' ||
                        substr(trim($src[2]), -strlen('.coffee')) == '.coffee'
                    ) {
                        // the registered chunk is a separate javascript
                        if (strpos($src[2], 'http') === 0 || strpos($src[2], '//') === 0) {
                            // do not minify scripts with an external url
                            $registeredScripts['jsexternal'][] = $src[2];
                        } elseif (!empty($this->minifyx->getOption('excludeRegistered')) && preg_match($this->minifyx->getOption('excludeRegistered'), $src[2])) {
                            // do not minify scripts matched with excludeRegistered
                            $registeredScripts['jsnomin'][] = $src[2];
                        } elseif (strpos($src[2], $this->minifyx->getOption('cachePath')) === 0) {
                            // do not minify scripts in the MinifyX cache folder (i.e. added by the MinifyX script)
                            $registeredScripts['jsnomin'][] = $src[2];
                        } else {
                            // minify scripts
                            $registeredScripts['jsmin'][] = $src[2];
                        }
                    } elseif (
                        substr(trim($src[2]), -strlen('.css')) == '.css' ||
                        substr(trim($src[2]), -strlen('.scss')) == '.scss' ||
                        substr(trim($src[2]), -strlen('.less')) == '.less'
                    ) {
                        if (strpos($src[2], 'http') === 0 || strpos($src[2], '//') === 0) {
                            // do not minify css with an external url
                            $registeredScripts['cssexternal'][] = $src[2];
                        } elseif (strpos($src[2], $this->minifyx->getOption('cachePath')) === 0) {
                            // do not minify css in the MinifyX cache folder (added by the MinifyX script)
                            $registeredScripts['cssnomin'][] = $src[2];
                        } else {
                            // minify css
                            $registeredScripts['cssmin'][] = $src[2];
                        }
                    } else {
                        // do not minify any other file
                        $registeredScripts['untouched'][] = $scriptSrc;
                    }
                } else {
                    // if there is no filename referenced in the registered line leave it alone
                    $registeredScripts['untouched'][] = $scriptSrc;
                }
            }
        }
        foreach ($registeredScripts as &$scriptSection) {
            $scriptSection = array_unique($scriptSection);
        }
        return $registeredScripts;
    }

    /**
     * Register not handled assets
     *
     * @param array $scripts
     * @param string $template
     * @return string
     */
    private function registerBlock(array $scripts, string $template): string
    {
        $block = [];
        foreach ($scripts as $script) {
            $block[] = $this->modx->getChunk($template, [
                'file' => $script,
            ]);
            break;
        }
        return implode("\r\n", $block);
    }

    /**
     * Register combined (and optional minify) assets
     *
     * @param array $scripts
     * @param string $prefix
     * @param string $template
     * @return string
     */
    private function registerMinBlock(array $scripts, string $prefix, string $template): string
    {
        if ($scripts) {
            try {
                $assetCollection = new AssetCollection();

                // Collect the assets ...
                $webrootPath = substr($this->modx->getOption('base_path'), 0, -strlen($this->modx->getOption('base_url'))) . '/';
                $type = 'css';
                foreach ($scripts as $file) {
                    $file = $webrootPath . ltrim($file, '/');
                    $extension = pathinfo($file, PATHINFO_EXTENSION);
                    switch ($extension) {
                        case 'js':
                            $assetCollection->add(new FileAsset($file));
                            $type = 'js';
                            break;
                        case 'css':
                            $assetCollection->add(new FileAsset($file));
                            break;
                        case 'scss':
                            $assetCollection->add(new FileAsset($file, array(new ScssphpFilter())));
                            break;
                        case 'less':
                            $assetCollection->add(new FileAsset($file, array(new LessphpFilter())));
                            break;
                    }
                }

                // ... and combine them in one file
                if ($this->minifyx->getOption('minify' . ucfirst($type))) {
                    if ($type == 'js') {
                        $content = $assetCollection->dump(new JavaScriptMinifierFilter());
                    } else {
                        $content = $assetCollection->dump(new StylesheetMinifyFilter());
                    }
                } else {
                    $content = $assetCollection->dump();
                }
                if ($content) {
                    $file = $this->minifyx->saveAssetFile($content, $type, $prefix);
                    return $this->modx->getChunk($template, [
                        'file' => $file
                    ]);
                }
            } catch (Exception $e) {
                $this->modx->log(xPDO::LOG_LEVEL_ERROR, $e->getMessage());
            }
        }
        return '';
    }

    /**
     * Minify images
     *
     * @param string $code
     * @return string
     *
     * @TODO switch from Munee to Assetic
     */
    private function processImages(string $code)
    {
        if (!$this->modx->getService('minifyx', 'MinifyX', MODX_CORE_PATH . 'components/minifyx/model/minifyx/')) {
            return false;
        }

        $connector = $this->modx->getOption('minifyx.connector', null, '/assets/components/minifyx/munee.php', true);
        $exclude = $this->modx->getOption('minifyx.excludeImages');
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
    private function processHtml(string $code): string
    {
        return TinyMinify::html($code);
    }
}
