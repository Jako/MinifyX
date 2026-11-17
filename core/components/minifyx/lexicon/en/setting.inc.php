<?php
/**
 * Setting Lexicon Entries for MinifyX
 *
 * @package minifyx
 * @subpackage lexicon
 */
$_lang['setting_minifyx.cachePath'] = 'Cache Folder';
$_lang['setting_minifyx.cachePath_desc'] = 'Specify the folder where the plugin will put the results of its work. You can specify a non-existent folder, it will be created automatically. A relative path is located in the MODX base path. You can use the placeholders {base_path}, {assets_path} and {core_path}.';
$_lang['setting_minifyx.cacheUrl'] = 'Cache URL';
$_lang['setting_minifyx.cacheUrl_desc'] = 'Specify the URL where the plugin will put the results of its work. It has to point to the cache folder. A relative URL is prepended with the MODX base URL. You can use the placeholders {base_url} and {assets_url}.';
$_lang['setting_minifyx.cssFilename'] = 'CSS Filename';
$_lang['setting_minifyx.cssFilename_desc'] = 'Specify the name of the CSS file that will contain all combined styles. The suffix .min will be added when compression is enabled.';
$_lang['setting_minifyx.cssTpl'] = 'CSS Template';
$_lang['setting_minifyx.cssTpl_desc'] = 'Name of a template chunk for the CSS tag. The placeholder "[[+file]]" must be present.';
$_lang['setting_minifyx.debug'] = 'Debug';
$_lang['setting_minifyx.debug_desc'] = 'Log debug information in the MODX error log.';
$_lang['setting_minifyx.excludeRegistered'] = 'Exclude Scripts and Styles';
$_lang['setting_minifyx.excludeRegistered_desc'] = 'A regular expression for registered scripts and styles, that are output unchanged and are not combined or minified.';
$_lang['setting_minifyx.jsFilename'] = 'Javascript Filename';
$_lang['setting_minifyx.jsFilename_desc'] = 'Specify the name of the javascript file that will contain all combined scripts. The suffix .min will be added when compression is enabled.';
$_lang['setting_minifyx.jsTpl'] = 'Javascript Template';
$_lang['setting_minifyx.jsTpl_desc'] = 'Name of a template chunk for the JS tag. The placeholder "[[+file]]" must be present.';
$_lang['setting_minifyx.minifyCss'] = 'Compress CSS';
$_lang['setting_minifyx.minifyCss_desc'] = 'Enable CSS compression.';
$_lang['setting_minifyx.minifyHtml'] = 'Compress HTML';
$_lang['setting_minifyx.minifyHtml_desc'] = 'Compress the page content before output.';
$_lang['setting_minifyx.minifyJs'] = 'Compress Javascript';
$_lang['setting_minifyx.minifyJs_desc'] = 'Enable javascript compression.';
$_lang['setting_minifyx.processRegistered'] = 'Process Registered';
$_lang['setting_minifyx.processRegistered_desc'] = 'You can enable automatic processing of all registered scripts and styles of the page using the MinifyX plugin.';
$_lang['setting_minifyx.selection_range'] = 'Resource IDs for the Plugin';
$_lang['setting_minifyx.selection_range_desc'] = 'A comma-separated list of resource IDs the MinifyX plugin should (not) work on. The setting minifyx.selection_type defines, if the resources are included or excluded.';
$_lang['setting_minifyx.selection_type'] = 'Selection Type for the Plugin';
$_lang['setting_minifyx.selection_type_desc'] = 'If only the resources in minifyx.selection_range should be processed by the MinifyX plugin, change the value of this setting to `include`. With `exclude`, the resources in minifyx.selection_range are not processed.';
$_lang['setting_minifyx.tpl_only'] = 'Don’t Work on Resources with Blank Template';
$_lang['setting_minifyx.tpl_only_desc'] = 'Enable this, if the MinifyX plugin should not work on resources with the `(blank)` template.';
