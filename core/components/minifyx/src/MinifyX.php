<?php
/**
 * minifyx
 *
 * Copyright 2008-2011 by Florian Wobbe - www.eprofs.de
 * Copyright 2011-2022 by Thomas Jakobi <office@treehillstudio.com>
 *
 * @package minifyx
 * @subpackage classfile
 */

namespace TreehillStudio\MinifyX;

use Assetic\Asset\AssetCollection;
use Assetic\Asset\FileAsset;
use Assetic\Filter\CoffeeScriptFilter;
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
    public $modx;

    /**
     * The namespace
     * @var string $namespace
     */
    public $namespace = 'minifyx';

    /**
     * The package name
     * @var string $packageName
     */
    public $packageName = 'MinifyX';

    /**
     * The version
     * @var string $version
     */
    public $version = '2.0.0';

    /**
     * The class options
     * @var array $options
     */
    public $options = [];

    /**
     * @var array
     */
    public $groups = array();

    /**
     * @var array
     */
    protected $sources;

    /**
     * Content of the current processed file
     * @var string
     */
    protected $_content;

    /**
     * Name of the current processed file
     * @var string
     */
    protected $_filename;

    /**
     * Type of the processed files
     * @var string
     */
    protected $_filetype;

    /**
     * List of all cached files
     * @var array $cachedFiles
     */
    protected $cachedFiles = array();

    /**
     * @var array $parameters
     */
    protected $parameters = array('jsGroups', 'cssGroups', 'jsSources', 'cssSources', 'hooks', 'preHooks');

    /**
     * MinifyX constructor
     *
     * @param modX $modx A reference to the modX instance.
     * @param array $options An array of options. Optional.
     */
    public function __construct(modX &$modx, $options = [])
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
            'cacheFolder' => $this->modx->getOption($this->namespace . '.cacheFolder', null, $assetsPath . 'cache/', true),
            'cacheUrl' => $this->modx->getOption($this->namespace . '.cacheFolder', null, $assetsUrl . 'cache/', true),
            'jsGroups' => '',
            'cssGroups' => '',
            'jsSources' => '',
            'cssSources' => '',
            'cssFilename' => 'styles',
            'jsFilename' => 'scripts',
            'minifyJs' => (bool)$this->getOption('minifyJs', $options, false),
            'minifyCss' => (bool)$this->getOption('minifyCss', $options, false),
            'registerCss' => 'default',
            'registerJs' => 'default',
            'jsPlaceholder' => 'MinifyX.javascript',
            'cssPlaceholder' => 'MinifyX.css',
            'forceUpdate' => $this->modx->context->key == 'mgr',
            'forceDelete' => $this->modx->getOption($this->namespace . '.forceDelete', null, false),
            'munee_cache' => MODX_CORE_PATH . 'cache/default/munee/',
            'hash_length' => 10,
            'hooksPath' => MODX_CORE_PATH . 'components/minifyx/hooks/',
            'hooks' => '',
            'preHooks' => '',
            'jsTpl' => '<script src="[[+file]]"></script>',
            'cssTpl' => '<link rel="stylesheet" href="[[+file]]" type="text/css">',
            'version' => '',
            'jsExt' => $this->modx->getOption($this->namespace . '.minifyJs', $options, false) ? '.min.js' : '.js',
            'cssExt' => $this->modx->getOption($this->namespace . '.minifyCss', $options, false) ? '.min.css' : '.css',
        ]);

        $test = $this->modx->getOption($this->namespace . '.cacheFolder', null, $assetsPath . 'cache/', true);

        $this->processParams();
        if (file_exists(MODX_CORE_PATH . 'components/minifyx/config/groups.php')) {
            $this->groups = include MODX_CORE_PATH . 'components/minifyx/config/groups.php';
        }
        if ($this->prepareCacheFolder()) {
            $this->cachedFiles = $this->getCachedFiles();
        } else {
            $this->modx->log(xPDO::LOG_LEVEL_ERROR, '[MinifyX] Could not create cache dir "' . $this->getOption('cacheFolderPath') . '"');
        }

        $lexicon = $this->modx->getService('lexicon', 'modLexicon');
        $lexicon->load($this->namespace . ':default');
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
    public function getOption($key, $options = [], $default = null)
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
     * Set a local configuration option.
     *
     * @param string $key The option key to search for.
     * @param mixed $value The option value.
     */
    public function setOption($key, $value)
    {
        $this->options[$key] = $value;
    }

    /**
     * Explode snippet parameters to an array
     */
    protected function processParams()
    {
        foreach ($this->parameters as $source) {
            $this->setOption($source, $this->explodeParam($this->getOption($source)));
        }
    }

    /**
     * Explode a comma separated string parameter to an array.
     *
     * @param $param
     * @return array
     */
    protected function explodeParam($param)
    {
        if (is_string($param)) {
            $param = (!empty($param)) ? array_map('trim', explode(',', $param)) : array();
        }
        return $param;
    }

    /**
     * Checks and creates cache dir for storing prepared scripts and styles
     *
     * @return bool
     */
    public function prepareCacheFolder()
    {
        $success = true;
        $path = trim($this->getOption('cacheFolder'));
        if (strpos(MODX_BASE_PATH, $path) === 0) {
            $path = substr($path, strlen(MODX_BASE_PATH));
        }
        $path = rtrim($path, '/') . '/';
        if (!file_exists(MODX_BASE_PATH . $path)) {
            $cacheManager = $this->modx->getCacheManager();
            $success = $cacheManager->writeTree(MODX_BASE_PATH . $path);
        }
        $this->setOption('cacheFolderPath', MODX_BASE_PATH . $path);
        return $success;
    }

    /**
     * Recursive create of directories by specified path
     *
     * @param string path
     *
     * @return bool
     */
    public function makeDir($path = '')
    {
        if (empty($path)) {
            return false;
        } elseif (file_exists($path)) {
            return true;
        }

        $base = (strpos($path, MODX_CORE_PATH) === 0) ? MODX_CORE_PATH : MODX_BASE_PATH;
        $tmp = explode('/', str_replace($base, '', $path));
        $path = $base;
        foreach ($tmp as $v) {
            if (!empty($v)) {
                $path .= $v . '/';
                if (!file_exists($path)) {
                    mkdir($path);
                }
            }
        }
        return file_exists($path);
    }

    /**
     * Get the latest cached files for current options
     *
     * @param string $prefix
     * @param string $extension
     *
     * @return array
     */
    public function getCachedFiles($prefix = '', $extension = '')
    {
        $cached = array();
        $regexp = $prefix . '[a-z0-9]{' . $this->getOption('hash_length') . '}.*';
        if (!empty($extension)) {
            $regexp .= '?' . str_replace('.', '\.', $extension);
        }
        $files = scandir($this->getOption('cacheFolderPath'));
        foreach ($files as $file) {
            if (preg_match("/$regexp/i", $file, $matches)) {
                $cached[] = $file;
            }
        }
        return $cached;
    }

    /**
     * Reset the config and prepare an optional config array
     *
     * @param array $config
     */
    public function reset(array $config = array())
    {
        $this->_filename = $this->_content = '';
        foreach ($this->parameters as $source) {
            $this->setOption($source, '');
        }
        $this->setConfig($config);
        $this->processParams();
    }

    /**
     * Set new config.
     *
     * @param array $config
     */
    public function setConfig(array $config = array())
    {
        $this->options = (is_array($config) && !empty($config)) ? array_merge($this->options, $config) : $this->options;
    }

    /**
     * Get a specified group.
     *
     * @param string $group
     * @return array
     */
    public function getGroup($group)
    {
        return $this->groups[$group] ?? array();
    }

    /**
     * Add a group or groups of files.
     *
     * @param string $type
     * @param mixed $group
     */
    public function addGroups($type, $group)
    {
        if (!empty($group)) {
            if (!is_array($group)) {
                $group = $this->explodeParam($group);
            }
            $this->setOption($type, array_merge($this->getOption($type), $group));
        }
    }

    /**
     * Get the stored groups of files.
     *
     * @param string $type
     * @param string $group
     * @return mixed
     */
    public function getGroups($type, $group = '')
    {
        return (!empty($group)) ? $this->getOption($type)[$group] : $this->getOption($type);
    }

    /**
     * Replace the stored groups of files with new ones.
     *
     * @param string $type
     * @param mixed $group
     */
    public function setGroups($type, $group)
    {
        if (!is_array($group)) {
            $group = $this->explodeParam($group);
        }
        $this->setOption($type, $group);
    }

    /**
     * Add a group or groups of javascript files.
     *
     * @param $group
     */
    public function addJsGroup($group)
    {
        $this->addGroups('jsGroups', $group);
    }

    /**
     * Get the stored groups of javascript files.
     *
     * @param string $group
     * @return mixed
     */
    public function getJsGroup($group = '')
    {
        return $this->getGroups('jsGroups', $group);
    }

    /**
     * Replace the stored groups of javascript files with new ones.
     *
     * @param $group
     */
    public function setJsGroup($group)
    {
        $this->setGroups('jsGroups', $group);
    }

    /**
     * Add a group or groups of stylesheet files.
     *
     * @param $group
     */
    public function addCssGroup($group)
    {
        $this->addGroups('cssGroups', $group);
    }

    /**
     * Get the stored groups of stylesheet files.
     *
     * @param null $group
     * @return mixed
     */
    public function getCssGroup($group = null)
    {
        return $this->getGroups('cssGroups', $group);
    }

    /**
     * Replace the stored groups of stylesheet files with new ones.
     *
     * @param $group
     */
    public function setCssGroup($group)
    {
        $this->setGroups('cssGroups', $group);
    }

    /**
     * Add javascript sources.
     *
     * @param $source
     */
    public function addJsSource($source)
    {
        $this->addGroups('jsSources', $source);
    }

    /**
     * Get the stored sources of javascript files.
     *
     * @param $source
     * @return mixed
     */
    public function getJsSource($source)
    {
        return $this->getGroups('jsSources', $source);
    }

    /**
     * Replace the stored sources of javascript files with new ones.
     *
     * @param $source
     */
    public function setJsSource($source)
    {
        $this->setGroups('jsSources', $source);
    }

    /**
     * Add stylesheet sources.
     *
     * @param $source
     */
    public function addCssSource($source)
    {
        $this->addGroups('cssSources', $source);
    }

    /**
     * Get the stored sources of stylesheet files.
     *
     * @param $source
     * @return mixed
     */
    public function getCssSource($source)
    {
        return $this->getGroups('cssSources', $source);
    }

    /**
     * Replace the stored sources of stylesheet files with new ones.
     *
     * @param $source
     */
    public function setCssSource($source)
    {
        $this->setGroups('cssSources', $source);
    }

    /**
     * Check if the process file is javascript file.
     *
     * @param string $file
     * @return bool
     */
    public function isJs($file = null)
    {
        $file = $file ?: $this->_filename;
        return isset($file) ? pathinfo($file, PATHINFO_EXTENSION) == 'js' : false;
    }

    /**
     * Check if the process file is stylesheet file.
     *
     * @param string $file
     * @return bool
     */
    public function isCss($file = null)
    {
        $file = $file ?: $this->_filename;
        return isset($file) ? pathinfo($file, PATHINFO_EXTENSION) == 'css' : false;
    }

    /**
     * Removes cache files
     *
     * @return bool
     */
    public function clearCache()
    {
        if ($this->prepareCacheFolder()) {
            if ($this->getOption('forceDelete')) {
                foreach (new DirectoryIterator($this->getOption('cacheFolderPath')) as $file) {
                    if ($file->isFile()) {
                        unlink($file->getPathname());
                    }
                }
            } else {
                foreach ($this->cachedFiles as $file) {
                    unlink($this->getOption('cacheFolderPath') . $file);
                }
            }
            $this->cachedFiles = array();
        }
        if ($dir = $this->getTmpDir()) {
            return $this->removeDir($dir);
        }
        return false;
    }

    /**
     * Prepares and returns path to temporary directory for storing Munee cache
     *
     * @return bool
     */
    public function getTmpDir()
    {
        $dir = str_replace('//', '/', $this->getOption('munee_cache'));
        if ($this->makeDir($dir)) {
            return $dir;
        } else {
            return false;
        }
    }

    /**
     * Recursive remove of a directory
     *
     * @param string $dir
     *
     * @return bool
     */
    public function removeDir($dir)
    {
        $dir = rtrim($dir, '/');
        if (is_dir($dir)) {
            $list = scandir($dir);
            foreach ($list as $file) {
                if ($file[0] == '.') {
                    continue;
                } elseif (is_dir($dir . '/' . $file)) {
                    $this->removeDir($dir . '/' . $file);
                } else {
                    @unlink($dir . '/' . $file);
                }
            }
        }
        @rmdir($dir);
        return !file_exists($dir);
    }

    /**
     * Activate/Deactivate minify
     *
     * @param bool $value
     * @return $this
     */
    public function minify($value = true)
    {
        $this->setOption('minifyJs', (bool)$value);
        $this->setOption('minifyCss', (bool)$value);
        return $this;
    }

    /**
     * @param string $path
     * @return $this
     */
    public function cacheFolder($path)
    {
        if (!empty($path)) {
            $this->setOption('cacheFolder', $path);
            if (!$this->prepareCacheFolder()) {
                $this->modx->log(1, 'Can\'t create the cache folder "' . $this->getOption('cacheFolderPath') . '"!');
            };
        }
        return $this;
    }

    /**
     * @param $key
     * @param $parameters
     * @return $this
     */
    public function __call($key, $parameters)
    {
        if (isset($this->config[$key])) {
            $this->config[$key] = (in_array($key, $this->parameters)) ? $this->explodeParam($parameters[0]) : $parameters[0];
        }
        return $this;
    }

//    /**
//     * @return array|string|string[]
//     */
//    public function __toString()
//    {
//        return $this->run();
//    }
//

    /**
     * Prepare an array of css and js files.
     * @return array
     */
    public function prepareSources()
    {
        $js = $css = array();
        $this->processHooks($this->getOption('preHooks'));
        foreach ($this->getOption('jsGroups') as $group) {
            if (isset($this->groups[$group])) {
                $js = array_merge($js, $this->groups[$group]);
            }
        }
        foreach ($this->getOption('cssGroups') as $group) {
            if (isset($this->groups[$group])) {
                $css = array_merge($css, $this->groups[$group]);
            }
        }
        $js = array_unique(array_merge($js, $this->getOption('jsSources')));
        $css = array_unique(array_merge($css, $this->getOption('cssSources')));
        $js = array_map(array($this, 'parseUrl'), $js);
        $css = array_map(array($this, 'parseUrl'), $css);
        return $this->sources = compact('js', 'css');
    }

    /**
     * Process specified hooks.
     * @param $hooks
     */
    protected function processHooks($hooks)
    {
        foreach ($hooks as $hook) {
            $modx = $this->modx;
            if (preg_match('#\.php$#', $hook)) {
                $MinifyX = $this;
                if (file_exists($this->getOption('hooksPath') . $hook)) {
                    include $this->getOption('hooksPath') . $hook;
                }
            } else {
                $modx->runSnippet($hook, array('MinifyX' => $this));
            }
        }
    }

    /**
     * Prepare string or array of files for Munee.
     *
     * @param array|string $files
     * @param string $type Type of files
     * @return array
     */
    public function prepareFiles($files, $type = '')
    {
        if (is_string($files)) {
            $files = array_map('trim', explode(',', $files));
        }
        if (!is_array($files)) {
            return [];
        }
        $site_url = $this->modx->getOption('site_url');
        $this->_filetype = $type;
        $output = array();
        foreach ($files as $file) {
            if (!empty($file) && $file[0] !== '-') {
                $file = (strpos($file, MODX_BASE_PATH) === 0) ? substr($file, strlen(MODX_BASE_PATH)) : $file;
                $file = (strpos($file, $site_url) === 0) ? substr($file, strlen($site_url)) : $file;
                if (preg_match('#https?://#', $file)) {
                    $this->modx->log(xPDO::LOG_LEVEL_ERROR, 'Can\'t handle the external asset URL ' . $file);
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
            $collection = new AssetCollection();
            foreach ($files as $file) {
                $file = MODX_BASE_PATH . ltrim($file, '/');
                $extension = pathinfo($file, PATHINFO_EXTENSION);
                switch ($extension) {
                    case 'js':
                    case 'css':
                        $collection->add(new FileAsset($file));
                        break;
                    case 'coffee':
                        $collection->add(new FileAsset($file, array(new CoffeeScriptFilter())));
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
            $this->modx->log(xPDO::LOG_LEVEL_ERROR, $e->getMessage());
            return '';
        }

    }

    /**
     * Save data in cache file
     *
     * @param $data
     *
     * @return bool|string
     */
    public function saveFile($data)
    {
        $filename = $this->getOption($this->_filetype . 'Filename');
        if (pathinfo($filename, PATHINFO_EXTENSION) == $this->_filetype) {
            $this->_filename = $filename;
            if (file_exists($this->getFilePath())) {
                $this->cachedFiles[] = $this->_filename;
            }
        } else {
            $extension = $this->getOption($this->_filetype . 'Ext');
            $hash = substr(hash('sha1', $this->getContent()), 0, $this->getOption('hash_length'));
            $this->_filename = $filename . '_' . $hash . $extension;
        }
        $this->setContent($data);
        $this->processHooks($this->getOption('hooks'));
        if (empty($this->_filename)) {
            return false;
        }
        $tmp = array_flip($this->cachedFiles);
        if (!isset($tmp[$this->_filename]) || $this->getOption('forceUpdate')) {
            if (!file_put_contents($this->getFilePath(), $this->getContent())) {
                $this->modx->log(xPDO::LOG_LEVEL_ERROR, '[MinifyX] Could not save cache file ' . $this->getOption('cacheFolderPath') . $this->_filename);
                return false;
            }
            if (!isset($tmp[$this->_filename])) {
                $this->cachedFiles[] = $this->_filename;
            }
        }
        return file_exists($this->getFilePath());
    }

    /**
     * Get path of the cache file.
     *
     * @param string $file
     * @return string
     */
    public function getFilePath($file = null)
    {
        if (is_null($file)) {
            $file = $this->getFilename();
        }
        return $this->getOption('cacheFolderPath') . $file;
    }

    /**
     * Get filename of the processed file.
     *
     * @return string
     */
    public function getFilename()
    {
        return $this->_filename;
    }

    /**
     * Set a filename for the processed file.
     * @param string $name
     * @return string
     */
    public function setFilename($name)
    {
        return $this->_filename = $name;
    }

    /**
     * Get content of the processed file.
     * @return string
     */
    public function getContent()
    {
        return $this->_content;
    }

    /**
     * Set new content.
     * @param string $content
     * @return string
     */
    public function setContent($content)
    {
        return $this->_content = $content;
    }

    /**
     * Get url of the cache file.
     * @param string $file
     * @return string
     */
    public function getFileUrl($file = null)
    {
        if (is_null($file)) {
            $file = $this->getFilename();
        }
        return $this->getOption('cacheFolder') . $file;
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        $version = '';
        if (!empty($this->getOption('version'))) {
            $version = '?v=' . (($this->getOption('version') == 'auto') ? hash('crc32b', $this->getContent()) : $this->getOption('version'));
        }
        return $version;
    }

    /**
     * @param string $url
     * @return string
     */
    protected function parseUrl($url)
    {
        $url = str_replace(array('[[+', '{', '}'), array('[[++', '[[++', ']]'), $url);
        $this->modx->getParser()->processElementTags('', $url, false, false, '[[', ']]', array(), 1);
        return $url;
    }
}
