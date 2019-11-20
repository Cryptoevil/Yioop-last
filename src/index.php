<?php
/**
 * SeekQuarry/Yioop --
 * Open Source Pure PHP Search Engine, Crawler, and Indexer
 *
 * Copyright (C) 2009 - 2019  Chris Pollett chris@pollett.org
 *
 * LICENSE:
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * END LICENSE
 *
 * Main web interface entry point for Yioop!
 * search site. Used to both get and display
 * search results. Also used for inter-machine
 * communication during crawling
 *
 * @author Chris Pollett chris@pollett.org
 * @license https://www.gnu.org/licenses/ GPL3
 * @link https://www.seekquarry.com/
 * @copyright 2009 - 2019
 * @filesource
 */
namespace seekquarry\yioop;

use seekquarry\yioop\configs as C;
use seekquarry\yioop\library as L;

/**
 * Main entry point to the Yioop web app.
 *
 * Initialization is done in  a function to avoid polluting the global
 * namespace with variables.
 * @param object $web_site
 * @param bool $start_new_session whether to start a session or not
 */
function bootstrap($web_site = null, $start_new_session = true)
{
    //check if mobile css and formatting should be used or not
    if (isset($_SERVER['HTTP_USER_AGENT'])) {
        $agent = $_SERVER['HTTP_USER_AGENT'];
        if ((stristr($agent, "mobile") || stristr($agent, "fennec")) &&
            !stristr($agent, "ipad") ) {
            $_SERVER["MOBILE"] = true;
        } else {
            $_SERVER["MOBILE"] = false;
        }
    } else {
        $_SERVER["MOBILE"] = false;
    }
    /**
     * Did we come to this index.php from ../index.php? If so, rewriting
     * must be on
     */
    if (!C\nsdefined("REDIRECTS_ON")) {
        C\nsdefine("REDIRECTS_ON", false);
    }
    /**
     * Check if doing url rewriting, and if so, do initial routing
     */
    configureRewrites($web_site);
    if ((C\DEBUG_LEVEL & C\ERROR_INFO) == C\ERROR_INFO) {
        set_error_handler(C\NS_CONFIGS . "yioop_error_handler");
    }
    /**
     * Load global functions related to localization
     */
    require_once __DIR__ . "/library/LocaleFunctions.php";
    ini_set("memory_limit","500M");
    if (!empty($web_site)) {
        if ((empty($_REQUEST['c']) || $_REQUEST['c'] != 'resource')) {
            $web_site->header("X-FRAME-OPTIONS: DENY"); //prevent click-jacking
        }
        $web_site->header("X-Content-Type-Options: nosniff"); /*
        Let browsers know that we should be setting the mimetype correctly --
        For non dumb browsers this should help prevent against XSS attacks
        to images containing HTML. Also, might help against PRSSI attacks.
        */
        if ($start_new_session) {
            $options = ['name' => C\SESSION_NAME];
            $web_site->sessionStart($options);
        }
    }
    /**
     * Load global functions related to checking Yioop! version
     */
    require_once C\BASE_DIR."/library/UpgradeFunctions.php";
    if (!function_exists('mb_internal_encoding')) {
        echo "PHP Zend Multibyte Support must be enabled for Yioop! to run.";
        exit();
    }
    /**
     * Make an initial setting of controllers. This can be overridden in
     * configs/LocalConfig.php
     */
    $available_controllers = ["admin", "api", "archive",  "cache",
        "classifier", "crawl", "fetch", "group", "jobs", "machine", "resource",
        "search", "static", "tests"];
    if (function_exists(C\NS_CONFIGS . "localControllers")) {
        $available_controllers = array_merge($available_controllers,
            C\localControllers());
    }
    if (in_array(C\REGISTRATION_TYPE, ['no_activation', 'email_registration',
        'admin_activation'])) {
        $available_controllers[] = "register";
    }
    if (!C\WEB_ACCESS) {
        $available_controllers = ["admin", "archive", "cache", "crawl",
            "fetch", "jobs", "machine"];
    }
    //the request variable c is used to determine the controller
    if (!isset($_REQUEST['c'])) {
        $controller_name = "search";
        if (C\nsdefined('LANDING_PAGE') && C\LANDING_PAGE &&
            !isset($_REQUEST['q'])) {
            $controller_name = "static";
            $_REQUEST['c'] = "static";
            $_REQUEST['p'] = "Main";
        }
    } else {
        $controller_name = $_REQUEST['c'];
    }
    if (!in_array($controller_name, $available_controllers))
    {
        if (C\WEB_ACCESS) {
            $controller_name = "search";
        } else {
            $controller_name = "admin";
        }
    }
    // if no profile exists we force the page to be the configuration page
    if (!C\PROFILE || (C\nsdefined("FIX_NAME_SERVER") && C\FIX_NAME_SERVER)) {
        $controller_name = "admin";
    }
    $locale_tag = L\getLocaleTag();
    if (C\PROFILE && L\upgradeDatabaseWorkDirectoryCheck()) {
        /**
         * Load global functions needed to upgrade between versions
         * (note only do this if need to upgrade)
         */
        require_once C\BASE_DIR . "/library/VersionFunctions.php";
        L\upgradeDatabaseWorkDirectory();
    }
    if (C\PROFILE && L\upgradeLocalesCheck($locale_tag)) {
        L\upgradeLocales();
        /* upgrade manipulations might mess with global local,
            so set it back here
         */
        L\setLocaleObject($locale_tag);
    }
    /**
     * Loads controller responsible for calculating
     * the data needed to render the scene
     *
     */
    $controller_class = C\NS_CONTROLLERS . ucfirst($controller_name) .
        "Controller";
    $controller = new $controller_class($web_site);
    $controller->processRequest();
}
/**
 * Used to setup and handles url rewriting for the Yioop Web app
 *
 * Developers can add new routes by creating a Routes class in
 * the app_dir with a static method getRoutes which should return
 * an associating array of incoming_path => handler function
 * @param object $web_site
 */
function configureRewrites($web_site)
{
    $route_map = [
        'advertise' => 'routeDirect',
        'blog' => 'routeBlog',
        'bot' => 'routeDirect',
        'privacy' => 'routeDirect',
        'terms' => 'routeDirect',
        'admin' => 'routeController',
        'register' => 'routeController',
        'tests' => 'routeController',
        'trending' => 'routeTrending',
        's' => "routeSubsearch",
        'suggest' => 'routeSuggest',
        'group' => 'routeFeeds',
        'thread' => 'routeFeeds',
        'user' => 'routeFeeds',
        'p' => 'routeWiki'
    ];
    if (class_exists(C\NS. "Routes")) {
        $route_map = array_merge($route_map, Routes::getRoutes());
    }
    /**
     * Check for paths of the form index.php/something which yioop doesn't
     * support
     */
    $s_name = $_SERVER['SCRIPT_NAME'] . "/";
    $path_name = substr($_SERVER["REQUEST_URI"], 0, strlen($s_name));
    if (strcmp($path_name, $s_name) == 0) {
        $_SERVER["PATH_TRANSLATED"] = C\BASE_DIR;
        $script_info = pathinfo($s_name);
        $_SERVER["PATH_INFO"] = ($script_info["dirname"] == "/") ? "" :
            $script_info["dirname"] ;
        $error = directUrl("error");
        $web_site->header("Location: $error");
        L\webExit();
    }
    if (!isset($_SERVER["PATH_INFO"])) {
        $_SERVER["PATH_INFO"] = ".";
    }
    if (!C\REDIRECTS_ON) {
        return;
    }
    /**
     * Now look for and handle routes
     */
    $index_php = "index.php";
    if ((php_sapi_name() == 'cli')) {
        $script_path = "/";
    } else {
        $script_path = substr($_SERVER['PHP_SELF'], 0, -strlen($index_php));
    }
    $request_script = "";
    if (empty($_SERVER['QUERY_STRING'])) {
        $request_script = rtrim(
            substr($_SERVER['REQUEST_URI'], strlen($script_path)), "?");
    } else {
        $q_pos = strpos($_SERVER['REQUEST_URI'], "?");
        if ($q_pos !== false) {
            $request_script = substr($_SERVER['REQUEST_URI'], 0,
                $q_pos);
        }
        $request_script = substr($request_script, strlen($script_path));
    }
    $request_script = ($request_script == "") ? $index_php : $request_script;
    if (in_array($request_script, ['', '/', $index_php])) {
        return;
    }
    $request_parts = explode("/", $request_script);
    $handled = false;
    if (isset($route_map[$request_parts[0]])) {
        if (empty($_REQUEST['c']) || $_REQUEST['c'] == $request_parts[0]) {
            $route = C\NS . $route_map[$request_parts[0]];
            $handled = $route($request_parts);
        } else if (!empty($_REQUEST['c'])) {
            $handled = true;
        }
    }
    if (!$handled) {
        $route_args = ["error"];
        routeDirect($route_args);
    }
}
/**
 * Used to route page requests to pages that are fixed Public Group wiki
 * that should always be present. For example, 404 page.
 *
 * @param array $route_args of url parts (split on slash).
 * @return bool whether was able to compute a route or not
 */
function routeDirect($route_args)
{
    $_REQUEST['route']['c'] = true;
    $_REQUEST['c'] = "static";
    $_REQUEST['route']['p'] = true;
    $_REQUEST['p'] = $route_args[0];
    return true;
}
/**
 * Given the name of a fixed public group static page creates the url
 * where it can be accessed in this instance of Yioop, making use of the
 * defined variable REDIRECTS_ON.
 *
 * @param string $name of static page
 * @param bool $with_delim whether it should be terminated with nothing or
 *      ? or &
 * @return string url for the page in question
 */
function directUrl($name, $with_delim = false)
{
    if (C\REDIRECTS_ON) {
        $delim = ($with_delim) ? "?" : "";
        return C\BASE_URL . $name . $delim;
    } else {
        $delim = ($with_delim) ? "&" : "";
        return C\BASE_URL . "$name.php$delim";
    }
}
/**
 * Used to route page requests to for the website's public blog
 *
 * @param array $route_args of url parts (split on slash).
 * @return bool whether was able to compute a route or not
 */
function routeBlog($route_args)
{
    $_REQUEST['route']['c'] = true;
    $_REQUEST['c'] = "group";
    $_REQUEST['route']['a'] = true;
    $_REQUEST['a'] = "groupFeeds";
    $_REQUEST['route']['just_group_id'] = true;
    $_REQUEST['just_group_id'] = 2;
    return true;
}
/**
 * Used to route page requests for pages corresponding to a group, user,
 * or thread feed. If redirects on then urls ending with /feed_type/id map
 * to a page for the id'th item of that feed_type
 *
 * @param array $route_args of url parts (split on slash).
 * @return bool whether was able to compute a route or not
 */
function routeFeeds($route_args)
{
    $handled = true;
    if (isset($route_args[1]) && $route_args[1] == intval($route_args[1])) {
        $_REQUEST['c'] = "group";
        if (!empty($route_args[2])) {
            $_REQUEST['a'] = 'wiki';
            if ($route_args[2] == 'pages') {
                $_REQUEST['arg'] = 'pages';
                $_REQUEST['route']['arg'] = true;
            } else {
                if (empty($_REQUEST['page_name'])) {
                    $_REQUEST['page_name'] = $route_args[2];
                    $_REQUEST['route']['page_name'] = true;
                }
                if (empty($_REQUEST['sf']) && !empty($route_args[3]) ) {
                    $rest = array_slice($route_args, 3);
                    $_REQUEST['sf'] = implode("/", $rest);
                    $_REQUEST['route']['sf'] = true;
                }
            }
        }
        $_REQUEST['a'] = (isset($_REQUEST['a']) &&
            $_REQUEST['a'] == 'wiki') ? $_REQUEST['a'] : "groupFeeds";
        $_REQUEST['route']['c'] = true;
        $_REQUEST['route']['a'] = true;
        $end = ($route_args[0] == 'thread') ? "" : "_id";
        if ($_REQUEST['a'] == 'wiki') {
            $_REQUEST['group_id'] = $route_args[1];
            $_REQUEST['route']['group_id'] = true;
        } else {
            $just_id = "just_" . $route_args[0] . $end;
            $_REQUEST[$just_id] = $route_args[1];
            $_REQUEST['route'][$just_id] = true;
        }
    } else if (!isset($route_args[1])) {
        $_REQUEST['c'] = "group";
        $_REQUEST['a'] = (isset($_REQUEST['a']) &&
            $_REQUEST['a'] == 'wiki') ? $_REQUEST['a'] : "groupFeeds";
        $_REQUEST['route']['c'] = true;
        $_REQUEST['route']['a'] = true;
    } else {
        $handled = false;
    }
    return $handled;
}
/**
 * Given the type of feed, the identifier of the feed instance, and which
 * controller is being used creates the url where that feed item can be
 * accessed from the instance of Yioop. It makes use of the
 * defined variable REDIRECTS_ON.
 *
 * @param string $type of feed: group, user, thread
 * @param int $id the identifier for that feed.
 * @param bool $with_delim whether it should be terminated with nothing or
 *      ? or &
 * @param string $controller which controller is being used to access the
 *      feed: usuall admin or group
 * @return string url for the page in question
 */
function feedsUrl($type, $id, $with_delim = false, $controller = "group")
{
    if (C\REDIRECTS_ON && $controller == 'group') {
        $delim = ($with_delim) ? "?" : "";
        $path = ($type == "") ? "group" : "$type/$id";
        return C\BASE_URL ."$path$delim";
    } else {
        $delim = ($with_delim) ? "&" : "";
        $begin = (C\REDIRECTS_ON && $controller == "admin") ?
            "admin?" : "?c=$controller&";
        $query = "{$begin}a=groupFeeds";
        $end = ($type == 'thread') ? "" : "_id";
        if ($type != "") {
            if ($begin == "admin?" && $type == "group") {
                $query = "admin/$id";
                $delim = "?";
            } else {
                $query .= "&just_{$type}$end=$id";
            }
        }
        return C\BASE_URL . "$query$delim";
    }
}
/**
 * Used to route requests to the trending page page.
 * If redirects on, then /trending routes to this trending page.
 *
 * @param array $route_args of url parts (split on slash).
 * @return bool whether was able to compute a route or not
 */
function routeTrending($route_args)
{
    $_REQUEST['c'] = "search";
    $_REQUEST['a'] = "trending";
    $_REQUEST['route']['c'] = true;
    $_REQUEST['route']['a'] = true;
    return true;
}
/**
 * Used to route page requests to end-user controllers such as
 * register, admin. urls ending with /controller_name will
 * be routed to that controller.
 *
 * @param array $route_args of url parts (split on slash).
 * @return bool whether was able to compute a route or not
 */
function routeController($route_args)
{
    $_REQUEST['c'] = $route_args[0];
    $_REQUEST['route']['c'] = true;
    if (isset($route_args[1]) && intval($route_args[1]) == $route_args[1]) {
        if (isset($_REQUEST['a']) && $_REQUEST['a'] == 'wiki') {
            $_REQUEST['group_id'] = $route_args[1];
        } else if (!empty($route_args[2])) {
            $_REQUEST['a'] = 'wiki';
            $_REQUEST['group_id'] = $route_args[1];
            if ($route_args[2] == 'pages') {
                $_REQUEST['arg'] = 'pages';
                $_REQUEST['route']['arg'] = true;
            } else {
                $_REQUEST['page_name'] = $route_args[2];
                if (empty($_REQUEST['sf']) && !empty($route_args[3]) ) {
                    $rest = array_slice($route_args, 3);
                    $_REQUEST['sf'] = implode("/", $rest);
                    $_REQUEST['route']['sf'] = true;
                }
                $_REQUEST['route']['page_name'] = true;
            }
            $_REQUEST['route']['page_name'] = true;
            $_REQUEST['route']['a'] = true;
        } else {
            $_REQUEST['a'] = 'groupFeeds';
            $_REQUEST['just_group_id'] = $route_args[1];
        }
        $_REQUEST['route']['group_id'] = true;
    }
    return true;
}
/**
 * Given the name of a controller for which an easy end-user link is useful
 * creates the url where it can be accessed on this instance of Yioop,
 * making use of the defined variable REDIRECTS_ON. Examples of end-user
 * controllers would be the admin, and register controllers.
 *
 * @param string $name of controller
 * @param bool $with_delim whether it should be terminated with nothing or
 *      ? or &
 * @return string url for the page in question
 */
function controllerUrl($name, $with_delim = false)
{
    if (C\REDIRECTS_ON) {
        $delim = ($with_delim) ? "?" : "";
        $_REQUEST['route']['c'] = true;
        return C\BASE_URL . $name . $delim;
    } else {
        $delim = ($with_delim) ? "&" : "";
        return C\BASE_URL . "?c=$name$delim";
    }
}
/**
 * Used to route page requests for subsearches such as news, video, and images
 * (site owner can define other). Urls of the form /s/subsearch will
 * go the page handling the subsearch.
 *
 * @param array $route_args of url parts (split on slash).
 * @return bool whether was able to compute a route or not
 */
function routeSubsearch($route_args)
{
    $handled = true;
    if (isset($route_args[1])) {
        $_REQUEST['route']['c'] = true;
        $_REQUEST['route']['s'] = true;
        $_REQUEST['c'] = "search";
        $_REQUEST['s'] = $route_args[1];
    } else {
        $handled = false;
    }
    return $handled;
}
/**
 * Given the name of a subsearch  creates the url where it can be accessed
 * on this instance of Yioop, making use of the defined variable REDIRECTS_ON.
 * Examples of subsearches include news, video, and images. A site owner
 * can add to these and delete from these.
 *
 * @param string $name of subsearch
 * @param bool $with_delim whether it should be terminated with nothing or
 *      ? or &
 * @return string url for the page in question
 */
function subsearchUrl($name, $with_delim = false)
{
    if (C\REDIRECTS_ON) {
        $delim = ($with_delim) ? "?" : "";
        return C\BASE_URL ."s/$name$delim";
    } else {
        $delim = ($with_delim) ? "&" : "";
        return C\BASE_URL . "?s=$name$delim";
    }
}
/**
 * Used to route requests for the suggest-a-url link on the tools page.
 * If redirects on, then /suggest routes to this suggest-a-url page.
 *
 * @param array $route_args of url parts (split on slash).
 * @return bool whether was able to compute a route or not
 */
function routeSuggest($route_args)
{
    $_REQUEST['c'] = "register";
    $_REQUEST['a'] = "suggestUrl";
    return true;
}
/**
 * Return the url for the suggest-a-url link on the more tools page, making use
 * of the defined variable REDIRECTS_ON.
 *
 * @param bool $with_delim whether it should be terminated with nothing or
 *      ? or &
 * @return string url for the page in question
 */
function suggestUrl($with_delim = false)
{
    if (C\REDIRECTS_ON) {
        $_REQUEST['route']['c'] = true;
        $_REQUEST['route']['a'] = true;
        $delim = ($with_delim) ? "?" : "";
        return C\BASE_URL ."suggest$delim";
    } else {
        $delim = ($with_delim) ? "&" : "";
        return C\BASE_URL . "?c=register&a=suggestUrl$delim";
    }
}
/**
 * Used to route page requests for pages corresponding to a wiki page of
 * group. If it is a wiki page for the public group viewed without being
 * logged in, the route might come in as yioop_instance/p/page_name if
 * redirects are on. If it is for a non-public wiki or page accessed with
 * logged in the url will look like either:
 * yioop_instance/group/group_id?a=wiki&page_name=some_name
 * or
 * yioop_instance/admin/group_id?a=wiki&page_name=some_name&csrf_token_string
 *
 * @param array $route_args of url parts (split on slash).
 * @return bool whether was able to compute a route or not
 */
function routeWiki($route_args)
{
    $handled = true;
    if (isset($route_args[1])) {
        if ($route_args[1] == 'pages') {
            $_REQUEST['c'] = "group";
            $_REQUEST['a'] = 'wiki';
            $_REQUEST['arg'] = 'pages';
            $_REQUEST['route']['c'] = true;
            $_REQUEST['route']['a'] = true;
            $_REQUEST['route']['arg'] = true;
        } else {
            $_REQUEST['c'] = "static";
            $_REQUEST['p'] = $route_args[1];
            $_REQUEST['route']['c'] = true;
            $_REQUEST['route']['p'] = true;
            if (empty($_REQUEST['sf']) && !empty($route_args[2]) ) {
                $rest = array_slice($route_args, 2);
                $_REQUEST['sf'] = implode("/", $rest);
                $_REQUEST['route']['sf'] = true;
            }
        }
    } else {
        $handled = false;
    }
    return $handled;
}
/**
 * Given the name of a wiki page, the group it belongs to, and which
 * controller is being used creates the url where that feed item can be
 * accessed from the instance of Yioop. It makes use of the
 * defined variable REDIRECTS_ON.
 *
 * @param string $name of wiki page
 * @param bool $with_delim whether it should be terminated with nothing or
 *      ? or &
 * @param string $controller which controller is being used to access the
 *      feed: usually static (for the public group), admin, or group
 * @param int $id the group the wiki page belongs to
 * @return string url for the page in question
 */
function wikiUrl($name, $with_delim = false, $controller = "static", $id =
    C\PUBLIC_GROUP_ID)
{
    $q = ($with_delim) ? "?" : "";
    $a = ($with_delim) ? "&" : "";
    $is_static = ($controller == "static");
    if (C\REDIRECTS_ON) {
        $q = ($with_delim) ? "?" : "";
        if ($is_static) {
            if ($name == "") {
                $name = "Main";
            }
            return C\BASE_URL ."p/$name$q";
        } else {
            $page = ($name== "") ? "?a=wiki$a" : "/$name$q";
            return C\BASE_URL .
                $controller . "/$id$page";
        }
    } else {
        $delim = ($with_delim) ? "&" : "";
        if ($name == 'pages') {
            if ($is_static) {
                $controller = "group";
            }
            return  C\BASE_URL .
                "?c=$controller&a=wiki&arg=pages&group_id=$id$a";
        } else {
            if ($is_static) {
                if ($name == "") {
                    $name = "main";
                }
                return C\BASE_URL . "?c=static&p=$name$a";
            } else {
                $page = ($name== "") ? "" : "&page_name=$name";
                return C\BASE_URL .
                    "?c=$controller&a=wiki&group_id=$id$page$a";
            }
        }
    }
}
if (php_sapi_name() != 'cli' &&
    (empty($web_site) &&
    !defined("seekquarry\\yioop\\configs\\REDIRECTS_ON"))) {
    /**
     * For error function and yioop constants if we are in non-cli
     * non-redirects situation
     */
    require_once __DIR__ . "/library/Utility.php";
    $web_site =  new L\WebSite();
    bootstrap($web_site);
}
