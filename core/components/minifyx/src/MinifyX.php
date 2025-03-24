<?php
/**
 * MinifyX
 *
 * Copyright 2012 by Patrick Nijkamp - https://github.com/b03tz
 * Copyright 2012-2018 by Vasily Naumkin - https://github.com/bezumkin
 * Copyright 2018-2021 by Sergey Shlokov - https://github.com/sergant210
 * Copyright 2021-2025 by Thomas Jakobi <office@treehillstudio.com>
 *
 * @package minifyx
 * @subpackage classfile
 */

namespace TreehillStudio\MinifyX;

use Assetic\Asset\AssetCollection;
use Assetic\Asset\FileAsset;
use Assetic\Filter\JavaScriptMinifierFilter;
use Assetic\Filter\LessphpFilter;
use Assetic\Filter\ScssphpFilter;
use Assetic\Filter\StylesheetMinifyFilter;
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
    public string $version = '2.0.4';

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
        $this->namespace = $this->getOption('namespace', $options, $this->namespace);

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
     * Checks and creates cache folder for storing prepared scripts and styles
     *
     * @return bool
     */
    public function prepareCacheFolder(): bool
    {
        $success = true;

        // Optionally prepend cache path with MODX base path.
        $path = trim($this->getOption('cachePath'));
        if (strpos($path, MODX_BASE_PATH) === 0) {
            $path = substr($path, strlen(MODX_BASE_PATH));
        }
        $path = MODX_BASE_PATH . rtrim($path, '/') . '/';
        $this->options['cachePath'] = $path;

        if (!file_exists($path)) {
            $cacheManager = $this->modx->getCacheManager();
            $success = $cacheManager->writeTree($path);
            $success &= $cacheManager->writeTree($path . '/js/');
            $success &= $cacheManager->writeTree($path . '/css/');
        }
        return $success;
    }

    /**
     * Prepare comma separated string or array of files.
     *
     * @param array|string $files
     * @param string $type Type of files
     * @param string $position The register position of files
     * @return array
     */
    public function prepareFiles($files, string $type = '', string $position = ''): array
    {
        if (is_string($files)) {
            $files = array_map('trim', explode(',', $files));
        }
        if (!is_array($files)) {
            return [];
        }
        $siteUrl = $this->modx->getOption('site_url');
        $output = array();
        foreach ($files as $file) {
            if (!empty($file) && substr($file, 0, 1) !== '-') {
                $file = (strpos($file, MODX_BASE_PATH) === 0) ? substr($file, strlen(MODX_BASE_PATH)) : $file;
                $file = (strpos($file, $siteUrl) === 0) ? substr($file, strlen($siteUrl)) : $file;
                if (preg_match('#https?://#', $file)) {
                    // Handle external assets
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
                    $file = '/' . $file;
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

            if (substr($this->modx->getOption('base_path'), -strlen($this->modx->getOption('base_url'))) === $this->modx->getOption('base_url')) {
                $webrootPath = substr($this->modx->getOption('base_path'), 0, -strlen($this->modx->getOption('base_url'))) . '/';
            } else {
                $webrootPath = $this->modx->getOption('base_path');
            }
            $collection = new AssetCollection();
            foreach ($files as $file) {
                $file = $webrootPath . ltrim($file, '/');
                $extension = pathinfo($file, PATHINFO_EXTENSION);
                if ($this->getOption('debug')) {
                    $this->modx->log(xPDO::LOG_LEVEL_ERROR, 'File added to the asset collection: ' . $file);
                }
                switch ($extension) {
                    case 'js':
                    case 'css':
                        $collection->add(new FileAsset($file));
                        break;
                    case 'scss':
                        $collection->add(new FileAsset($file, array(new ScssphpFilter())));
                        break;
                    case 'less':
                        $collection->add(new FileAsset($file, array(new LessphpFilter())));
                        break;
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
     * @param string $content
     * @param string $type
     * @param string $hashPrefix
     * @return string
     * @throws Exception
     */
    public function saveAssetFile(string $content, string $type, string $hashPrefix): string
    {
        $filename = $this->getOption($type . 'Filename') . $hashPrefix;
        $extension = $this->getOption($type . 'Ext');
        $hash = substr(hash('sha1', $content), 0, $this->getOption('hashLength'));
        $filename = $filename . $hash . $extension;
        $folderPath = $this->getOption('cachePath') . $type . '/';
        $folderUrl = $this->getOption('cacheUrl') . $type . '/';

        $cacheManager = $this->modx->getCacheManager();
        if (!$cacheManager->writeTree($folderPath)) {
            throw new Exception('Could not create folder ' . $folderPath);
        }
        if (!$cacheManager->writeFile($folderPath . $filename, $content)) {
            throw new Exception('Could not create file ' . $folderPath . $filename);
        }
        return $folderUrl . $filename;

    }

    public function clearAssetFiles()
    {
        $types = ['js', 'css'];
        foreach ($types as $type) {
            $folderPath = $this->getOption('cachePath') . $type . '/';
            $filenameRegex = '~' . $this->getOption($type . 'Filename') . '_[hbp]~';

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
}
