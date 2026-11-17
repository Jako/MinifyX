<?php
/**
 * Setting Lexicon Entries for MinifyX
 *
 * @package minifyx
 * @subpackage lexicon
 */
$_lang['setting_minifyx.cachePath'] = 'Папка кэша';
$_lang['setting_minifyx.cachePath_desc'] = 'Укажите папку, в которую плагин будет помещать результаты своей работы. Вы можете указать несуществующую папку, она будет создана автоматически. Относительный путь отсчитывается от базового пути MODX. Можно использовать плейсхолдеры {base_path}, {assets_path} и {core_path}.';
$_lang['setting_minifyx.cacheUrl'] = 'URL-адрес кэша';
$_lang['setting_minifyx.cacheUrl_desc'] = 'Укажите URL, куда плагин будет помещать результаты своей работы. Он должен указывать на папку кэша. К относительному URL добавляется базовый URL MODX. Можно использовать плейсхолдеры {base_url} и {assets_url}.';
$_lang['setting_minifyx.cssFilename'] = 'Имя файла CSS';
$_lang['setting_minifyx.cssFilename_desc'] = 'Укажите имя CSS-файла, который будет содержать все объединенные стили. Суффикс .min будет добавлен, если включено сжатие.';
$_lang['setting_minifyx.cssTpl'] = 'Шаблон CSS';
$_lang['setting_minifyx.cssTpl_desc'] = 'Имя фрагмента шаблона для тега CSS. Должен присутствовать плейсхолдер "[[+file]]".';
$_lang['setting_minifyx.debug'] = 'Отладка';
$_lang['setting_minifyx.debug_desc'] = 'Записывать отладочную информацию в лог ошибок MODX.';
$_lang['setting_minifyx.excludeRegistered'] = 'Исключить скрипты и стили';
$_lang['setting_minifyx.excludeRegistered_desc'] = 'Регулярное выражение для зарегистрированных скриптов и стилей, которые выводятся без изменений и не объединяются и не минифицируются.';
$_lang['setting_minifyx.jsFilename'] = 'Имя файла Javascript';
$_lang['setting_minifyx.jsFilename_desc'] = 'Укажите имя файла javascript, который будет содержать все объединенные сценарии. Суффикс .min будет добавлен, если включено сжатие.';
$_lang['setting_minifyx.jsTpl'] = 'Шаблон Javascript';
$_lang['setting_minifyx.jsTpl_desc'] = 'Имя фрагмента шаблона для тега JS. Должен присутствовать плейсхолдер "[[+file]]".';
$_lang['setting_minifyx.minifyCss'] = 'Сжатие CSS';
$_lang['setting_minifyx.minifyCss_desc'] = 'Включите сжатие CSS.';
$_lang['setting_minifyx.minifyHtml'] = 'Сжать HTML';
$_lang['setting_minifyx.minifyHtml_desc'] = 'Включает сжатие HTML страницы перед выводом пользователю.';
$_lang['setting_minifyx.minifyJs'] = 'Сжать Javascript';
$_lang['setting_minifyx.minifyJs_desc'] = 'Включите сжатие javascript.';
$_lang['setting_minifyx.processRegistered'] = 'Обрабатывать зарегистрированные';
$_lang['setting_minifyx.processRegistered_desc'] = 'Вы можете включить автоматическую обработку всех зарегистрированных скриптов и стилей страницы с помощью плагина MinifyX.';
$_lang['setting_minifyx.selection_range'] = 'ID ресурсов для плагина';
$_lang['setting_minifyx.selection_range_desc'] = 'Список ID ресурсов через запятую, которые плагин MinifyX должен (не) обрабатывать. Настройка minifyx.selection_type определяет, включаются или исключаются эти ресурсы.';
$_lang['setting_minifyx.selection_type'] = 'Тип выбора для плагина';
$_lang['setting_minifyx.selection_type_desc'] = 'Если плагин MinifyX должен обрабатывать только ресурсы из minifyx.selection_range, измените значение этой настройки на `include`. При значении `exclude` ресурсы из minifyx.selection_range не обрабатываются.';
$_lang['setting_minifyx.tpl_only'] = 'Не обрабатывать ресурсы с пустым шаблоном';
$_lang['setting_minifyx.tpl_only_desc'] = 'Включите, если плагин MinifyX не должен обрабатывать ресурсы с шаблоном `(blank)`.';
