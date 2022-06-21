<?php
/**
 * Setting Lexicon Entries for MinifyX
 *
 * @package minifyx
 * @subpackage lexicon
 */
$_lang['setting_minifyx.cacheFolder'] = 'Директория с готовыми файлами';
$_lang['setting_minifyx.cacheFolder_desc'] = 'Укажите директорию, в которую плагин будет складывать результаты своей работы. Можно указывать несуществующую директорию - она будет создана автоматически.';
$_lang['setting_minifyx.cacheUrl'] = 'Папка кэша';
$_lang['setting_minifyx.cacheUrl_desc'] = 'Укажите url, куда плагин будет помещать результаты своей работы. Он должен указывать на папку кэша.';
$_lang['setting_minifyx.cssFilename'] = 'Имя готового css';
$_lang['setting_minifyx.cssFilename_desc'] = 'Укажите имя готового css файла, который будет содержать все обработанные стили. К нему будет добавлено время создания и, если включено сжатие - суффикс .min.';
$_lang['setting_minifyx.cssTpl'] = 'Шаблон CSS';
$_lang['setting_minifyx.cssTpl_desc'] = 'Шаблон CSS файла для вывода. Вставка "[[+file]]" должна существовать.';
$_lang['setting_minifyx.debug'] = 'Отладка';
$_lang['setting_minifyx.debug_desc'] = 'Записывать отладочную информацию в лог ошибок MODX.';
$_lang['setting_minifyx.excludeRegistered'] = 'Исключить скрипты и стили';
$_lang['setting_minifyx.excludeRegistered_desc'] = 'Регулярное выражение для исключения зарегистрированных файлов из обработки. По умолчанию исключаются скрипты и стили, подготовленные сниппетом MinifyX.';
$_lang['setting_minifyx.jsFilename'] = 'Имя готового javascript';
$_lang['setting_minifyx.jsFilename_desc'] = 'Укажите имя готового javascript файла, который будет содержать все обработанные скрипты. К нему будет добавлено время создания и, если включено сжатие - суффикс .min.';
$_lang['setting_minifyx.jsTpl'] = 'Шаблон Javascript';
$_lang['setting_minifyx.jsTpl_desc'] = 'Шаблон файла Javascript для вывода. Вставка "[[+file]]" должна существовать.';
$_lang['setting_minifyx.minifyCss'] = 'Сжимать css?';
$_lang['setting_minifyx.minifyCss_desc'] = 'Включает сжатие css. Все файлы, у которых есть в имени суффикс .min будут пропущены.';
$_lang['setting_minifyx.minifyHtml'] = 'Сжимать HTML?';
$_lang['setting_minifyx.minifyHtml_desc'] = 'Включает сжатие HTML страницы перед выводом пользователю.';
$_lang['setting_minifyx.minifyJs'] = 'Сжимать javascript?';
$_lang['setting_minifyx.minifyJs_desc'] = 'Включает сжатие javascript. Все файлы, у которых есть в имени суффикс .min будут пропущены.';
$_lang['setting_minifyx.processRegistered'] = 'Обработка скриптов и стилей';
$_lang['setting_minifyx.processRegistered_desc'] = 'Вы можете включить автоматическую сборку и обработку всех зарегистрированных скриптов и стилей страницы при помощи плагина MinifyX.';
