<?php
/**
 * @package minifyx
 * @subpackage plugin
 */

namespace TreehillStudio\MinifyX\Plugins\Events;

use TreehillStudio\MinifyX\Plugins\Plugin;

class OnWebPagePrerender extends Plugin
{
    public function process()
    {
        $time = microtime(true);
        // Process scripts and styles
        if ($this->modx->getOption('minifyx_process_registered', null, false, true)) {
            $current = array(
                'head' => $this->modx->sjscripts,
                'body' => $this->modx->jscripts,
            );
            $included = $excluded = $prepared = $raw = array(
                'head' => array('css' => array(), 'js' => array(), 'html' => array()),
                'body' => array('css' => array(), 'js' => array(), 'html' => array()),
            );
            $exclude = $this->modx->getOption('minifyx_exclude_registered');

            // Split all scripts and styles by type
            foreach ($current as $key => $value) {
                foreach ($value as $v) {
                    if (preg_match('/<(?:link|script).*?(?:href|src)=[\'|"](.*?)[\'|"]/', $v, $tmp)) {
                        if (strpos($tmp[1], '.css') !== false) {
                            if (!empty($exclude) && preg_match($exclude, $tmp[1])) {
                                $excluded[$key]['css'][] = $tmp[1];
                            }
                            else {
                                $included[$key]['css'][] = $tmp[1];
                            }
                        }
                        if (strpos($tmp[1], '.js') !== false) {
                            if (!empty($exclude) && preg_match($exclude, $tmp[1])) {
                                $excluded[$key]['js'][] = $tmp[1];
                            }
                            else {
                                $included[$key]['js'][] = $tmp[1];
                            }
                        }
                    }
                    elseif (strpos($v, '<script') !== false) {
                        $raw[$key]['js'][] = trim(preg_replace('#<!--.*?-->(\n|)#s', '', $v));
                    }
                    elseif (strpos($v, '<style') !== false) {
                        $raw[$key]['css'][] = trim(preg_replace('#/\*.*?\*/(\n|)#s', '', $v));
                    }
                    else {
                        $excluded[$key]['html'][] = $v;
                    }
                }
            }

            // Main options for MinifyX
            $scriptProperties = array(
                'cacheFolder' => $this->modx->getOption('minifyx_cacheFolder', null, '/assets/components/minifyx/cache/', true),
                'forceUpdate' => $this->modx->getOption('minifyx_forceUpdate', null, false, true),
                'minifyJs' => $this->modx->getOption('minifyx_minifyJs', null, false, true),
                'minifyCss' => $this->modx->getOption('minifyx_minifyCss', null, false, true),
                'jsFilename' => $this->modx->getOption('minifyx_jsFilename', null, 'all', true),
                'cssFilename' => $this->modx->getOption('minifyx_cssFilename', null, 'all', true),
            );
            /** @var MinifyX $MinifyX */
            if (isset($this->modx->minifyx) && $this->modx->minifyx instanceof MinifyX) {
                $MinifyX = $this->modx->minifyx;
                $MinifyX->reset($scriptProperties);
            } else {
                $MinifyX = $this->modx->getService('minifyx', 'MinifyX', MODX_CORE_PATH . 'components/minifyx/model/minifyx/', $scriptProperties);
            }
            if (!$MinifyX->prepareCacheFolder()) {
                $this->modx->log(modX::LOG_LEVEL_ERROR, '[MinifyX] Could not create cache dir "'.$scriptProperties['cacheFolderPath'].'"');
                return;
            }
            //$cacheFolderUrl = $MinifyX->config['cacheFolder'];

            // Process raw scripts and styles
            $tmp_dir = $MinifyX->getTmpDir() . 'resources/' . $this->modx->resource->id . '/';
            foreach ($raw as $key => $value) {
                foreach ($value as $type => $rows) {
                    $tmp = '';
                    if ($type == 'css' && $this->modx->getOption('minifyx_processRawCss', null, false, true) ||
                        $type == 'js' && $this->modx->getOption('minifyx_processRawJs', null, false, true)) {

                        $text = '';
                        foreach ($rows as $text) {
                            $text = preg_replace('#^<(script|style).*?>#', '', $text);
                            $text = preg_replace('#</(script|style)>$#', '', $text);
                            $tmp .= $text;
                        }

                        if (!empty($tmp)) {
                            $file = sha1($tmp) . '.' . $type;
                            if (!file_exists($tmp_dir . $file)) {
                                if (!file_exists($tmp_dir)) {
                                    $MinifyX->makeDir($tmp_dir);
                                }
                                file_put_contents($tmp_dir . $file, $tmp);
                            }
                            $included[$key][$type][] = $tmp_dir . $file;
                            $raw[$key][$type] = array();
                        }
                    }
                }
            }

            // Combine and minify files
            foreach ($included as $key => $value) {
                foreach ($value as $type => $files) {
                    if (empty($files)) {continue;}
//					$filename = $MinifyX->config[$type.'Filename'] . '_';
//					$extension = $MinifyX->config[$type.'Ext'];
                    $files = $MinifyX->prepareFiles($files, $type);
                    $properties = array(
                        'minify' => $MinifyX->config['minify'.ucfirst($type)]
                            ? 'true'
                            : 'false',
                    );

                    $result = $MinifyX->Munee($files, $properties);
                    if ($MinifyX->saveFile($result)) {
                        $prepared[$key][$type][] = $MinifyX->getFileUrl();
                    }
                }
            }

            // Combine files by type
            $final = array(
                'head' => array_merge(
                    $excluded['head']['css'], $prepared['head']['css'], $raw['head']['css'],
                    $excluded['head']['js'], $prepared['head']['js'], $raw['head']['js']
                ),
                'body' => array_merge(
                    $excluded['body']['css'], $prepared['body']['css'], $raw['body']['css'],
                    $excluded['body']['js'], $prepared['body']['js'], $raw['body']['js']
                ),
            );

            // Push files to tags
            foreach ($final as $type => &$value) {
                foreach ($value as &$file) {
                    if (strpos($file, '<script') === false && strpos($file, '<style') === false) {
                        $file = preg_match('/\.css$/iu', $file)
                            ? '<link rel="stylesheet" href="' . $file . '" type="text/css" />'
                            : '<script type="text/javascript" src="' . $file . '"></script>';
                    }
                }
                if (!empty($excluded[$type]['html'])) {
                    $value[] = implode("\n", $excluded[$type]['html']);
                }
            }
            unset($value);

            // Replace tags in web page
            $this->modx->resource->_output = str_replace(
                array($this->modx->getRegisteredClientStartupScripts() . "\n</head>", $this->modx->getRegisteredClientScripts() . "\n</body>"),
                array(implode("\n", $final['head']) . "\n</head>", implode("\n", $final['body']) . "\n</body>"),
                $this->modx->resource->_output
            );
        }
        // Process images
        if ($this->modx->getOption('minifyx_process_images', null, false, true)) {
            if (!$this->modx->getService('minifyx','MinifyX', MODX_CORE_PATH.'components/minifyx/model/minifyx/')) {return false;}

            $connector = $this->modx->getOption('minifyx_connector', null, '/assets/components/minifyx/munee.php', true);
            $exclude = $this->modx->getOption('minifyx_exclude_images');
            $replace = array('from' => array(), 'to' => array());
            $site_url = $this->modx->getOption('site_url');
            $default = $this->modx->getOption('minifyx_images_filters', null, '', true);

            preg_match_all('/<img.*?>/i', $this->modx->resource->_output, $tags);
            foreach ($tags[0] as $tag) {
                if (preg_match($exclude, $tag)) {
                    continue;
                }
                elseif (preg_match_all('/(src|height|width|filters)=[\'|"](.*?)[\'|"]/i', $tag, $properties)) {
                    if (count($properties[0]) >= 2) {
                        $file = $connector . '?files=';
                        $resize = '';
                        $filters = '';
                        $tmp = array('from' => array(), 'to' => array());

                        foreach ($properties[1] as $k => $v) {
                            if ($v == 'src') {
                                $src = $properties[2][$k];
                                if (strpos($src, '://') !== false) {
                                    if (strpos($src, $site_url) !== false) {
                                        $src = str_replace($site_url, '', $src);
                                    }
                                    else {
                                        // Image from 3rd party domain
                                        continue;
                                    }
                                }
                                $file .= $src;
                                $tmp['from']['src'] = $properties[2][$k];
                            }
                            elseif ($v == 'height' || $v == 'width') {
                                $resize .=  $v[0] . '['.$properties[2][$k].']';
                            }
                            elseif ($v == 'filters') {
                                $filters .= $properties[2][$k];
                                $tmp['from']['filters'] = $properties[0][$k];
                                $tmp['to']['filters'] = '';
                            }
                        }

                        if (!empty($tmp['from']['src'])) {
                            $resize .= isset($tmp['from']['filters'])
                                ? $filters
                                : $default;
                            $tmp['to']['src'] = $file . '?resize=' . $resize;

                            ksort($tmp['from']);
                            ksort($tmp['to']);

                            $replace['from'][] = $tag;
                            $replace['to'][] = str_replace($tmp['from'], $tmp['to'], $tag);
                        }
                    }
                }
            }

            if (!empty($replace)) {
                $this->modx->resource->_output = str_replace(
                    $replace['from'],
                    $replace['to'],
                    $this->modx->resource->_output
                );
            }
        }
        // Minify the page content
        if ($this->modx->getOption('minifyx_minifyHtml', null, false)) {
            $output = $this->modx->resource->_output;
            $replace = [
                '/<!--[^\[](.*?)[^\]]-->/s' => '',
                "/<\?php/"                  => '<?php ',
                "/\n([\S])/"                => ' $1',
                "/\n([\S])/"                => '$1',
                "/>\n</"                    => '><',
                "/>\s+\n</"                 => '><',
                "/>\n\s+</"                 => '><',
                "/\r/"                      => '',
                "/\n/"                      => '',
                "/\t/"                      => ' ',
                '/ +/'                      => ' ',
                "/\t/"                      => '',
                "/ +/"                      => ' ',
            ];
            $output = preg_replace(array_keys($replace), array_values($replace), $output);
            $this->modx->resource->_output = $output;
        }

        $this->modx->log(modX::LOG_LEVEL_INFO, '[MinifyX] Total time for page "'.$this->modx->resource->id.'" = '.(microtime(true) - $time));
    }
}
