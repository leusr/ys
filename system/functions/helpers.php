<?php
use ys\main\Router;

/**
 * Environment is development?
 * @return bool
 */
function isDev() {
    return defined('APP_ENV') && APP_ENV === 'development';
}

/**
 * Environment is production?
 * @return bool
 */
function isProd() {
    if ( ! defined('APP_ENV')) {
        return true;
    }

    return APP_ENV === 'production';
}

/**
 * Detect if url is external
 * @param string $url
 * @return bool
 */
function isExternal($url) {
    vd(parse_url($url, PHP_URL_HOST));

    $host = parse_url($url, PHP_URL_HOST);
    if (empty($host)) {
        return false;
    }

    return $_SERVER['HTTP_HOST'] !== $host;
}

/**
 * Detect if url is absolute
 * (with 'http' or '//' at the beginning)
 * @param string $url
 * @return bool
 */
function isAbsolute($url) {
    return substr($url, 0, 2) === '//' || substr($url, 0, 4) === 'http';
}

/**
 * Redirect
 * @param string     $location Url to jump
 * @param int|string $status   HTTP status code (default: 302 - found)
 */
function redirect($location, $status = 302) {
    if (is_string($status)) {
        if ( ! is_numeric($status)) {
            trigger_error("Invalid status code <b>$status</b> on redirect.", E_USER_ERROR);
        }
        $status = (int)$status;
    }

    if (substr($location, 0, 4) !== 'http' && substr($location, 0, 1) !== '/') {
        $location = '/' . $location;
    }

    http_response_code($status);
    header("Location: $location");
    exit;
}

/**
 * Print out 404 page.
 * (Actually parse 404 route, and call it's action.)
 */
function error404() {
    $Router = Router::getInstance();

    list($controller, $method) = $Router->parseAction($Router->getRoute('404'));

    $controller = controllerNS($controller);
    with(new $controller)->$method();
}

/**
 * Make slug from string
 * @param string $str
 * @return string
 */
function slug($str) {
    $str = (mb_strlen($str, 'UTF-8') > 150) ? substr($str, 0, 149) : $str;

    $str = mb_strtolower($str, 'UTF-8');

    $str = str_replace(['ö', 'ü', 'ó', 'ő', 'ú', 'é', 'á', 'ű', 'í'], ['o', 'u', 'o', 'o', 'u', 'e', 'a', 'u', 'i'],
                       $str);

    $str = html_entity_decode($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    $str = strip_tags($str);

    $str = preg_replace('/[^a-z0-9-]/', '-', $str);  // Replace illegals with dashes

    $str = preg_replace('/-{2,}/', '-', $str);  // Replace multiple dashes with one

    $str = trim($str, '-');  // Remove the leading/trailing dash

    return $str;
}

/**
 * Format bytes
 * @param int $size
 * @return string
 */
function formatBytes($size) {
    $units = ['byte', 'kb', 'mb', 'gb', 'tb', 'pb'];

    if ($size < 1024) {
        return $size . ' ' . $units[0];
    }

    for ($i = 1; $i < count($units); $i++) {
        if ($size < pow(1024, $i + 1)) {
            return round($size / pow(1024, $i), 1) . ' ' . $units[$i];
        }
    }

    // This will never happen.
    return "Googolplex in the power of Graham's number bytes";
}

/**
 * Format microtime
 * @param float $sec
 * @return string
 */
function formatSeconds($sec) {
    $units = ['s', 'ms', 'µs'];

    for ($i = 0; $i < count($units); $i++) {
        $time = $sec * pow(1000, $i);

        if ($time > 1) {
            return round($time, 1) . ' ' . $units[$i];
        }
    }

    // This will never happen.
    return '0.00 µs (or some time-travelling negative value)';
}

/**
 * Secure hash script with random hash.
 * @param string      $data
 * @param string|null $encdata
 * @return mixed  The hashed data or false.
 */
function shash($data, $encdata = null) {
    if (CRYPT_BLOWFISH !== 1) {
        trigger_error('This server not supports the required type of crypt.', E_USER_NOTICE);

        return false;
    }

    $strength = '08';  // 04-31

    // If $encdata, get the data and the salt and check against it
    if (isset($encdata)) {
        $mixed = strrev($encdata);
        $salt = '';
        $encdata = '';
        for ($i = 0; $i < 22; $i++) {
            $salt .= substr($mixed, $i * 3, 1);
            $encdata .= substr($mixed, $i * 3 + 1, 2);
        }
        $encdata = '$2a$' . $strength . '$' . $encdata . substr($mixed, 65 + 1);

        return $encdata === crypt($data, '$2a$' . $strength . '$' . $salt) ? true : false;
    } else {
        // Create random salt, crypt the data with it and mix it into the hash
        $salt = '';
        for ($i = 0; $i < 22; $i++) {
            $salt .= substr('./ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789', mt_rand(0, 63), 1);
        }
        $encdata = substr(crypt($data, '$2a$' . $strength . '$' . $salt), 7);
        $mixed = '';
        for ($i = 0; $i < 22; $i++) {
            $mixed .= substr($salt, $i, 1) . substr($encdata, $i * 2, 2);
        }
        $mixed .= substr($encdata, 44);

        return strrev($mixed);
    }
}

/**
 * Insert or return with `.min` depending on SCRIPT_DEBUG and APP_ENV constants.
 *
 * If $src was given insert `.min` before the extension, or leave it as is.
 * If no $src return with `.min` or empty string.
 *
 * @param null|string $src
 * @return string
 */
function dotmin($src = null) {
    $isDebug = defined('SCRIPT_DEBUG') && true === SCRIPT_DEBUG;

    if ( ! isset($src)) {
        return $isDebug ? "" : '.min';
    }

    if ($isDebug) {
        return $src;
    }

    $path = pathinfo($src);

    return $path['dirname'] . '/' . $path['filename'] . '.min.' . $path['extension'];
}
