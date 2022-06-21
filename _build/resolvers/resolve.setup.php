<?php
/**
 * Resolves setup-options settings
 *
 * @var xPDOObject $object
 * @var array $options
 */

if ($object->xpdo) {
	/** @var modX $modx */
	$modx =& $object->xpdo;

	$success = false;
	switch ($options[xPDOTransport::PACKAGE_ACTION]) {
		case xPDOTransport::ACTION_INSTALL:
		case xPDOTransport::ACTION_UPGRADE:
            $file = MODX_CORE_PATH . 'components/minifyx/config/groups.php';
            if (!file_exists($file)) {
                if (!file_exists(MODX_CORE_PATH . 'components/minifyx/config')) {
                    mkdir(MODX_CORE_PATH . 'components/minifyx/config');
                }
                $content = <<<content
<?php
/**
 * Demo groups file
 *
 * @package minifyx
 * @subpackage groupfile
 */

return array(
    /*'css' => [
      '[[+assets_url]]style1.css',
      '{assets_url}style2.css'
    ],
    'js' => [
        ...
    ]*/
);
content;

                if (!file_put_contents($file, $content)) {
                    $this->modx->log(xPDO::LOG_LEVEL_ERROR, 'Could not create file {core_path}components/minifyx/config/groups.php');
                    return false;
                }
            }
			$success = true;
			break;

		case xPDOTransport::ACTION_UNINSTALL:
			$success = true;
			break;
	}

	return $success;
}
