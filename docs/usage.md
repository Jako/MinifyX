---
title: Usage
---

After the package is installed, you can use the snippet and the plugin to create
combined and optionally minified assets.

## Snippet

This snippet combines and optionally minifies the referenced javascript and stylesheet files.

The following properties can be used in the snippet call:

| Property       | Description                                                                                                                                                                                             | Default                      |
|----------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|------------------------------|
| cssFilename    | Base name of the css file, without extension                                                                                                                                                            | The according system setting |
| cssPlaceholder | Name of the css placeholder. Used when &registerCss is set to placeholder                                                                                                                               | MinifyX.css                  |
| cssSources     | Comma-separated list of CSS files for processing. You can specify *.css, *.less, and *.scss files.                                                                                                      | -                            |
| cssTpl         | Name of a template chunk for the CSS tag. The placeholder "[[+file]]" must be present.                                                                                                                  | The according system setting |
| forceUpdate    | Disable MinifyX cache and generate new scripts and styles every time.                                                                                                                                   | 0 (No)                       |
| jsFilename     | Base name of the js file, without extension                                                                                                                                                             | The according system setting |
| jsPlaceholder  | Name of javascript placeholder. Used when &registerJs is set to placeholder                                                                                                                             | MinifyX.javascript           |
| jsSources      | Comma-separated list of JS files for processing. You can specify *.js files.                                                                                                                            | -                            |
| jsTpl          | Name of a template chunk for the JS tag. The placeholder "[[+file]]" must be present.                                                                                                                            | The according system setting |
| minifyCss      | Turn on CSS minification? The property overrides the minifyx.minifyCss system setting.                                                                                                                  | 0 (No)                       |
| minifyJs       | Turn on JS minification? The property overrides the minifyx.minifyJs system setting.                                                                                                                    | 0 (No)                       |
| registerCss    | How do you want the CSS to be registered? It can be output in the placeholder (`placeholder`), called in the "head" tag (`default`) or output immediately (`print`). | placeholder                  |
| registerJs     | How do you want the javascript to be registered? It can be output in the placeholder (`placeholder`), called in the "head" tag (`startup`), placed before the closing "body" tag (`default`) or output immediately (`print`). | placeholder                  |

### Sources

The sources can be specified relative to the MODX base path (i.e.
`assets/js/scripts.js`), as root-relative URL (i.e. `/assets/js/scripts.js`),
with the MODX site URL or with the MODX base path in front. The sources can
contain MODX system settings like `[[++assets_url]]` or `{assets_url}`.

External sources (starting with `http://`, `https://` or `//`) are not combined.
They are output before the combined file, so that the combined local files can
use them.

Files that don't exist and files with another extension than js, css, less or
scss are skipped and logged in the MODX error log. The other files are combined
anyway.

### Stylesheets

Relative URLs in the combined stylesheets (i.e. `url(img/background.png)` or
`url(../fonts/font.woff2)`) are rewritten to root-relative URLs, because the
combined file is located in the MinifyX cache folder. This is done for the
snippet and the plugin.

An `@import` in a plain CSS file is not inlined. Browsers only process an
`@import` at the top of a stylesheet, so it is usually ignored in a combined
file. Add the imported file to the sources before the importing file instead.
SCSS and LESS imports are inlined by the compiler.

### Groups

Sources that are used on every snippet call can be defined in the file
`core/components/minifyx/config/groups.php`. The file has to return an array
with the keys `css` and `js`. The sources of the groups are added before the
sources of the snippet call.

```php
<?php
return [
    'css' => [
        'assets/css/bootstrap.min.css',
    ],
    'js' => [
        'assets/js/jquery.js',
    ],
];
```

## Plugin

This plugin combines and optionally minifies the registered javascript and
stylesheet files registered with the MODX API. It processes only resources with
an HTML content type.

It can be configured with the MinifyX system settings.

By default, the plugin does not work on resources with the `(blank)` template
(`minifyx.tpl_only`). With the `minifyx.selection_type` and
`minifyx.selection_range` system settings, the plugin can be restricted to or
disabled for a list of resources. These settings don't affect the snippet.

The registered stylesheets are output before the other registered assets. The
order of the registered stylesheets and the order of the other registered
assets (scripts, inline scripts and HTML blocks) are preserved. Only consecutive
local files of the same type are combined in one file. So a page could get
more than one combined file, if i.e. an inline script is registered between
two script files.

The following registered assets are output unchanged:

- External files (starting with `http://`, `https://` or `//`)
- Files matched by the `minifyx.excludeRegistered` system setting
- Files located in the MinifyX cache folder (i.e. added by the MinifyX snippet)
- Inline scripts, conditional comments and other HTML blocks

If a combined file can't be generated, the original files are output instead.

## System Settings

MinifyX uses the following system settings in the namespace `minifyx`:

| Key                       | Name                       | Description                                                                                                                                                                                                                             | Default                                |
|---------------------------|----------------------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|----------------------------------------|
| minifyx.cachePath         | Cache Folder               | Specify the folder where the plugin will put the results of its work. You can specify a non-existent folder, it will be created automatically. A relative path is located in the MODX base path. You can use the placeholders {base_path}, {assets_path} and {core_path}. | assets/minifyx/                        |
| minifyx.cacheUrl          | Cache URL                  | Specify the URL where the plugin will put the results of its work. It has to point to the cache folder. A relative URL is prepended with the MODX base URL. You can use the placeholders {base_url} and {assets_url}.                 | assets/minifyx/                        |
| minifyx.cssFilename       | CSS Filename               | Specify the name of the CSS file that will contain all combined styles. The suffix .min will be added when compression is enabled.                                                                                                      | styles                                 |
| minifyx.cssTpl            | CSS Template               | Name of a template chunk for the CSS tag. The placeholder "[[+file]]" must be present.                                                                                                                                                  | tplMinifyXcss                          |
| minifyx.debug             | Debug                      | Log debug information in the MODX error log.                                                                                                                                                                                            | No                                     |
| minifyx.excludeRegistered | Exclude Scripts and Styles | A regular expression for registered scripts and styles, that are output unchanged and are not combined or minified.                                                                                                                     | #(scripts&#x7cstyles)_[a-z0-9]{11}\.#i |
| minifyx.jsFilename        | Javascript Filename        | Specify the name of the javascript file that will contain all combined scripts. The suffix .min will be added when compression is enabled.                                                                                              | scripts                                |
| minifyx.jsTpl             | Javascript Template        | Name of a template chunk for the JS tag. The placeholder "[[+file]]" must be present.                                                                                                                                                            | tplMinifyXjs                           |
| minifyx.minifyCss         | Compress CSS               | Enable CSS compression.                                                                                                                                                                                                                 | No                                     |
| minifyx.minifyHtml        | Compress HTML              | Compress the page content before output.                                                                                                                                                                                                | No                                     |
| minifyx.minifyJs          | Compress Javascript        | Enable javascript compression.                                                                                                                                                                                                          | No                                     |
| minifyx.processRegistered | Process Registered         | You can enable automatic processing of all registered scripts and styles of the page using the MinifyX plugin.                                                                                                                          | No                                     |
| minifyx.selection_range   | Resource IDs for the Plugin | A comma-separated list of resource IDs the MinifyX plugin should (not) work on. The setting minifyx.selection_type defines, if the resources are included or excluded. | - |
| minifyx.selection_type    | Selection Type for the Plugin | If only the resources in minifyx.selection_range should be processed by the MinifyX plugin, change the value of this setting to `include`. With `exclude`, the resources in minifyx.selection_range are not processed. | exclude |
| minifyx.tpl_only          | Don’t Work on Resources with Blank Template | Enable this, if the MinifyX plugin should not work on resources with the `(blank)` template. | Yes |
