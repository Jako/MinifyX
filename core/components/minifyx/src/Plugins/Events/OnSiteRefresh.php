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
        if ($this->minifyx->clearCache()) {
            $this->modx->log(xPDO::LOG_LEVEL_INFO, $this->modx->lexicon('refresh_default') . ': MinifyX');
        }
    }
}
