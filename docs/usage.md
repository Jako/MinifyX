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
| forceUpdate    | Disable MinifyX cache and generate new scripts and styles every time.                                                                                                                                   | No                           |
| jsFilename     | Base name of the js file, without extension                                                                                                                                                             | The according system setting |
| jsPlaceholder  | Name of javascript placeholder. Used when &registerCss is set to placeholder                                                                                                                            | MinifyX.javascript           |
| jsSources      | Comma-separated list of JS files for processing. You can specify *.js files.                                                                                                                            | -                            |
| jsTpl          | Name of a template chunk for the JS tag. Placeholder "[[+file]]" must exists.                                                                                                                           | The according system setting |
| minifyCss      | Turn on CSS minification??                                                                                                                                                                              | No                           |
| minifyJs       | Turn on JS minification?                                                                                                                                                                                | No                           |
| registerCss    | How do you want the CSS to be registered? It can be output in the placeholder, called in the "head" tag (Default) or output immediately (Print).                                                        | placeholder                  |
| registerJs     | How do you want the javascript to be registered? It can be output in the placeholder, called in the "head" tag (Startup), placed before the closing "body" tag (Default) or output immediately (Print). | placeholder                  |

## Plugin

This plugin combines and optionally minifies the registered javascript and stylesheet files registered with the MODX API.

It can be configured with the MinifyX system settings.

## System Settings

MinifyX uses the following system settings in the namespace `minifyx`:

| Key               | Description                                                                                                                                                                   | Default        |
|-------------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|----------------|
| cacheFolder       | Specify the folder where the plugin will put the results of it’s work. You can specify a non-existent folder, it will be created automatically.                               | assets/minifyx/ |
| cacheUrl          | Specify the url where the plugin will put the results of it’s work. It has to point to the cache folder.                                                                      | assets/minifyx/ |
| cssFilename       | Specify the name of the prepared CSS file that will contain all processed scripts. To it will be added the time of creation and suffix .min, if compression is enabled.       | styles         |
| cssTpl            | Name of a template chunk for the CSS tag. Placeholder "[[+file]]" must exists.                                                                                                | tplMinifyXcss  |
| debug             | Log debug information in the MODX error log.                                                                                                                                  | No             |
| excludeRegistered | A regular expression for exclude files from processing. By default excludes scripts and styles prepared by snippet MinifyX.                                                   | #(scripts&#x7c;styles)_[a-z0-9]{11}\.#i |
| jsFilename        | Specify the name of the prepared javascript file that will contain all processed scripts. To it will be added the time of creation and suffix .min, if compression is enabled | scripts        |
| jsTpl             | Name of a template chunk for the JS tag. Placeholder "[[+file]]" must exists.                                                                                                 | tplMinifyXjs   |
| minifyCss         | You can enable compression CSS compression. All files that have suffix .min in the name will be skipped.                                                                      | No             |
| minifyHtml        | Compress the page content before output.                                                                                                                                      | No             |
| minifyJs          | You can enable compression javascript compression. All files that have suffix .min in the name will be skipped.                                                               | No             |
| processRegistered | You can enable automatic processing of all registered scripts and styles of the page using the plugin MinifyX.                                                                | No             |
