<?php namespace ys\main;

/*
|--------------------------------------------------------------------------
| Router
|--------------------------------------------------------------------------
|
| This class analyse and normalize the given url, detect if installation
| is in domain root, or in a subdirectory. The parseRequest() detects the
| controller class and its targeted method (if any) to action. A method
| can be set with `@` sign. If the route not define a method, it will be
| the `index`.
|
| Native parameter passing support is not impemented, but there are two
| handy function to this: getSegment($i) and getSegments(), with these
| it is possible to access url parts in the controllers.
|
| (!) Required routes: 'home' and '404'.
|     These two routes *must* have an action associaciated with.
|
| Wildcars:
|     :word       - A-Za-z, hypen (-) and underscore (_)
|     :num        - 0-9 and hypen (-)
|     ()          - Optional segment. e.g.: 'big-list(/page-:num)'
|
*/

class Router {
    /**
     * Singleton pattern, contains self object.
     * @var Router
     */
    protected static $instance = null;
    /**
     * Default routes. Can overwrite via addRoute() method.
     * @var array
     */
    private $defaultRoutes
            = [
                    'home' => 'DefaultPageController',
                    '404'  => 'ErrorPageController@notFound',
            ];
    /**
     * route => action pairs
     * @var array
     */
    private $routes;
    /**
     * Holds the subdir name (if any), without leading or trailing slash.
     * At domain root the subdir will be empty string.
     * @var string
     */
    private $subdir;
    /**
     * Holds the base url depending on subdir. If subdir is empty,
     * baseurl will be a single slash `/`. Otherwise `/sub/dir/`.
     * @var string
     */
    private $baseurl;
    /**
     * Requested url without subdir and leading or trailing slash.
     * @var string
     */
    private $url;
    /**
     * Url segments (without subdir).
     * @var array
     */
    private $segments;

    /**
     * Constructor
     */
    protected function __construct() {
        $this->routes = $this->defaultRoutes;
        $this->detectSubDir();
        $this->detectBaseUrl();
        $this->initUrlProcess();
    }

    /**
     * Detect subdir name
     */
    private function detectSubDir() {
        $phpself = $_SERVER['PHP_SELF'];
        $lastseg = substr($phpself, 0, strrpos($phpself, '/'));
        $this->subdir = trim($lastseg, '/');
    }

    /**
     * Detect baseurl depending on subdir
     */
    private function detectBaseUrl() {
        $this->baseurl = empty($this->subdir) ? '/' : "/{$this->subdir}/";
    }

    /**
     * Initial url processing
     *
     * Normalize request url,
     * redirect index aliases (home, index, index.php)
     * and full up $url and $segments vars.
     */
    private function initUrlProcess() {
        $url = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $urld = urldecode($url);
        $url = trim(str_replace($this->subdir, "", $urld), '/');

        // Redirect index aliases
        if (in_array($url, ['home', 'index', 'index.php'])) {
            redirect($this->subdir, 301);
        }

        $this->url = $url;
        $this->segments = empty($url) ? [] : explode('/', $url);
    }

    /**
     * Get instance
     * @return Router
     */
    public static function getInstance() {
        if ( ! isset(self::$instance)) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * Add a route
     * @param string $route  A route string
     * @param string $action Controller class and method
     */
    public function addRoute($route, $action) {
        $this->routes[$route] = $action;
    }

    /**
     * Get action of a route
     * @param string $route A route string
     * @return string|null   The action or null
     */
    public function getRoute($route) {
        return isset($this->routes[$route]) ? $this->routes[$route] : null;
    }

    /**
     * Return with registered routes
     * @return array
     */
    public function getRoutes() {
        return $this->routes;
    }

    /**
     * Get an URL segment
     * @param int $index Index of URL segment in segments array
     * @return string|null The segment or null
     */
    public function getSegment($index) {
        return (isset($this->segments[$index])) ? $this->segments[$index] : null;
    }

    /**
     * Get all segments
     * @return array
     */
    public function getSegments() {
        return $this->segments;
    }

    /**
     * Get baseurl
     * @return string
     */
    public function getBaseUrl() {
        return $this->baseurl;
    }

    /**
     * Get subdir
     * @return string
     */
    public function getSubDir() {
        return $this->subdir;
    }

    /**
     * Get URL
     * @return string
     */
    public function getUrl() {
        return $this->url;
    }

    /**
     * Parse request
     * @return array  The action splitted into class and method names.
     */
    public function parseRequest() {
        if (empty($this->segments)) {
            // We are on the index page.
            return $this->parseAction($this->routes['home']);
        }

        foreach ($this->routes as $route => $action) {
            if ($this->matchRoute($route)) {
                // Found the right route.
                return array_merge($this->parseAction($action), $this->getVars($route));
            }
        }

        // No action registered for this request, go with 404.
        return $this->parseAction($this->routes['404']);
    }

    /**
     * Split action to controller class name and method name.
     * @param string $action
     * @return array
     */
    public function parseAction($action) {
        if ( ! strstr($action, '@')) {
            return [$action, 'index'];
        }

        return [
                substr($action, 0, strpos($action, '@')),
                substr($action, strpos($action, '@') + 1),
        ];
    }

    /**
     * Get values of wildcards from current url.
     * @param string $route
     * @return array
     */
    private function getVars($route) {
        if ( ! strstr($route, ':word') && ! strstr($route, ':num')) {
            return [];
        }
        $regex = str_replace(['(', ')', ':word', ':num'], ["", "", '([\w_-]+)', '([\d-]+)'], $route);
        $found = preg_match_all('~' . $regex . '~', $this->url, $matches, PREG_SET_ORDER);
        if ($found) {
            unset($matches[0][0]);  // first match is the full url path
            return array_values($matches[0]);
        }

        return [];
    }

    /**
     * Compare url with one route
     * @param string $route
     * @return bool
     */
    private function matchRoute($route) {
        // Route match exactly with the url?
        if ($route === $this->url) {
            return true;
        }

        $regex = str_replace(['/', ')', ':word', ':num'], ['\/', ')?', '[\w_-]+', '[\d-]+'], $route);
        if (preg_match('~^' . $regex . '$~', $this->url)) {
            return true;
        }
        return false;
    }
}
