<?php
/**
 * @package minifyx
 * @subpackage plugin
 */

namespace TreehillStudio\MinifyX\Plugins\Events;

use TreehillStudio\MinifyX\Plugins\Plugin;
use xPDO;

class OnSiteRefresh extends Plugin
{
    public function process()
    {
        if (!$this->minifyx->prepareCacheFolder()) {
            $this->modx->log(xPDO::LOG_LEVEL_ERROR, 'Could not create cache dir "' . $this->minifyx->getOption('cachePath') . '"', '', $this->minifyx->packageName);
        }
        $this->minifyx->clearAssetFiles();
        $this->modx->log(xPDO::LOG_LEVEL_INFO, $this->modx->lexicon('minifyx.refresh_cache', [
            'packagename' => $this->minifyx->packageName
        ]));
    }
}
