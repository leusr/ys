<?php

use ys\main\Image;
use ys\main\Router;

/**
 * Escape HTML special chars.
 * @param string $value
 * @return string
 */
function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8', false);
}

/**
 * Escape all weird characters to HTML entities.
 * @param string $value
 * @return string
 */
function ent($value) {
    return htmlentities((string)$value, ENT_QUOTES, 'UTF-8', false);
}

/**
 * Get resized image url.
 * @formatter:off
 * @param string $path   Path of the original image.
 *                       Relative from base path defined in Image class,
 *                       or may set by constructor or setBasePath setter.
 *
 *                       Additional params:
 *                       1: string|int|null  A predefined named size, or the (max) width value.
 *                       2: int|null         The (max) height value.
 *                       3: bool             The crop flag.
 *
 *                       Some examples:
 *                       img('some.jpg', 'thumb');         // Resize to predefined `thumb` size.
 *                       img('some.jpg', 400, 300);        // Original ratio within max-width=400 and max-height=300.
 *                       img('some.jpg', 400, 300, true);  // Exact 400x300.
 *                       img('some.jpg', null, 400);       // Orginal ratio, width no matter, height=400.
 *
 * @return string  The url of resized or original image
 * @formatter:on
 */
function img($path) {
    if (func_num_args() < 2) {
        return $path;
    }

    $img = new Image;

    // Quick config
    $img->setJpegQuality(90)->switchInterlace()->setThumbDir(config('general.img.thumbdir'));

    $img->set($path);

    if (is_string($name = func_get_arg(1))) {
        return $img->sizename($name)->url();
    }

    $params = func_get_args();
    array_shift($params);

    return $img->size($params)->url();
}

/**
 * The same as above, but instead of saving image
 * send it to http output.
 * @param string $path
 */
function show($path) {
    $img = new Image;

    // Quick config
    $img->setJpegQuality(90)->switchInterlace()->setThumbDir(config('general.img.thumbdir'));

    $img->set($path);

    if (is_string($name = func_get_arg(1))) {
        $img->sizename($name)->show();
    } else {
        $params = func_get_args();
        array_shift($params);

        $img->size($params)->show();
    }

    unset($img);
}

/**
 * Include an svg icon from configured path
 * or fallback default to DOCROOT/assets/icons
 * @param string $name
 * @return string
 */
function svg($name) {
    if (is_null($path = config('general.path.svg'))) {
        $path = DOCROOT . '/assets/icons';
    }

    $path = realpath("{$path}/{$name}.svg");

    if ( ! $path) {
        trigger_error("Misiing {$name}.svg. File not found.", E_USER_WARNING);

        return null;
    }

    return file_get_contents($path);
}

/**
 * Insert svg use xlink:href tag
 * @param string $id
 * @param string $url
 * @return string
 */
function svguse($id, $url = "") {
    return '<svg class="' . $id . '"><use xlink:href="' . $url . '#' . $id . '"></use></svg>';
}

/**
 * Return blank.gif base64 data
 * @return string
 */
function blank() {
    return 'data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==';
}

/**
 * Get data for creating links from registered routes
 * @return array
 */
function routes() {
    $Router = Router::getInstance();
    $routes = [];
    foreach ($Router->getRoutes() as $pattern => $action) {
        $action = $Router->parseAction($action);

        $routes[] = [
                'href'       => baseurl() . $pattern,
                'pattern'    => $pattern,
                'controller' => $action[0],
                'method'     => $action[1],
                'namespace'  => "ys\\controller",
                'filepath'   => SYSDIR . DIRECTORY_SEPARATOR . 'controller' . DIRECTORY_SEPARATOR . $action[0]
                        . '.php',
        ];
    }

    return $routes;
}
