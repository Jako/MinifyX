<?php
/**
 * cssToPage Hook
 *
 * @package minifyx
 * @subpackage hook
 */

/** @var MinifyX $this */

if ($this->isCss()) {
    $style = '<style type="text/css">' . $this->getContent() . '</style>';
    $this->modx->regClientCSS($style);
    // Switch off file registration
    $this->setFilename('');
}