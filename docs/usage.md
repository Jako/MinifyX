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
| jsPlaceholder  | Name of javascript placeholder. Used when &registerCss is set to placeholder                                                                                                                            | MinifyX.javascript           |
| jsSources      | Comma-separated list of JS files for processing. You can specify *.js files.                                                                                                                            | -                            |
| jsTpl          | Name of a template chunk for the JS tag. Placeholder "[[+file]]" must exists.                                                                                                                           | The according system setting |
| minifyCss      | Turn on CSS minification?                                                                                                                                                                               | 0 (No)                       |
| minifyJs       | Turn on JS minification?                                                                                                                                                                                | 0 (No)                       |
| registerCss    | How do you want the CSS to be registered? It can be output in the placeholder, called in the "head" tag (Default) or output immediately (Print).                                                        | placeholder                  |
| registerJs     | How do you want the javascript to be registered? It can be output in the placeholder, called in the "head" tag (Startup), placed before the closing "body" tag (Default) or output immediately (Print). | placeholder                  |

## Plugin

This plugin combines and optionally minifies the registered javascript and stylesheet files registered with the MODX API.

It can be configured with the MinifyX system settings.

## System Settings

MinifyX uses the following system settings in the namespace `minifyx`:

| Key                       | Name                       | Description                                                                                                                                     | Default                                |
|---------------------------|----------------------------|-------------------------------------------------------------------------------------------------------------------------------------------------|----------------------------------------|
| minifyx.cachePath         | Cache Folder               | Specify the folder where the plugin will put the results of it’s work. You can specify a non-existent folder, it will be created automatically. | assets/minifyx/                        |
| minifyx.cacheUrl          | Cache URL                  | Specify the URL where the plugin will put the results of it’s work. It has to point to the cache folder.                                        | assets/minifyx/                        |
| minifyx.cssFilename       | CSS Filename               | Specify the name of the CSS file that will contain all combined styles. The suffix .min will be added when compression is enabled.              | styles                                 |
| minifyx.cssTpl            | CSS Template               | Name of a template chunk for the CSS tag. The placeholder "[[+file]]" must be present.                                                          | tplMinifyXcss                          |
| minifyx.debug             | Debug                      | Log debug information in the MODX error log.                                                                                                    | No                                     |
| minifyx.excludeRegistered | Exclude Scripts and Styles | A regular expression for excludeing files from processing.                                                                                      | #(scripts&#x7cstyles)_[a-z0-9]{11}\.#i |
| minifyx.jsFilename        | Javascript Filename        | Specify the name of the javascript file that will contain all combined scripts. The suffix .min will be added when compression is enabled.      | scripts                                |
| minifyx.jsTpl             | Javascript Template        | Name of a template chunk for the JS tag. Placeholder "[[+file]]" must exists.                                                                   | tplMinifyXjs                           |
| minifyx.minifyCss         | Compress CSS               | Enable CSS compression.                                                                                                                         | No                                     |
| minifyx.minifyHtml        | Compress HTML              | Compress the page content before output.                                                                                                        | No                                     |
| minifyx.minifyJs          | Compress Javascript        | Enable javascript compression.                                                                                                                  | No                                     |
| minifyx.processRegistered | Process Registered         | You can enable automatic processing of all registered scripts and styles of the page using the MinifyX plugin.                                  | No                                     |
