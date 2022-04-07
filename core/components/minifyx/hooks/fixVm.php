<?php
/**
 * FixVm Hook
 *
 * @package minifyx
 * @subpackage hook
 */

/** @var MinifyX $this */

if ($this->isCss()) {
    $data = preg_replace('#vm (ax|in)#', 'vm$1', $this->getContent());
    $this->setContent($data);
}