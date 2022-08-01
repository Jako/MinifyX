<?php
/**
 * MinifyX Snippet
 *
 * @package agenda
 * @subpackage snippet
 */

namespace TreehillStudio\MinifyX\Snippets;

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
            'minifyJs::bool' => false,
            'minifyCss::bool' => false,
            'jsFilename' => $this->minifyx->getOption('jsFilename'),
            'cssFilename' => $this->minifyx->getOption('cssFilename'),
            'cacheFolder' => $this->minifyx->getOption('cacheFolder'),
            'cacheUrl' => $this->minifyx->getOption('cacheUrl'),
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
        // Work only on frontend
        if ($this->modx->context->key != 'mgr') {
            if (!$this->minifyx->prepareCacheFolder()) {
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
                $placeholder = !empty($this->minifyx->getOption($type . 'Placeholder')) ? $this->minifyx->getOption($type . 'Placeholder') : '';

                // Prepare source files
                $files = $this->minifyx->prepareFiles($value, $type, $register);
                if ($register == 'startup' || $register == 'default') {
                    // Register files
                    foreach ($files as $file) {
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
                    // Get combined asset collection
                    $result = $this->minifyx->getAssetCollection($type, $files, $this->minifyx->getOption('minify' . ucfirst($type)));
                    $url = $this->minifyx->saveAssetFile($result, $type, '_p');
                    $tag = $this->modx->getChunk($this->minifyx->getOption($type . 'Tpl'), [
                        'file' => $url,
                    ]);
                    switch ($register) {
                        case 'placeholder':
                            if ($placeholder) {
                                $this->modx->setPlaceholder($placeholder, $tag);
                            }
                            break;
                        case 'print':
                            return $tag;
                    }
                }
            }
        }

        return '';
    }

    /**
     * Prepare an array of css and js files.
     *
     * @return array
     */
    public function prepareSources(): array
    {
        if (file_exists(MODX_CORE_PATH . 'components/minifyx/config/groups.php')) {
            $groups = include MODX_CORE_PATH . 'components/minifyx/config/groups.php';
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
