<?php
/**
 * MinifyX
 *
 * Copyright 2012 by Patrick Nijkamp - https://github.com/b03tz
 * Copyright 2012-2018 by Vasily Naumkin - https://github.com/bezumkin
 * Copyright 2018-2021 by Sergey Shlokov - https://github.com/sergant210
 * Copyright 2021-2026 by Thomas Jakobi <office@treehillstudio.com>
 *
 * @package minifyx
 * @subpackage classfile
 */

namespace TreehillStudio\MinifyX;

use Assetic\Asset\AssetCollection;
use Assetic\Asset\AssetInterface;
use Assetic\Asset\FileAsset;
use Assetic\Filter\CallablesFilter;
use Assetic\Filter\JavaScriptMinifierFilter;
use Assetic\Filter\LessphpFilter;
use Assetic\Filter\ScssphpFilter;
use Assetic\Filter\StylesheetMinifyFilter;
use Assetic\Util\CssUtils;
use DirectoryIterator;
use Exception;
use modX;
use xPDO;

/**
 * class MinifyX
 */
class MinifyX
{
    /**
     * A reference to the modX instance
     * @var modX $modx
     */
    public modX $modx;

    /**
     * The namespace
     * @var string $namespace
     */
    public string $namespace = 'minifyx';

    /**
     * The package name
     * @var string $packageName
     */
    public string $packageName = 'MinifyX';

    /**
     * The version
     * @var string $version
     */
    public string $version = '2.1.0';

    /**
     * The class options
     * @var array $options
     */
    public array $options = [];

    /**
     * The class cache options
     * @var array $cacheOptions
     */
    public array $cacheOptions;

    /**
     * @var array
     */
    protected array $sources;

    /**
     * Content of the current processed file
     * @var string
     */
    protected string $_content;

    /**
     * MinifyX constructor
     *
     * @param modX $modx A reference to the modX instance.
     * @param array $options An array of options. Optional.
     */
    public function __construct(modX &$modx, array $options = [])
    {
        $this->modx =& $modx;

        $corePath = $this->getOption('core_path', $options, $this->modx->getOption('core_path', null, MODX_CORE_PATH) . 'components/' . $this->namespace . '/');
        $assetsPath = $this->getOption('assets_path', $options, $this->modx->getOption('assets_path', null, MODX_ASSETS_PATH) . 'components/' . $this->namespace . '/');
        $assetsUrl = $this->getOption('assets_url', $options, $this->modx->getOption('assets_url', null, MODX_ASSETS_URL) . 'components/' . $this->namespace . '/');

        // Load some default paths for easier management
        $this->options = array_merge([
            'namespace' => $this->namespace,
            'version' => $this->version,
            'corePath' => $corePath,
            'modelPath' => $corePath . 'model/',
            'vendorPath' => $corePath . 'vendor/',
            'chunksPath' => $corePath . 'elements/chunks/',
            'pagesPath' => $corePath . 'elements/pages/',
            'snippetsPath' => $corePath . 'elements/snippets/',
            'pluginsPath' => $corePath . 'elements/plugins/',
            'controllersPath' => $corePath . 'controllers/',
            'processorsPath' => $corePath . 'processors/',
            'templatesPath' => $corePath . 'templates/',
            'assetsPath' => $assetsPath,
            'assetsUrl' => $assetsUrl,
            'jsUrl' => $assetsUrl . 'js/',
            'cssUrl' => $assetsUrl . 'css/',
            'imagesUrl' => $assetsUrl . 'images/',
            'connectorUrl' => $assetsUrl . 'connector.php'
        ], $options);

        // Add default options
        $this->options = array_merge($this->options, [
            'debug' => (bool)$this->modx->getOption($this->namespace . '.debug', null, '0') == 1,
            'cachePath' => $this->modx->getOption($this->namespace . '.cachePath', null, MODX_ASSETS_PATH . 'minifyx/', true),
            'cacheUrl' => $this->modx->getOption($this->namespace . '.cacheUrl', null, MODX_ASSETS_URL . 'minifyx/', true),
            'cssFilename' => $this->modx->getOption($this->namespace . '.cssFilename', $options, 'styles', true),
            'jsFilename' => $this->modx->getOption($this->namespace . '.jsFilename', $options, 'scripts', true),
            'jsTpl' => $this->modx->getOption($this->namespace . '.jsTpl', $options, 'tplMinifyXjs', true),
            'cssTpl' => $this->modx->getOption($this->namespace . '.cssTpl', $options, 'tplMinifyXcss', true),
            'jsExt' => $this->modx->getOption($this->namespace . '.minifyJs', $options, false, true) ? '.min.js' : '.js',
            'cssExt' => $this->modx->getOption($this->namespace . '.minifyCss', $options, false, true) ? '.min.css' : '.css',
            'minifyJs' => (bool)$this->modx->getOption($this->namespace . '.minifyJs', $options, false, true),
            'minifyCss' => (bool)$this->modx->getOption($this->namespace . '.minifyCss', $options, false, true),
            'hashLength' => (int)$this->modx->getOption($this->namespace . '.hashLength', $options, 10, true),
        ]);

        $this->options['cacheUrl'] = $this->prepareCacheUrl($this->options['cacheUrl']);

        $this->cacheOptions = [
            xPDO::OPT_CACHE_KEY => $this->namespace,
        ];

        $lexicon = $this->modx->getService('lexicon', 'modLexicon');
        $lexicon->load($this->namespace . ':default');

        $this->packageName = $this->modx->lexicon('minifyx');
    }

    /**
     * Get a local configuration option or a namespaced system setting by key.
     *
     * @param string $key The option key to search for.
     * @param array $options An array of options that override local options.
     * @param mixed $default The default value returned if the option is not found locally or as a
     * namespaced system setting; by default this value is null.
     * @return mixed The option value or the default value specified.
     */
    public function getOption(string $key, array $options = [], $default = null)
    {
        $option = $default;
        if (!empty($key) && is_string($key)) {
            if ($options != null && array_key_exists($key, $options)) {
                $option = $options[$key];
            } elseif (array_key_exists($key, $this->options)) {
                $option = $this->options[$key];
            } elseif (array_key_exists("$this->namespace.$key", $this->modx->config)) {
                $option = $this->modx->getOption("$this->namespace.$key");
            }
        }
        return $option;
    }

    /**
     * Checks and creates cache path for storing prepared scripts and styles
     *
     * @return bool
     */
    public function prepareCachePath(): bool
    {
        $success = true;

        // Replace the path placeholders
        $basePath = $this->modx->getOption('base_path', null, MODX_BASE_PATH);
        $assetsPath = $this->modx->getOption('assets_path', null, MODX_ASSETS_PATH);
        $corePath = $this->modx->getOption('core_path', null, MODX_CORE_PATH);
        $path = str_replace(['{base_path}', '{assets_path}', '{core_path}'], [$basePath, $assetsPath, $corePath], trim($this->getOption('cachePath')));

        // Prepend the cache path with the MODX base path, if it is not located in one of the MODX paths.
        if (strpos($path, $basePath) !== 0 && strpos($path, $assetsPath) !== 0 && strpos($path, $corePath) !== 0) {
            $path = $basePath . ltrim($path, '/');
        }
        $path = rtrim($path, '/') . '/';
        $this->options['cachePath'] = $path;

        if (!file_exists($path)) {
            $cacheManager = $this->modx->getCacheManager();
            $success = $cacheManager->writeTree($path);
            $success &= $cacheManager->writeTree($path . 'js/');
            $success &= $cacheManager->writeTree($path . 'css/');
        }
        return $success;
    }

    /**
     * Prepare the cache URL, a relative URL is prepended with the MODX base URL.
     *
     * @param string $url
     * @return string
     */
    protected function prepareCacheUrl(string $url): string
    {
        $url = str_replace(['{base_url}', '{assets_url}'], [
            $this->modx->getOption('base_url', null, MODX_BASE_URL),
            $this->modx->getOption('assets_url', null, MODX_ASSETS_URL)
        ], trim($url));
        if (strpos($url, '/') !== 0 && strpos($url, '://') === false) {
            $url = $this->modx->getOption('base_url', null, MODX_BASE_URL) . $url;
        }
        return rtrim($url, '/') . '/';
    }

    /**
     * Prepare comma separated string or array of files.
     *
     * External files are collected in $externalFiles, if that parameter is
     * set. Otherwise they are registered with the MODX register methods.
     *
     * @param array|string $files
     * @param string $type Type of files
     * @param string $position The register position of files
     * @param array|null $externalFiles
     * @return array
     */
    public function prepareFiles($files, string $type = '', string $position = '', ?array &$externalFiles = null): array
    {
        if (is_string($files)) {
            $files = array_map('trim', explode(',', $files));
        }
        if (!is_array($files)) {
            return [];
        }
        $siteUrl = $this->modx->getOption('site_url');
        $baseUrl = '/' . ltrim($this->modx->getOption('base_url', null, '/'), '/');
        $output = array();
        foreach ($files as $file) {
            if (!empty($file) && substr($file, 0, 1) !== '-') {
                // Strip the base path and the site URL, the remaining file is relative to the base URL
                $file = (strpos($file, MODX_BASE_PATH) === 0) ? substr($file, strlen(MODX_BASE_PATH)) : $file;
                $file = ($siteUrl && strpos($file, $siteUrl) === 0) ? substr($file, strlen($siteUrl)) : $file;
                if (preg_match('#https?://#', $file) || strpos($file, '//') === 0) {
                    // Handle external assets
                    if (is_array($externalFiles)) {
                        $externalFiles[] = $file;
                        continue;
                    }
                    if ($type == 'css') {
                        $this->modx->regClientCSS($file);
                    } else {
                        if ($position == 'startup') {
                            $this->modx->regClientStartupScript($file);
                        } else {
                            $this->modx->regClientScript($file);
                        }
                    }
                    if ($this->getOption('debug')) {
                        $this->modx->log(xPDO::LOG_LEVEL_ERROR, 'Can\'t handle the external asset URL ' . $file, '', $this->packageName);
                    }
                    continue;
                }
                if (strpos($file, '/') !== 0) {
                    $file = $baseUrl . $file;
                }
                if ($path = parse_url($file, PHP_URL_PATH)) {
                    $output[] = $path;
                }
            }
        }
        return $output;
    }

    public function getAssetCollection($type, $files, $minify): string
    {
        try {
            if ($this->getOption('debug')) {
                $this->modx->log(xPDO::LOG_LEVEL_ERROR, 'Collected asset collection: ' . print_r($files, true));
            }

            $webrootPath = $this->getWebrootPath();
            $collection = new AssetCollection();
            foreach ($files as $file) {
                // Remove a query string i.e. used for cache busting
                $urlPath = '/' . ltrim(parse_url($file, PHP_URL_PATH) ?: $file, '/');
                $file = $webrootPath . ltrim($urlPath, '/');
                $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                if (!is_file($file)) {
                    $this->modx->log(xPDO::LOG_LEVEL_ERROR, 'The file ' . $file . ' was skipped, it does not exist.', '', $this->packageName);
                    continue;
                }
                if ($this->getOption('debug')) {
                    $this->modx->log(xPDO::LOG_LEVEL_ERROR, 'File added to the asset collection: ' . $file);
                }
                switch ($extension) {
                    case 'js':
                        $collection->add(new FileAsset($file));
                        break;
                    case 'css':
                        $collection->add(new FileAsset($file, [$this->getCssRewriteFilter($urlPath)]));
                        break;
                    case 'scss':
                        $collection->add(new FileAsset($file, [new ScssphpFilter(), $this->getCssRewriteFilter($urlPath)]));
                        break;
                    case 'less':
                        $collection->add(new FileAsset($file, [new LessphpFilter(), $this->getCssRewriteFilter($urlPath)]));
                        break;
                    default:
                        $this->modx->log(xPDO::LOG_LEVEL_ERROR, 'The file ' . $file . ' was skipped, only js, css, scss and less files can be combined.', '', $this->packageName);
                }
            }
            if ($minify) {
                if ($type === 'js') {
                    return $collection->dump(new JavaScriptMinifierFilter());
                } elseif ($type === 'css') {
                    return $collection->dump(new StylesheetMinifyFilter());
                }
            }
            return $collection->dump();
        } catch (Exception $e) {
            $this->modx->log(xPDO::LOG_LEVEL_ERROR, $e->getMessage(), '', $this->packageName);
            return '';
        }

    }

    /**
     * Get a filter, that rewrites the relative URLs in a stylesheet to root-relative URLs.
     *
     * The combined file is located in the cache folder, so the relative URLs
     * of the source stylesheet would point to the wrong location.
     *
     * @param string $urlPath The root-relative URL of the source stylesheet
     * @return CallablesFilter
     */
    protected function getCssRewriteFilter(string $urlPath): CallablesFilter
    {
        $baseUrl = rtrim(dirname($urlPath), '/') . '/';
        return new CallablesFilter(null, function (AssetInterface $asset) use ($baseUrl) {
            $asset->setContent(CssUtils::filterReferences($asset->getContent(), function ($matches) use ($baseUrl) {
                $url = $matches['url'];
                if ($url === '' || preg_match('#^([a-z][a-z0-9+.-]*:|/|\#)#i', $url)) {
                    // absolute, protocol-relative, root-relative, data URI or fragment
                    return $matches[0];
                }
                return str_replace($url, $this->normalizeUrlPath($baseUrl . $url), $matches[0]);
            }));
        });
    }

    /**
     * Resolve the dot segments of an URL path.
     *
     * @param string $url
     * @return string
     */
    protected function normalizeUrlPath(string $url): string
    {
        $suffix = '';
        if (preg_match('/[?#].*$/', $url, $match)) {
            $suffix = $match[0];
            $url = substr($url, 0, -strlen($suffix));
        }
        $segments = [];
        foreach (explode('/', $url) as $segment) {
            if ($segment === '..') {
                if (count($segments) > 1) {
                    array_pop($segments);
                }
            } elseif ($segment !== '.') {
                $segments[] = $segment;
            }
        }
        return implode('/', $segments) . $suffix;
    }

    /**
     * @param string $content
     * @param string $type
     * @param string $hashPrefix
     * @param string $filename Optional filename, the type specific filename option is used if empty
     * @param bool|null $minified Optional state of the minification, the type specific minify option is used if null
     * @return string
     * @throws Exception
     */
    public function saveAssetFile(string $content, string $type, string $hashPrefix, string $filename = '', ?bool $minified = null): string
    {
        $filename = ($filename ?: $this->getOption($type . 'Filename')) . $hashPrefix;
        $extension = ($minified === null) ? $this->getOption($type . 'Ext') : (($minified ? '.min.' : '.') . $type);
        $hash = substr(hash('sha1', $content), 0, $this->getOption('hashLength'));
        $filename = $filename . $hash . $extension;
        $folderPath = $this->getOption('cachePath') . $type . '/';
        $folderUrl = $this->getOption('cacheUrl') . $type . '/';

        $cacheManager = $this->modx->getCacheManager();
        if ($cacheManager->writeTree($folderPath) === false) {
            throw new Exception('Could not create folder ' . $folderPath);
        }
        if ($cacheManager->writeFile($folderPath . $filename, $content) === false) {
            throw new Exception('Could not create file ' . $folderPath . $filename);
        }
        return $folderUrl . $filename;

    }

    public function clearAssetFiles()
    {
        $types = ['js', 'css'];
        foreach ($types as $type) {
            $folderPath = $this->getOption('cachePath') . $type . '/';
            // Match all generated files, the snippet could use a different filename than the system setting
            $filenameRegex = '~_[hbp][0-9a-f]+\.(min\.)?' . $type . '$~';

            try {
                $files = new DirectoryIterator($folderPath);
                foreach ($files as $fileinfo) {
                    if (!$fileinfo->isDot() && preg_match($filenameRegex, $fileinfo->getFilename())) {
                        if (!@unlink($fileinfo->getPathname())) {
                            $this->modx->log(xPDO::LOG_LEVEL_ERROR, 'Could not delete ' . $fileinfo->getPathname(), '', $this->packageName);
                        }
                    }
                }
            } catch (Exception $e) {
                $this->modx->log(xPDO::LOG_LEVEL_ERROR, $folderPath . ' does not exist!', '', $this->packageName);
            }
        }
        $this->modx->cacheManager->refresh([
            'minifyx' => [],
        ]);
    }

    /**
     * Get the filesystem path of the webroot.
     *
     * @return string
     */
    public function getWebrootPath(): string
    {
        $basePath = $this->modx->getOption('base_path');
        $baseUrl = $this->modx->getOption('base_url');
        if ($baseUrl && substr($basePath, -strlen($baseUrl)) === $baseUrl) {
            return substr($basePath, 0, -strlen($baseUrl)) . '/';
        }
        return $basePath;
    }

    /**
     * Check if a local asset URL points to the MinifyX cache folder.
     *
     * @param string $url
     * @return bool
     */
    public function isCacheUrl(string $url): bool
    {
        $cacheUrl = $this->normalizeUrl($this->getOption('cacheUrl'));
        return $cacheUrl !== '' && strpos($this->normalizeUrl($url), $cacheUrl) === 0;
    }

    /**
     * Normalize a local URL to a path relative to the MODX base URL.
     *
     * @param string $url
     * @return string
     */
    protected function normalizeUrl(string $url): string
    {
        $siteUrl = $this->modx->getOption('site_url');
        $baseUrl = $this->modx->getOption('base_url', null, '/');
        $url = trim($url);
        if ($siteUrl && strpos($url, $siteUrl) === 0) {
            $url = substr($url, strlen($siteUrl));
        }
        $url = (string)parse_url($url, PHP_URL_PATH);
        if ($baseUrl !== '/' && strpos($url, $baseUrl) === 0) {
            $url = substr($url, strlen($baseUrl));
        }
        return ltrim($url, '/');
    }
}
