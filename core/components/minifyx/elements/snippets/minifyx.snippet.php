<?php
/**
 * MinifyX Snippet
 *
 * @package minifyx
 * @subpackage snippet
 *
 * @var modX $modx
 * @var array $scriptProperties
 */

use TreehillStudio\MinifyX\Snippets\MinifyXSnippet;

$corePath = $modx->getOption('minifyx.core_path', null, $modx->getOption('core_path') . 'components/minifyx/');
/** @var MinifyX $minifyx */
$minifyx = $modx->getService('minifyx', 'MinifyX', $corePath . 'model/minifyx/', [
    'core_path' => $corePath
]);

$snippet = new MinifyXSnippet($modx, $scriptProperties);
if ($snippet instanceof TreehillStudio\MinifyX\Snippets\MinifyXSnippet) {
    return $snippet->execute();
}
return 'TreehillStudio\MinifyX\Snippets\MinifyXSnippet class not found';