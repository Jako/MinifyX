<?php
/**
 * @package minifyx
 * @subpackage plugin
 */

namespace TreehillStudio\MinifyX\Plugins\Events;

use Exception;
use Minifier\TinyMinify;
use modContentType;
use TreehillStudio\MinifyX\Plugins\Plugin;
use xPDO;

class OnWebPagePrerender extends Plugin
{
    /**
     * Set to true, if a combined asset file could not be generated. The result is not cached then.
     * @var bool $failed
     */
    private bool $failed = false;

    /**
     * {@inheritDoc}
     */
    public function init(): bool
    {
        if (!parent::init()) {
            return false;
        }

        // Process only HTML output
        if (!$this->modx->resource) {
            return false;
        }
        /** @var modContentType $contentType */
        $contentType = $this->modx->resource->getOne('ContentType');
        return $contentType && !$contentType->get('binary') && strpos($contentType->get('mime_type'), 'text/html') !== false;
    }

    /**
     * {@inheritDoc}
     */
    public function process()
    {
        $minify = false;
        $time = microtime(true);

        if (!$this->minifyx->prepareCachePath()) {
            $this->modx->log(xPDO::LOG_LEVEL_ERROR, 'Could not create cache dir "' . $this->minifyx->getOption('cachePath') . '"', '', $this->minifyx->packageName);
        }

        // Process registered assets
        if ($this->minifyx->getOption('processRegistered', [], false)) {
            $this->modx->resource->_output = $this->processRegistered($this->modx->resource->_output);
            $minify = true;
        }
        // Process images
        // @TODO Implement this
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
        $clientStartupScripts = $this->modx->sjscripts;
        $implodedStartupScripts = implode("\n", $clientStartupScripts);
        $clientScripts = $this->modx->jscripts;
        $implodedScripts = implode("\n", $clientScripts);

        if ($this->minifyx->getOption('debug')) {
            $this->modx->log(xPDO::LOG_LEVEL_ERROR, 'Registered startup scripts:' . print_r($clientStartupScripts, true));
            $this->modx->log(xPDO::LOG_LEVEL_ERROR, 'Registered scripts:' . print_r($clientScripts, true));
        }

        // Remove inserted registered scripts in the current code
        if ($clientStartupScripts) {
            $code = str_replace($implodedStartupScripts . "\n", '', $code);
        }
        if ($clientScripts) {
            $code = str_replace($implodedScripts . "\n", '', $code);
        }

        // Any cached minified scripts?
        $cacheManager = $this->modx->getCacheManager();
        $cacheKey = 'minifyx_' . md5($implodedStartupScripts . $implodedScripts);
        $registeredScripts = $cacheManager->get($cacheKey, $this->minifyx->cacheOptions);
        if (empty($registeredScripts)) {

            // Collect the registered scripts
            $registeredHeadScripts = $this->collectRegistered(($clientStartupScripts) ?: []);
            $registeredBodyScripts = $this->collectRegistered(($clientScripts) ?: []);
            $registeredScripts = [
                'head' => $this->prepareRegistered($registeredHeadScripts, '_h'),
                'body' => $this->prepareRegistered($registeredBodyScripts, '_b'),
            ];
            // Don't cache an incomplete result, the combined files are generated again on the next request
            if (!$this->failed) {
                $cacheManager->set($cacheKey, $registeredScripts, 0, $this->minifyx->cacheOptions);
            }

            if ($this->minifyx->getOption('debug')) {
                $this->modx->log(xPDO::LOG_LEVEL_ERROR, 'Collected startup scripts:' . print_r($registeredHeadScripts, true));
                $this->modx->log(xPDO::LOG_LEVEL_ERROR, 'Collected scripts:' . print_r($registeredBodyScripts, true));
                $this->modx->log(xPDO::LOG_LEVEL_ERROR, 'Prepared registered scripts:' . print_r($registeredScripts, true));
            }
        }

        // Insert minified scripts
        if ($registeredScripts['head']) {
            $code = $this->insertBefore($code, '</head>', implode("\r\n", $registeredScripts['head']));
        }
        if ($registeredScripts['body']) {
            $code = $this->insertBefore($code, '</body>', implode("\r\n", $registeredScripts['body']));
        }
        return $code;
    }

    /**
     * Insert a string before the first occurrence of a closing tag, like MODX inserts the registered scripts
     *
     * @param string $code
     * @param string $tag
     * @param string $insert
     * @return string
     */
    private function insertBefore(string $code, string $tag, string $insert): string
    {
        $pos = stripos($code, $tag);
        return ($pos !== false) ? substr_replace($code, $insert, $pos, 0) : $code;
    }

    /**
     * Prepare the output of the collected asset segments
     *
     * @param array $segments
     * @param string $prefix
     * @return array
     */
    private function prepareRegistered(array $segments, string $prefix): array
    {
        $output = [];
        foreach ($segments as $segment) {
            switch ($segment['kind']) {
                case 'cssmin':
                    $output[] = $this->registerMinBlock($segment['items'], 'css', $prefix, $this->minifyx->getOption('cssTpl'));
                    break;
                case 'jsmin':
                    $output[] = $this->registerMinBlock($segment['items'], 'js', $prefix, $this->minifyx->getOption('jsTpl'));
                    break;
                case 'cssexternal':
                case 'cssnomin':
                    $output[] = $this->registerBlock($segment['items'], $this->minifyx->getOption('cssTpl'));
                    break;
                case 'jsexternal':
                case 'jsnomin':
                    $output[] = $this->registerBlock($segment['items'], $this->minifyx->getOption('jsTpl'));
                    break;
                default:
                    $output[] = $this->registerBlock($segment['items']);
            }
        }
        return array_values(array_filter($output));
    }

    /**
     * Collect the registered assets into ordered segments.
     *
     * Stylesheets are output before all other assets. The order of the
     * stylesheets and the order of the other assets are preserved, only
     * consecutive local assets of the same kind are combined.
     *
     * @param array $scripts
     * @return array
     */
    private function collectRegistered(array $scripts): array
    {
        $conditional = false;
        $streams = [
            'css' => [],
            'other' => [],
        ];
        $seen = [];
        $exclude = $this->minifyx->getOption('excludeRegistered');
        foreach ($scripts as $scriptSrc) {
            $stream = 'other';
            if (preg_match('/<!--\[if /', trim($scriptSrc)) || $conditional) {
                // don't touch conditional css/scripts
                $kind = 'untouched';
                $item = $scriptSrc;
                $conditional = !preg_match('/endif]-->/', trim($scriptSrc));
            } elseif (preg_match('/^<(script|link)[^>]+>/', trim($scriptSrc), $tag) &&
                preg_match('/(src|href)=\"(.*?)(\?v=.*?)?"/', $tag[0], $src)
            ) {
                // if there is a filename referenced in the registered line
                $file = trim($src[2]);
                $extension = strtolower(pathinfo(parse_url($file, PHP_URL_PATH) ?: '', PATHINFO_EXTENSION));
                $item = $file;
                if ($extension === 'js') {
                    $kind = $this->getAssetKind($file, $exclude, 'js');
                } elseif (in_array($extension, ['css', 'scss', 'less'])) {
                    $kind = $this->getAssetKind($file, $exclude, 'css');
                    $stream = 'css';
                } else {
                    // do not touch any other file
                    $kind = 'untouched';
                    $item = $scriptSrc;
                }
            } else {
                // if there is no filename referenced in the registered line leave it alone
                $kind = 'untouched';
                $item = $scriptSrc;
            }

            // Skip duplicate asset files, untouched lines (i.e. conditional comments) could be repeated
            if ($kind !== 'untouched') {
                if (isset($seen[$item])) {
                    continue;
                }
                $seen[$item] = true;
            }

            // Combine consecutive assets of the same kind
            $last = count($streams[$stream]) - 1;
            if ($last >= 0 && $streams[$stream][$last]['kind'] === $kind) {
                $streams[$stream][$last]['items'][] = $item;
            } else {
                $streams[$stream][] = [
                    'kind' => $kind,
                    'items' => [$item],
                ];
            }
        }
        return array_merge($streams['css'], $streams['other']);
    }

    /**
     * Get the kind of a referenced asset file
     *
     * @param string $file
     * @param string|null $exclude
     * @param string $type
     * @return string
     */
    private function getAssetKind(string $file, ?string $exclude, string $type): string
    {
        if (strpos($file, 'http') === 0 || strpos($file, '//') === 0) {
            // do not combine assets with an external url
            return $type . 'external';
        } elseif (!empty($exclude) && preg_match($exclude, $file)) {
            // do not combine assets matched with excludeRegistered
            return $type . 'nomin';
        } elseif ($this->minifyx->isCacheUrl($file)) {
            // do not combine assets in the MinifyX cache folder (i.e. added by the MinifyX snippet)
            return $type . 'nomin';
        }
        return $type . 'min';
    }

    /**
     * Register not handled assets
     *
     * @param array $scripts
     * @param string $template
     * @return string
     */
    private function registerBlock(array $scripts, string $template = ''): string
    {
        $block = [];
        foreach ($scripts as $script) {
            if ($template) {
                $block[] = $this->modx->getChunk($template, [
                    'file' => $script,
                ]);
            } else {
                $block[] = $script;
            }
        }
        return implode("\r\n", $block);
    }

    /**
     * Register combined (and optional minify) assets
     *
     * If the combined file can't be generated, the assets are registered
     * separately, so that they are not missing on the page.
     *
     * @param array $scripts
     * @param string $type
     * @param string $prefix
     * @param string $template
     * @return string
     */
    private function registerMinBlock(array $scripts, string $type, string $prefix, string $template): string
    {
        if (!$scripts) {
            return '';
        }

        $content = $this->minifyx->getAssetCollection($type, $scripts, $this->minifyx->getOption('minify' . ucfirst($type)));
        if ($this->minifyx->getOption('debug')) {
            $this->modx->log(xPDO::LOG_LEVEL_ERROR, 'Asset collection ' . $type . ' generated. Content length: ' . strlen($content));
        }

        if ($content !== '') {
            try {
                $file = $this->minifyx->saveAssetFile($content, $type, $prefix);
                return $this->modx->getChunk($template, [
                    'file' => $file
                ]);
            } catch (Exception $e) {
                $this->modx->log(xPDO::LOG_LEVEL_ERROR, $e->getMessage(), '', $this->minifyx->packageName);
            }
        } else {
            $this->modx->log(xPDO::LOG_LEVEL_ERROR, 'No generated content for the ' . $type . ' asset collection: ' . implode(', ', $scripts), '', $this->minifyx->packageName);
        }

        $this->failed = true;
        return $this->registerBlock($scripts, $template);
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
