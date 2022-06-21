<?php
/**
 * MinifyX Plugin
 *
 * @package minifyx
 * @subpackage plugin
 *
 * @var modX $modx
 * @var array $scriptProperties
 */

$className = 'TreehillStudio\MinifyX\Plugins\Events\\' . $modx->event->name;

$corePath = $modx->getOption('minifyx.core_path', null, $modx->getOption('core_path') . 'components/minifyx/');
/** @var MinifyX $minifyx */
$minifyx = $modx->getService('minifyx', 'MinifyX', $corePath . 'model/minifyx/', [
    'core_path' => $corePath
]);

if ($minifyx) {
    if (class_exists($className)) {
        $handler = new $className($modx, $scriptProperties);
        if (get_class($handler) == $className) {
            $handler->run();
        } else {
            $modx->log(xPDO::LOG_LEVEL_ERROR, $className. ' could not be initialized!', '', 'MinifyX Plugin');
        }
    } else {
        $modx->log(xPDO::LOG_LEVEL_ERROR, $className. ' was not found!', '', 'MinifyX Plugin');
    }
}

return;