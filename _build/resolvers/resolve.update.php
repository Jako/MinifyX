<?php
/**
 *
 * @package minifyx
 * @subpackage build
 *
 * @var array $options
 * @var xPDOObject $object
 */

if ($object->xpdo) {
    if (!function_exists('addToSetting')) {
        /**
         * @param modX $modx
         * @param string $key
         * @param string $old
         * @param string $new
         */
        function addToSetting($modx, $key, $add, $separator = ' ')
        {
            /** @var modSystemSetting $setting */
            $setting = $modx->getObject('modSystemSetting', [
                'key' => $key
            ]);
            if ($setting && strpos($setting->get('value'), $add) === false) {
                $array = explode($separator, $setting->get('value'));
                $array[] = $add;
                sort($array);
                $setting->set('value', implode(' ', $array));
                $setting->save();
            }
        }
    }

    if (!function_exists('removeFromSetting')) {
        /**
         * @param modX $modx
         * @param string $key
         * @param string $old
         * @param string $new
         */
        function removeFromSetting($modx, $key, $remove, $separator = ' ')
        {
            /** @var modSystemSetting $setting */
            $setting = $modx->getObject('modSystemSetting', [
                'key' => $key
            ]);
            if ($setting && strpos($setting->get('value'), $remove) !== false) {
                $array = explode($separator, $setting->get('value'));
                $array = array_diff($array, [$remove]);
                sort($array);
                $setting->set('value', implode(' ', $array));
                $setting->save();
            }
        }
    }

    if (!function_exists('changeSetting')) {
        /**
         * @param modX $modx
         * @param string $key
         * @param string $old
         * @param string $new
         */
        function changeSetting($modx, $key, $old, $new)
        {
            /** @var modSystemSetting $setting */
            $setting = $modx->getObject('modSystemSetting', [
                'key' => $key
            ]);
            if ($setting) {
                $setting->set('value', str_replace($old, $new, $setting->get('value')));
                $setting->save();
            }
        }
    }

    if (!function_exists('changeSettingKey')) {
        /**
         * @param modX $modx
         * @param string $key
         * @param string $old
         * @param string $new
         */
        function changeSettingKey($modx, $key, $new)
        {
            /** @var modSystemSetting $setting */
            $setting = $modx->getObject('modSystemSetting', [
                'key' => $key
            ]);
            if ($setting) {
                $setting->set('key', $new);
                $setting->save();
            }
        }
    }

    if (!function_exists('changeSettingArea')) {
        /**
         * @param modX $modx
         * @param string $old
         * @param string $new
         */
        function changeSettingArea($modx, $old, $new)
        {
            /** @var modSystemSetting[] $settings */
            $settings = $modx->getIterator('modSystemSetting', [
                'namespace' => 'minifyx',
                'area' => $old
            ]);
            foreach ($settings as $setting) {
                $setting->set('area', $new);
                $setting->save();
            }
        }
    }

    switch ($options[xPDOTransport::PACKAGE_ACTION]) {
        case xPDOTransport::ACTION_INSTALL:
        case xPDOTransport::ACTION_UPGRADE:
            /** @var modX $modx */
            $modx =& $object->xpdo;
            // http://forums.modx.com/thread/88734/package-version-check#dis-post-489104
            $c = $modx->newQuery('transport.modTransportPackage');
            $c->where([
                'workspace' => 1,
                "(SELECT
                        `signature`
                      FROM {$modx->getTableName('transport.modTransportPackage')} AS `latestPackage`
                      WHERE `latestPackage`.`package_name` = `modTransportPackage`.`package_name`
                      ORDER BY
                         `latestPackage`.`version_major` DESC,
                         `latestPackage`.`version_minor` DESC,
                         `latestPackage`.`version_patch` DESC,
                         IF(`release` = '' OR `release` = 'ga' OR `release` = 'pl','z',`release`) DESC,
                         `latestPackage`.`release_index` DESC
                      LIMIT 1,1) = `modTransportPackage`.`signature`",
            ]);
            $c->where([
                [
                    'modTransportPackage.package_name' => 'minifyx',
                    'OR:modTransportPackage.package_name:=' => 'MinifyX',
                ],
                'installed:IS NOT' => null
            ]);
            /** @var modTransportPackage $oldPackage */
            $oldPackage = $modx->getObject('transport.modTransportPackage', $c);
            
            if ($oldPackage && $oldPackage->compareVersion('2.0.0-pl', '>')) {
                changeSettingKey($modx, 'minifyx_process_registered', 'minifyx.process_registered');
                changeSettingKey($modx, 'minifyx_process_images', 'minifyx.process_images');
                changeSettingKey($modx, 'minifyx_exclude_registered', 'minifyx.exclude_registered');
                changeSettingKey($modx, 'minifyx_exclude_images', 'minifyx.exclude_images');
                changeSettingKey($modx, 'minifyx_images_filters', 'minifyx.images_filters');
                changeSettingKey($modx, 'minifyx_minifyJs', 'minifyx.minifyJs');
                changeSettingKey($modx, 'minifyx_minifyCss', 'minifyx.minifyCss');
                changeSettingKey($modx, 'minifyx_processRawJs', 'minifyx.processRawJs');
                changeSettingKey($modx, 'minifyx_processRawCss', 'minifyx.processRawCss');
                changeSettingKey($modx, 'minifyx_jsFilename', 'minifyx.jsFilename');
                changeSettingKey($modx, 'minifyx_cssFilename', 'minifyx.cssFilename');
                changeSettingKey($modx, 'minifyx_cacheFolder', 'minifyx.cacheFolder');
                changeSettingKey($modx, 'minifyx_forceUpdate', 'minifyx.forceUpdate');
                changeSettingKey($modx, 'minifyx_forceDelete', 'minifyx.forceDelete');
                changeSettingKey($modx, 'minifyx_minifyHtml', 'minifyx.minifyHtml');
                changeSetting($modx, 'minifyx.cacheFolder', '/assets/components/minifyx/cache/', 'assets/components/minifyx/cache/');
            }

            break;
    }
}
return true;
