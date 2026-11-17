<?php
/**
 * Setting Lexicon Entries for MinifyX
 *
 * @package minifyx
 * @subpackage lexicon
 */
$_lang['setting_minifyx.cachePath'] = 'Cache-Ordner';
$_lang['setting_minifyx.cachePath_desc'] = 'Geben Sie den Ordner an, in dem das Plugin die Ergebnisse seiner Arbeit ablegt. Sie können einen nicht existierenden Ordner angeben, er wird dann automatisch erstellt. Ein relativer Pfad befindet sich im MODX-Basispfad. Sie können die Platzhalter {base_path}, {assets_path} und {core_path} verwenden.';
$_lang['setting_minifyx.cacheUrl'] = 'Cache URL';
$_lang['setting_minifyx.cacheUrl_desc'] = 'Geben Sie die URL an, von der das Plugin die Ergebnisse seiner Arbeit abruft. Die URL muss auf den Cache-Ordner verweisen. Einer relativen URL wird die MODX-Basis-URL vorangestellt. Sie können die Platzhalter {base_url} und {assets_url} verwenden.';
$_lang['setting_minifyx.cssFilename'] = 'CSS Dateiname';
$_lang['setting_minifyx.cssFilename_desc'] = 'Geben Sie den Namen der CSS-Datei an, die alle kombinierten Stile enthalten soll. Wenn die Komprimierung aktiviert ist, wird das Suffix .min hinzugefügt.';
$_lang['setting_minifyx.cssTpl'] = 'CSS Template';
$_lang['setting_minifyx.cssTpl_desc'] = 'Name eines Template-Chunks für das CSS-Tag. Der Platzhalter "[[+file]]" muss vorhanden sein.';
$_lang['setting_minifyx.debug'] = 'Debug';
$_lang['setting_minifyx.debug_desc'] = 'Debug-Informationen im MODX-Fehlerprotokoll ausgeben.';
$_lang['setting_minifyx.excludeRegistered'] = 'Skripte und Stile ausschließen';
$_lang['setting_minifyx.excludeRegistered_desc'] = 'Ein regulärer Ausdruck für registrierte Skripte und Stile, die unverändert ausgegeben und nicht kombiniert oder minimiert werden.';
$_lang['setting_minifyx.jsFilename'] = 'Javascript Dateiname';
$_lang['setting_minifyx.jsFilename_desc'] = 'Geben Sie den Namen der Javascript-Datei an, die alle kombinierten Skripte enthalten soll. Wenn die Komprimierung aktiviert ist, wird die Endung .min hinzugefügt.';
$_lang['setting_minifyx.jsTpl'] = 'Javascript Template';
$_lang['setting_minifyx.jsTpl_desc'] = 'Name eines Template-Chunks für das JS-Tag. Der Platzhalter "[[+file]]" muss vorhanden sein.';
$_lang['setting_minifyx.minifyCss'] = 'CSS komprimieren';
$_lang['setting_minifyx.minifyCss_desc'] = 'CSS-Komprimierung aktivieren.';
$_lang['setting_minifyx.minifyHtml'] = 'HTML komprimieren';
$_lang['setting_minifyx.minifyHtml_desc'] = 'Komprimiert den Seiteninhalt vor der Ausgabe.';
$_lang['setting_minifyx.minifyJs'] = 'Javascript komprimieren';
$_lang['setting_minifyx.minifyJs_desc'] = 'Javascript-Komprimierung aktivieren.';
$_lang['setting_minifyx.processRegistered'] = 'Registrierte verarbeiten';
$_lang['setting_minifyx.processRegistered_desc'] = 'Sie können die automatische Verarbeitung aller registrierten Skripte und Stile der Seite mithilfe des MinifyX-Plugins aktivieren.';
$_lang['setting_minifyx.selection_range'] = 'Ressourcen-IDs für das Plugin';
$_lang['setting_minifyx.selection_range_desc'] = 'Eine kommagetrennte Liste mit Ressourcen-IDs, die das MinifyX-Plugin (nicht) bearbeiten soll. Die Einstellung minifyx.selection_type legt fest, ob die Ressourcen ein- oder ausgeschlossen werden.';
$_lang['setting_minifyx.selection_type'] = 'Auswahltyp für das Plugin';
$_lang['setting_minifyx.selection_type_desc'] = 'Wenn nur die Ressourcen in minifyx.selection_range durch das MinifyX-Plugin bearbeitet werden sollen, ändern Sie den Wert dieser Einstellung auf `include`. Mit `exclude` werden die Ressourcen in minifyx.selection_range nicht bearbeitet.';
$_lang['setting_minifyx.tpl_only'] = 'Keine Ressourcen mit leerer Vorlage bearbeiten';
$_lang['setting_minifyx.tpl_only_desc'] = 'Aktivieren Sie diese Einstellung, wenn das MinifyX-Plugin nicht auf Ressourcen mit der Vorlage `(blank)` arbeiten soll.';
