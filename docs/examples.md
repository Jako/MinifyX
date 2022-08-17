## Snippet

To use the snippet, you can use the following full snippet call with default and
example values:

```
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

```
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

```
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

```
[[*id:input=`assets/css/bootstrap.min.css`:cssToHead]]
[[*id:input=`assets/css/styles.css`:cssToHead]]
[[*id:input=`assets/js/jquery.js`:jsToHead]]
[[*id:input=`assets/js/bootstrap.bundle.min.js`:jsToHead]]
[[*id:input=`assets/js/scripts.js`:jsToBottom]]
```
