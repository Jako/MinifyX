<?php
/**
 * Setting Lexicon Entries for MinifyX
 *
 * @package minifyx
 * @subpackage lexicon
 */
$_lang['setting_minifyx.cacheFolder'] = 'Cache Folder';
$_lang['setting_minifyx.cacheFolder_desc'] = 'Specify the folder where the plugin will put the results of it’s work. You can specify a non-existent folder, it will be created automatically.';
$_lang['setting_minifyx.cacheUrl'] = 'Cache URL';
$_lang['setting_minifyx.cacheUrl_desc'] = 'Specify the URL where the plugin will put the results of it’s work. It has to point to the cache folder.';
$_lang['setting_minifyx.cssFilename'] = 'CSS Filename';
$_lang['setting_minifyx.cssFilename_desc'] = 'Specify the name of the prepared CSS file that will contain all processed scripts. To it will be added the time of creation and suffix .min, if compression is enabled.';
$_lang['setting_minifyx.cssTpl'] = 'CSS Template';
$_lang['setting_minifyx.cssTpl_desc'] = 'Name of a template chunk for the CSS tag. The placeholder "[[+file]]" must be present.';
$_lang['setting_minifyx.debug'] = 'Debug';
$_lang['setting_minifyx.debug_desc'] = 'Log debug information in the MODX error log.';
$_lang['setting_minifyx.excludeRegistered'] = 'Exclude Scripts and Styles';
$_lang['setting_minifyx.excludeRegistered_desc'] = 'A regular expression for exclude files from processing. By default excludes scripts and styles prepared by snippet MinifyX.';
$_lang['setting_minifyx.jsFilename'] = 'Javascript Filename';
$_lang['setting_minifyx.jsFilename_desc'] = 'Specify the name of the prepared javascript file that will contain all processed scripts. To it will be added the time of creation and suffix .min, if compression is enabled.';
$_lang['setting_minifyx.jsTpl'] = 'Javascript Template';
$_lang['setting_minifyx.jsTpl_desc'] = 'Name of a template chunk for the JS tag. Placeholder "[[+file]]" must exists.';
$_lang['setting_minifyx.minifyCss'] = 'Compress CSS?';
$_lang['setting_minifyx.minifyCss_desc'] = 'You can enable compression CSS compression. All files that have suffix .min in the name will be skipped.';
$_lang['setting_minifyx.minifyHtml'] = 'Compress HTML?';
$_lang['setting_minifyx.minifyHtml_desc'] = 'Compress the page content before output.';
$_lang['setting_minifyx.minifyJs'] = 'Compress Javascript?';
$_lang['setting_minifyx.minifyJs_desc'] = 'You can enable compression javascript compression. All files that have suffix .min in the name will be skipped.';
$_lang['setting_minifyx.processRegistered'] = 'Process Scripts and Styles';
$_lang['setting_minifyx.processRegistered_desc'] = 'You can enable automatic processing of all registered scripts and styles of the page using the plugin MinifyX.';
