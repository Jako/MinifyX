## Snippet

To use the snippet, you can use the following full snippet call with default and
example values:

```html
[[!MinifyX?
&cssFilename=`style`
&cssPlaceholder=`MinifyX.css`
&cssSources=`
assets/css/bootstrap.min.css,
assets/css/styles.css,
`
&cssTpl=`tplMinifyXcss`
&forceUpdate=`0`
&jsFilename=`scripts`
&jsPlaceholder=`MinifyX.javascript`
&jsSources=`
assets/js/jquery.js,
assets/js/bootstrap.bundle.min.js,
`
&jsTpl=`tplMinifyXjs`
&minifyCss=`0`
&minifyJs=`0`
&registerCss=`placeholder`
&registerJs=`placeholder`
]]
```

The following placeholders have to be added in the template.

```html
[[!+MinifyX.javascript]]
[[!+MinifyX.css]]
```

The snippet and the placeholder have to be called both uncached or both cached.
If they are called cached, the generated minified files are only created when
the resource is not cached, so you can't change the minified assets on the fly.

There is an internal MinifyX cache used, so it is not a real issue to call the
snippet uncached.

### Use without placeholder

If you don't want to use the placeholder, you can replace the following lines in
the snippet call above.

```html
&registerCss=`default`
&registerJs=`default`
```

That way, the snippet will insert the script and style tags automatically in the
code with the MODX regClient methods.

## Plugin

All registered javascript and stylesheet files of other MODX extras can be at
least combined and optionally minified. To combine the files you have to enable
the `minifyx.processRegistered` system setting. To minify the registered
javascripts, you have to enable `minifyx.minifyJs`. To minify the registered
styles, you have to enable `minifyx.minifyCss`.

To register javascript and stylesheet files directly on the page with the MODX
regClient API methods, you can use the following example:

```html
[[*id:input=`assets/css/bootstrap.min.css`:cssToHead]]
[[*id:input=`assets/css/styles.css`:cssToHead]]
[[*id:input=`assets/js/jquery.js`:jsToHead]]
[[*id:input=`assets/js/bootstrap.bundle.min.js`:jsToHead]]
[[*id:input=`assets/js/scripts.js`:jsToBottom]]
```

## Debugging

If you are facing issues with MinifyX, you can use the `minifyx.debug` system
setting. If this setting is enabled, the added files to the assets collection are
logged with the server path. 

All errors inside Assetic are logged without enabling this system setting.
Assetic will throw an error, when the file is not found, parts of the scripts
and styles are invalid etc.

So please use the MODX error log first to locate MinifyX issues. If you detect a
bug inside MinifyX, feel free to add it to the [bug
tracker](https://github.com/Jako/MinifyX/issues)
