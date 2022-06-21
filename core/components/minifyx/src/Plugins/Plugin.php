<?php
/**
 * Abstract plugin
 *
 * @package minifyx
 * @subpackage plugin
 */

namespace TreehillStudio\MinifyX\Plugins;

use modX;
use MinifyX;

/**
 * Class Plugin
 */
abstract class Plugin
{
    /** @var modX $modx */
    protected modX $modx;
    /** @var MinifyX $minifyx */
    protected $minifyx;
    /** @var array $scriptProperties */
    protected array $scriptProperties;

    /**
     * Plugin constructor.
     *
     * @param $modx
     * @param $scriptProperties
     */
    public function __construct($modx, &$scriptProperties)
    {
        $this->scriptProperties = &$scriptProperties;
        $this->modx =& $modx;
        $corePath = $this->modx->getOption('minifyx.core_path', null, $this->modx->getOption('core_path') . 'components/minifyx/');
        $this->minifyx = $this->modx->getService('minifyx', 'MinifyX', $corePath . 'model/minifyx/', [
            'core_path' => $corePath
        ]);
    }

    /**
     * Run the plugin event.
     */
    public function run()
    {
        $init = $this->init();
        if ($init !== true) {
            return;
        }

        $this->process();
    }

    /**
     * Initialize the plugin event.
     *
     * @return bool
     */
    public function init(): bool
    {
        // Get selection range and selection type system settings
        $tplOnly = (bool)$this->minifyx->getOption('tpl_only', [], true);
        $selectionType = $this->minifyx->getOption('selection_type', [], 'exclude');
        $selectionRange = $this->minifyx->getOption('selection_range');

        // Stop plugin on selection range and selection type
        $selectionRange = ($selectionRange) ? array_map('trim', explode(',', $selectionRange)) : [];
        $minifyxFound = in_array((isset($this->modx->resource)) ? $this->modx->resource->get('id') : 0, $selectionRange);
        if (($minifyxFound && ($selectionType == 'exclude')) || (!$minifyxFound && ($selectionType == 'include')) || ($tplOnly && $this->modx->resource && ($this->modx->resource->get('template') == 0))) {
            return false;
        }

        return true;
    }

    /**
     * Process the plugin event code.
     *
     * @return mixed
     */
    abstract public function process();
}