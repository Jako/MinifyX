<?php
/**
 * MinifyX Snippet
 *
 * @package agenda
 * @subpackage snippet
 */

namespace TreehillStudio\MinifyX\Snippets;

use Exception;
use xPDO;

/**
 * Class MinifyXSnippet
 */
class MinifyXSnippet extends Snippet
{
    /**
     * Get default snippet properties.
     *
     * @return array
     */
    public function getDefaultProperties(): array
    {
        return [
            'jsSources::explodeSeparated' => '',
            'cssSources::explodeSeparated' => '',
            'minifyJs::bool' => $this->minifyx->getOption('minifyJs'),
            'minifyCss::bool' => $this->minifyx->getOption('minifyCss'),
            'jsFilename' => $this->minifyx->getOption('jsFilename'),
            'cssFilename' => $this->minifyx->getOption('cssFilename'),
            'jsPlaceholder' => 'MinifyX.javascript',
            'cssPlaceholder' => 'MinifyX.css',
            'registerJs::registerJs' => 'placeholder',
            'registerCss::registerCss' => 'placeholder',
            'jsTpl' => $this->minifyx->getOption('jsTpl'),
            'cssTpl' => $this->minifyx->getOption('cssTpl'),
            'forceUpdate::bool' => false
        ];
    }

    /**
     * @param $value
     * @return string
     */
    protected function getRegisterJs($value): string
    {
        return (in_array($value, ['placeholder', 'startup', 'default', 'print'])) ? $value : 'placeholder';
    }

    /**
     * @param $value
     * @return string
     */
    protected function getRegisterCss($value): string
    {
        return (in_array($value, ['placeholder', 'default', 'print'])) ? $value : 'placeholder';
    }

    /**
     * Execute the snippet and return the result.
     *
     * @return string
     * @throws /Exception
     */
    public function execute(): string
    {
        $output = '';

        // Work only on frontend
        if ($this->modx->context->key != 'mgr') {
            if (!$this->minifyx->prepareCachePath()) {
                $this->modx->log(xPDO::LOG_LEVEL_ERROR, 'Could not create cache dir "' . $this->minifyx->getOption('cachePath') . '"', '', $this->minifyx->packageName);
            }

            if ($this->getProperty('forceUpdate')) {
                $this->minifyx->clearAssetFiles();
            }

            $sources = $this->prepareSources();
            foreach ($sources as $type => $value) {
                if (empty($value)) {
                    continue;
                }

                $register = $this->getProperty('register' . ucfirst($type));
                $placeholder = $this->getProperty($type . 'Placeholder', '');

                // Prepare source files
                $externalFiles = [];
                $files = $this->minifyx->prepareFiles($value, $type, $register, $externalFiles);
                if ($register == 'startup' || $register == 'default') {
                    // Register files, the external files are registered first
                    foreach (array_merge($externalFiles, $files) as $file) {
                        if ($type == 'css') {
                            $this->modx->regClientCSS($file);
                        } else {
                            if ($register == 'startup') {
                                $this->modx->regClientStartupScript($file);
                            } else {
                                $this->modx->regClientScript($file);
                            }
                        }
                    }
                } else {
                    // Output the external files before the combined file, the local files could depend on them
                    $tags = [];
                    foreach ($externalFiles as $file) {
                        $tags[] = $this->modx->getChunk($this->getProperty($type . 'Tpl'), [
                            'file' => $file,
                        ]);
                    }
                    if ($url = $this->getCombinedFile($type, $files)) {
                        $tags[] = $this->modx->getChunk($this->getProperty($type . 'Tpl'), [
                            'file' => $url,
                        ]);
                    }
                    $tag = implode("\n", $tags);
                    if ($tag === '') {
                        continue;
                    }
                    switch ($register) {
                        case 'placeholder':
                            if ($placeholder) {
                                $this->modx->setPlaceholder($placeholder, $tag);
                            }
                            break;
                        case 'print':
                            $output .= $tag;
                            break;
                    }
                }
            }
        }

        return $output;
    }

    /**
     * Combine (and optional minify) the local files and get the URL of the combined file.
     *
     * @param string $type
     * @param array $files
     * @return string
     */
    protected function getCombinedFile(string $type, array $files): string
    {
        if (!$files) {
            return '';
        }
        $minify = (bool)$this->getProperty('minify' . ucfirst($type));
        $filename = (string)$this->getProperty($type . 'Filename', '');

        // The cache key contains the modification times of the files, so a changed, added or removed file generates a new combined file
        $webrootPath = $this->minifyx->getWebrootPath();
        $modified = [];
        foreach ($files as $file) {
            $modified[] = @filemtime($webrootPath . ltrim(parse_url($file, PHP_URL_PATH) ?: $file, '/'));
        }
        $cacheKey = 'minifyx_snippet_' . md5(json_encode([$type, $files, $modified, $minify, $filename]));
        $cacheManager = $this->modx->getCacheManager();
        if (!$this->getProperty('forceUpdate')) {
            $cached = $cacheManager->get($cacheKey, $this->minifyx->cacheOptions);
            if (is_array($cached) && !empty($cached['path']) && is_file($cached['path'])) {
                return $cached['url'];
            }
        }

        $result = $this->minifyx->getAssetCollection($type, $files, $minify);
        if ($result === '') {
            $this->modx->log(xPDO::LOG_LEVEL_ERROR, 'No generated content for the ' . $type . ' asset collection: ' . implode(', ', $files), '', $this->minifyx->packageName);
            return '';
        }
        try {
            $url = $this->minifyx->saveAssetFile($result, $type, '_p', $filename, $minify);
        } catch (Exception $e) {
            $this->modx->log(xPDO::LOG_LEVEL_ERROR, $e->getMessage(), '', $this->minifyx->packageName);
            return '';
        }
        // xPDOCacheManager::set expects the value by reference
        $cached = [
            'url' => $url,
            'path' => $this->minifyx->getOption('cachePath') . $type . '/' . basename($url),
        ];
        $cacheManager->set($cacheKey, $cached, 0, $this->minifyx->cacheOptions);
        return $url;
    }

    /**
     * Prepare an array of css and js files.
     *
     * @return array
     */
    public function prepareSources(): array
    {
        $groupsFile = $this->minifyx->getOption('corePath') . 'config/groups.php';
        if (file_exists($groupsFile)) {
            $groups = include $groupsFile;
        }
        $sources = [
            'css' => [],
            'js' => []
        ];
        foreach ($sources as $type => $value) {
            if (isset($groups[$type])) {
                $sources[$type] = array_merge($value, $groups[$type]);
            }
            $sources[$type] = array_merge($sources[$type], $this->getProperty($type . 'Sources'));
            $sources[$type] = array_map([$this, 'parseUrl'], $sources[$type]);
            $sources[$type] = array_unique($sources[$type]);
        }
        return $sources;
    }

    /**
     * @param string $url
     * @return string
     */
    protected function parseUrl(string $url): string
    {
        $url = str_replace(array('[[+', '{', '}'), array('[[++', '[[++', ']]'), $url);
        $this->modx->getParser()->processElementTags('', $url, false, false, '[[', ']]', array(), 1);
        return $url;
    }
}
