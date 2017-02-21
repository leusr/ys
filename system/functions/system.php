<?php

use ys\main\Config;
use ys\main\Router;
use ys\main\View;

/**
 * This function calls the controller actually.
 */
function run() {
    list($controller, $method) = parsereq();
    with(new $controller)->$method();

    flushsettings();
}

/**
 * Parse request, and return with controller class name
 * with proper namespace prefix, and optional the method name.
 *
 * @return array  0: (string) class, 1: (string) method
 */
function parsereq() {
    $Router = Router::getInstance();
    $request = $Router->parseRequest();

    pr($request);

    list($controller, $method) = $request;

    return [controllerNS($controller), $method];
}

/**
 * Cascade controllers - search for controller in APPDIR,
 * and change the namespace if found.
 *
 * @param string $controller Controller name
 * @return string Controller with namespace
 */
function controllerNS($controller) {
    $ns = 'ys';
    if (is_file(APPDIR . "/controller/$controller.php")) {
        $ns = 'app';
    }

    return "\\" . $ns . "\\controller\\$controller";
}

/**
 * Get config value
 * @param string $key
 * @return mixed
 */
function config($key) {
    $Config = Config::getInstance();

    return $Config->getConfig($key);
}

/**
 * Get/set value from/to settings
 * @param string $key
 * @param mixed  $data
 * @return mixed
 */
function setting($key, $data = null) {
    $Config = Config::getInstance();

    return $Config->setting($key, $data);
}

/**
 * Store changes in files, or jut quick return if nothing changed.
 */
function flushsettings() {
    $Config = Config::getInstance();

    $Config->flushSettings();
}

/**
 * Identity function for chainable objects.
 * @param object $obj
 * @return object
 */
function with($obj) {
    return $obj;
}

/**
 * Get from cache
 * @param string $name
 * @param int    $expire
 * @return false|string
 */
function getcache($name, $expire = 0) {
    $path = SYSDIR . '/storage/cache/' . md5($name);

    if ( ! is_file($path)) {
        return false;
    }

    if ($expire !== 0) {
        if (filemtime($path) < time() - $expire) {
            return false;
        }
    }

    return file_get_contents($path);
}

/**
 * Put to cache
 * @param string $name
 * @param string $content
 */
function putcache($name, $content) {
    file_put_contents(SYSDIR . '/storage/cache/' . md5($name), $content);
}

/**
 * Add a new route
 * @param string $route
 * @param string $controller
 */
function route($route, $controller) {
    $Router = Router::getInstance();

    $Router->addRoute($route, $controller);
}

/**
 * Get baseurl
 * @return string
 */
function baseurl() {
    $Router = Router::getInstance();

    return $Router->getBaseUrl();
}

/**
 * Get subdir
 * @return string
 */
function subdir() {
    $Router = Router::getInstance();

    return $Router->getSubDir();
}

/**
 * Get an url segment by its index
 * (starting with zero)
 * @param int $index
 * @return string
 */
function segment($index) {
    $Router = Router::getInstance();

    return $Router->getSegment($index);
}

/**
 * Create new view instance with given arguments
 * @return View
 */
function view() {
    if (func_num_args() < 2) {
        return new View(func_get_arg(0));
    }

    return new View(func_get_arg(0), func_get_arg(1));
}

/**
 * Include view in another view
 */
function incview() {
    $View = new View(func_get_arg(0));

    if (func_num_args() > 1) {
        $View->addData(func_get_arg(1));
    }

    $View->inc();

    unset($View);
}
