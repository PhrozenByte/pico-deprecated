<?php
/**
 * This file is part of Pico. It's copyrighted by the contributors recorded
 * in the version control history of the file, available from the following
 * original location:
 *
 * <https://github.com/picocms/pico-deprecated/blob/master/plugins/PluginApi0Plugin.php>
 *
 * This file was created by splitting up an original file into multiple files,
 * which in turn was previously part of the project's main repository. The
 * version control history of these files apply accordingly, available from
 * the following original locations:
 *
 * <https://github.com/picocms/pico-deprecated/blob/90ea3d5a9767f1511f165e051dd7ffb8f1b3f92e/PicoDeprecated.php>
 * <https://github.com/picocms/Pico/blob/82a342ba445122182b898a2c1800f03c8d16f18c/plugins/00-PicoDeprecated.php>
 *
 * SPDX-License-Identifier: MIT
 * License-Filename: LICENSE
 */

namespace picocms\PicoDeprecated\Plugin;

use picocms\PicoDeprecated\AbstractPluginApiPlugin;
use Pico;
use PicoDeprecated;
use Twig\Environment as TwigEnvironment;

/**
 * Maintains backward compatibility with plugins using API version 0, written
 * for Pico 0.9 and earlier
 *
 * @author  Daniel Rudolf
 * @link    https://picocms.org
 * @license https://opensource.org/licenses/MIT The MIT License
 * @version 3.0
 */
class PluginApi0Plugin extends AbstractPluginApiPlugin
{
    /**
     * This plugin extends {@see PluginApi1Plugin} and {@see ThemeApi0Plugin}
     *
     * @var string[]
     */
    protected $dependsOn = [ PluginApi1Plugin::class, ThemeApi0Plugin::class ];

    /**
     * Map of core events matching event signatures of older API versions
     *
     * @see AbstractPluginApiPlugin::handleEvent()
     *
     * @var array<string,string>
     */
    protected $eventAliases = [
        'onConfigLoaded' =>      [ 'config_loaded' ],
        'onRequestUrl' =>        [ 'request_url' ],
        'onContentLoading' =>    [ 'before_load_content' ],
        'on404ContentLoading' => [ 'before_404_load_content' ],
        'onMetaParsed' =>        [ 'file_meta' ],
        'onContentParsing' =>    [ 'before_parse_content' ],
        'onContentParsed' =>     [ 'after_parse_content', 'content_parsed' ],
        'onTwigRegistration' =>  [ 'before_twig_register' ],
        'onPageRendered' =>      [ 'after_render' ],
    ];

    /**
     * Pico's request file
     *
     * @see Pico::$requestFile
     * @see PluginApi0Plugin::onRequestFile()
     *
     * @var string|null
     */
    protected $requestFile;

    /**
     * Triggers the plugins_loaded event
     *
     * @param object[] $plugins loaded plugin instances
     */
    public function onPluginsLoaded(array &$plugins)
    {
        $this->triggerEvent('plugins_loaded');
    }

    /**
     * Defines various config-related constants and sets the $config global
     *
     * `ROOT_DIR`, `LIB_DIR`, `PLUGINS_DIR`, `THEMES_DIR`, `CONTENT_EXT` and
     * `CACHE_DIR` were removed wih Pico 1.0, `CONTENT_DIR` existed just in
     * Pico 0.9 and `CONFIG_DIR` existed just for a short time between Pico 0.9
     * and Pico 1.0.
     *
     * @param array &$config array of config variables
     */
    public function onConfigLoaded(array &$config)
    {
        $this->defineConfigConstants($config);

        if (!isset($GLOBALS['config'])) {
            $GLOBALS['config'] = &$config;
        }
    }

    /**
     * Defines various config-related constants
     *
     * `ROOT_DIR`, `LIB_DIR`, `PLUGINS_DIR`, `THEMES_DIR`, `CONTENT_EXT` and
     * `CACHE_DIR` were removed wih Pico 1.0, `CONTENT_DIR` existed just in
     * Pico 0.9 and `CONFIG_DIR` existed just for a short time between Pico 0.9
     * and Pico 1.0.
     *
     * @param array &$config array of config variables
     */
    protected function defineConfigConstants(array &$config)
    {
        if (!defined('ROOT_DIR')) {
            define('ROOT_DIR', $this->getPico()->getRootDir());
        }
        if (!defined('CONFIG_DIR')) {
            define('CONFIG_DIR', $this->getPico()->getConfigDir());
        }
        if (!defined('LIB_DIR')) {
            $picoReflector = new \ReflectionClass(Pico::class);
            define('LIB_DIR', dirname($picoReflector->getFileName()) . '/');
        }
        if (!defined('PLUGINS_DIR')) {
            define('PLUGINS_DIR', $this->getPico()->getPluginsDir());
        }
        if (!defined('THEMES_DIR')) {
            define('THEMES_DIR', $this->getPico()->getThemesDir());
        }
        if (!defined('CONTENT_DIR')) {
            define('CONTENT_DIR', $this->getPico()->getConfig('content_dir'));
        }
        if (!defined('CONTENT_EXT')) {
            define('CONTENT_EXT', $this->getPico()->getConfig('content_ext'));
        }
        if (!defined('CACHE_DIR')) {
            $twigConfig = $this->getPico()->getConfig('twig_config');
            define('CACHE_DIR', $twigConfig['cache'] ?: '');
        }
    }

    /**
     * Sets PluginApi0Plugin::$requestFile
     *
     * @see PluginApi0Plugin::$requestFile
     *
     * @param string &$file absolute path to the content file to serve
     */
    public function onRequestFile(&$file)
    {
        $this->requestFile = &$file;
    }

    /**
     * Triggers the after_404_load_content event
     *
     * @param string &$rawContent raw file contents
     */
    public function on404ContentLoaded(&$rawContent)
    {
        $this->triggerEvent('after_404_load_content', [ &$this->requestFile, &$rawContent ]);
    }

    /**
     * Triggers the after_load_content event
     *
     * @param string &$rawContent raw file contents
     */
    public function onContentLoaded(&$rawContent)
    {
        $this->triggerEvent('after_load_content', [ &$this->requestFile, &$rawContent ]);
    }

    /**
     * Triggers the before_read_file_meta event
     *
     * @param string   &$rawContent raw file contents
     * @param string[] &$headers    list of known meta header fields
     */
    public function onMetaParsing(&$rawContent, array &$headers)
    {
        $this->triggerEvent('before_read_file_meta', [ &$headers ]);
    }

    /**
     * Triggers the get_page_data event
     *
     * @param array &$pageData data of the loaded page
     */
    public function onSinglePageLoaded(array &$pageData)
    {
        $this->triggerEvent('get_page_data', [ &$pageData, $pageData['meta'] ]);
    }

    /**
     * Triggers the get_pages event
     *
     * Please note that the `get_pages` event gets `$pages` passed without a
     * array index. The index is rebuild later using either the `id` array key
     * or is derived from the `url` array key. If it isn't possible to derive
     * the array key, `~unknown` is being used. Duplicates are prevented by
     * adding `~dup` when necessary.
     *
     * @param array[]    &$pages        sorted list of all known pages
     * @param array|null &$currentPage  data of the page being served
     * @param array|null &$previousPage data of the previous page
     * @param array|null &$nextPage     data of the next page
     */
    public function onPagesLoaded(
        array &$pages,
        array &$currentPage = null,
        array &$previousPage = null,
        array &$nextPage = null
    ) {
        // remove keys of pages array
        $plainPages = [];
        foreach ($pages as &$plainPageData) {
            $plainPages[] = &$plainPageData;
        }

        // trigger event
        $this->triggerEvent('get_pages', [ &$plainPages, &$currentPage, &$previousPage, &$nextPage ]);

        // re-index pages array
        $baseUrl = $this->getPico()->getBaseUrl();
        $baseUrlLength = strlen($baseUrl);
        $urlRewritingEnabled = $this->getPico()->isUrlRewritingEnabled();

        $pages = [];
        foreach ($plainPages as &$pageData) {
            if (!isset($pageData['id'])) {
                if (substr_compare($pageData['url'], $baseUrl, 0, $baseUrlLength) === 0) {
                    if ($urlRewritingEnabled && (substr_compare($pageData['url'], '?', $baseUrlLength, 1) === 0)) {
                        $pageData['id'] = substr($pageData['url'], $baseUrlLength + 1);
                    } else {
                        $pageData['id'] = substr($pageData['url'], $baseUrlLength);
                    }
                } else {
                    // foreign URLs are indexed by ~unknown, ~unknown~dup1, ~unknown~dup2, …
                    $pageData['id'] = '~unknown';
                }
            }

            // prevent duplicates
            $id = $pageData['id'];
            for ($i = 1; isset($pages[$id]); $i++) {
                $id = $pageData['id'] . '~dup' . $i;
            }

            $pages[$id] = &$pageData;
        }
    }

    /**
     * Triggers the before_render event
     *
     * Please note that the `before_render` event gets `$templateName` passed
     * without its file extension. The file extension is re-added later.
     *
     * @param TwigEnvironment &$twig          Twig instance
     * @param string          &$templateName  file name of the template
     * @param array           &$twigVariables template variables
     */
    public function onPageRendering(TwigEnvironment &$twig, array &$twigVariables, &$templateName)
    {
        $templateNameInfo = pathinfo($templateName) + [ 'extension' => '' ];

        // the template name hasn't had a file extension in API v0
        $templateName = $templateNameInfo['filename'];

        $this->triggerEvent('before_render', [ &$twigVariables, &$twig, &$templateName ]);

        // recover original file extension
        // we assume that all templates of a theme use the same file extension
        $templateName = $templateName . '.' . $templateNameInfo['extension'];
    }

    /**
     * {@inheritDoc}
     */
    public function handleCustomEvent($eventName, array $params = [])
    {
        // never trigger custom events
    }

    /**
     * {@inheritDoc}
     */
    public function triggerEvent($eventName, array $params = [])
    {
        // we don't support compat plugins using API v0, so no need to take care of compat plugins here
        // API v0 events are also triggered on plugins using API v1 (but not later)
        $plugins = $this->getPicoDeprecated()->getPlugins(PicoDeprecated::API_VERSION_0);
        $plugins += $this->getPicoDeprecated()->getPlugins(PicoDeprecated::API_VERSION_1);

        foreach ($plugins as $plugin) {
            if (method_exists($plugin, $eventName)) {
                call_user_func_array([ $plugin, $eventName ], $params);
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getApiVersion()
    {
        return PicoDeprecated::API_VERSION_1;
    }

    /**
     * {@inheritDoc}
     */
    public function getApiVersionSupport()
    {
        return PicoDeprecated::API_VERSION_0;
    }
}
