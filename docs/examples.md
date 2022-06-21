## Snippet

To use the snippet, you can use the following full snippet call with default and
example values:

```
[[!MinifyX?
&cacheFolder=`assets/minifyx/`
&cacheUrl=`assets/minifyx/`
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
#
[[!+MinifyX.javascript]]
[[!+MinifyX.css]]
#
```

## Plugin

To register the javascript and stylesheet files with the MODX API methods you
can use the following example code:

```
[[*id:input=`assets/css/bootstrap.min.css`:cssToHead]]
[[*id:input=`assets/css/styles.css`:cssToHead]]
[[*id:input=`assets/js/jquery.js`:jsToHead]]
[[*id:input=`assets/js/bootstrap.bundle.min.js`:jsToHead]]
[[*id:input=`assets/js/scripts.js`:jsToBottom]]
```

To combine and optionally minify the javascript and stylesheet files you have to enable the
processRegistered system setting.
