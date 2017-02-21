<?php

/** Fallback environment */
if ( ! defined('APPENV')) {
    define('APP_ENV', 'development');  // development|preview|production
}

/** Fallback directory config */
if ( ! defined('DOCROOT')) {
    define('DOCROOT', $_SERVER['DOCUMENT_ROOT']);
}
if ( ! defined('SYSDIR')) {
    define('SYSDIR', __DIR__);
}
if ( ! defined ('APPDIR')) {
    define('APPDIR', realpath(SYSDIR . '/../app'));
}
if ( ! defined('VIEWSDIR')) {
    if (is_dir(APPDIR . '/views')) {
        define('VIEWSDIR', APPDIR . '/views');
    } elseif (is_dir(APPDIR . '/templates')) {
        define('VIEWSDIR', APPDIR . '/templates');
    } else {
        define('VIEWSDIR', APPDIR);
    }
}

/** Handle debug mode */
if ( ! defined('DEBUG_MODE')) {
    define('DEBUG_MODE', false);
}
if (DEBUG_MODE) {
    @ini_set('display_errors', 1);
} else {
    @ini_set('display_errors', 0);
}

// Load basic system functions
require SYSDIR . '/functions/system.php';

// Load helper functions
require SYSDIR . '/functions/helpers.php';

// Load template functions
require SYSDIR . '/functions/template.php';

// Load debug functions
require SYSDIR . '/functions/debug.php';

/**
 * Autoloader for `system` and `app` project base.
 *
 * After registering this autoload function with SPL, the following line
 * would cause the function to attempt to load the \app\controller\Page class
 * from APPDIR/controller/Page.php
 *
 *      new \app\controller\Page();
 *
 * and \ys\main\Image class from SYSDIR/main/Image.php
 *
 *      new \ys\main\Image();
 *
 * @param string $class The fully-qualified class name.
 */
spl_autoload_register(function ($class) {
    $class = trim($class, "\\");
    if (strncmp('ys', $class, 2) === 0) {
        // system class
        $file = SYSDIR . str_replace('\\', '/', substr($class, 2)) . '.php';
    } elseif (strncmp('app', $class, 3) === 0) {
        // app class
        $file = APPDIR . str_replace('\\', '/', substr($class, 3)) . '.php';
    } else {
        // namespace not supported by this autoloader
        return;
    }

    if (file_exists($file)) {
        include_once $file;
    }
});

/** Register routes */
foreach (require APPDIR . '/config/routes.php' as $route => $action) {
    route($route, $action);
}
