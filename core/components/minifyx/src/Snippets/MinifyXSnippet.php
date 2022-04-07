<?php
/**
 * MinifyX Snippet
 *
 * @package agenda
 * @subpackage snippet
 */

namespace TreehillStudio\MinifyX\Snippets;

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
    public function getDefaultProperties()
    {
        $test = $this->minifyx->getOption('cacheFolder');
        return [
            'jsSources::explodeSeparated' => '',
            'cssSources::explodeSeparated' => '',
            'minifyJs::bool' => false,
            'minifyCss::bool' => false,
            'jsFilename' => 'scripts',
            'cssFilename' => 'styles',
            'cacheFolder' => $this->minifyx->getOption('cacheFolder'),
            'cacheUrl' => $this->minifyx->getOption('cacheUrl'),
            'registerJs::registerJs' => 'placeholder',
            'jsPlaceholder' => 'MinifyX.javascript',
            'registerCss::registerCss' => 'placeholder',
            'cssPlaceholder' => 'MinifyX.css',
            'jsGroups::explodeSeparated' => '',
            'cssGroups::explodeSeparated' => '',
            'preHooks::explodeSeparated' => '',
            'hooks::explodeSeparated' => '',
            'cssTpl' => '<link rel="stylesheet" href="[[+file]]" type="text/css" />',
            'jsTpl' => '<script src="[[+file]]"></script>',
            'forceUpdate::bool' => false
        ];
    }

    /**
     * @param $value
     * @return string
     */
    protected function getRegisterJs($value)
    {
        return (in_array($value, ['placeholder', 'startup', 'default', 'print'])) ? $value : 'placeholder';
    }

    /**
     * @param $value
     * @return string
     */
    protected function getRegisterCss($value)
    {
        return (in_array($value, ['placeholder', 'default', 'print'])) ? $value : 'placeholder';
    }

    /**
     * @param $value
     * @return array|null
     */
    protected function getAssociativeJson($value)
    {
        return json_decode($value, true);
    }

    /**
     * Execute the snippet and return the result.
     *
     * @return string
     * @throws /Exception
     */
    public function execute()
    {
        $this->minifyx->reset($this->properties);

        $sources = $this->minifyx->prepareSources();
        foreach ($sources as $type => $value) {
            if (empty($value)) {
                continue;
            }

            $register = $this->minifyx->getOption('register' . ucfirst($type));
            $placeholder = !empty($this->minifyx->getOption($type . 'Placeholder')) ? $this->minifyx->getOption($type . 'Placeholder') : '';
            $files = $this->minifyx->prepareFiles($value, $type);
            $result = $this->minifyx->getAssetCollection($type, $files, $this->minifyx->getOption('minify' . ucfirst($type)));

            // Register file on frontend
            if ($this->minifyx->saveFile($result) && $this->modx->context->key != 'mgr') {
                $link = $this->minifyx->getFileUrl() . (!empty($this->minifyx->getOption('version')) ? $this->minifyx->getVersion() : '');
                $tag = str_replace('[[+file]]', $link, $this->minifyx->getOption($type . 'Tpl'));
                switch ($register) {
                    case 'placeholder':
                        if ($placeholder) {
                            $this->modx->setPlaceholder($placeholder, $tag);
                        }
                        break;
                    case 'print':
                        return $tag;
                    case 'startup':
                        if ($type == 'js') {
                            $this->modx->regClientStartupScript($tag);
                        }
                        break;
                    default:
                        if ($type == 'css') {
                            $this->modx->regClientCSS($tag);
                        } else {
                            $this->modx->regClientScript($tag);
                        }
                }
            }
        }
        return ($this->modx->context->key == 'mgr') ? $this->minifyx->getFileUrl() : '';
    }
}
