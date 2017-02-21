<?php namespace ys\main;

class View {
    /**
     * Compress HTML?
     * @var bool
     */
    public $compressHTML = true;
    /**
     * Variables to passing for the view.
     *
     * If it's a string, reference to it in the view as `$data`.
     * If an associative array, reference to it with the keys.
     *
     * @var array|string
     */
    private $data = [];
    /**
     * The view file path based on $view param.
     *
     * $view must be the relative to VIEWSDIR
     *
     * @var string
     */
    private $path;
    /**
     * Basedirs. VIEWSDIR is only the first basedir,
     * others defined as fallbacks.
     *
     * @var array
     */
    private $basedirs
            = [
                    VIEWSDIR,
                    SYSDIR . '/views',
                    SYSDIR,
            ];

    /**
     * Constructor
     *
     * @param string $view
     * @param mixed  $data
     * @return self
     */
    public function __construct($view, $data = null) {
        $this->path = $this->getViewPath($view);
        $this->addData($data);

        return $this;
    }

    /**
     * Add data
     * @param array|string $data
     * @return self
     */
    public function addData($data) {
        if ( ! isset($data)) {
            return $this;
        }

        if (is_string($data)) {
            $data = ['data' => $data];
        } elseif ( ! is_array($data)) {
            return $this;
        }

        $this->data = array_merge($this->data, $data);

        return $this;
    }

    /**
     * Another method to adding data
     * @param string $key
     * @param mixed  $val
     * @return self
     */
    public function addKeyVal($key, $val) {
        $this->addData([$key => $val]);

        return $this;
    }

    /**
     * Return with rendered view.
     * @return string
     */
    public function get() {
        return $this->doRender();
    }

    /**
     * Echo rendered view.
     */
    public function render() {
        echo $this->doRender();
    }

    /**
     * Include the view within another view.
     * (Output buffer already started.)
     */
    public function inc() {
        extract($this->data);

        include $this->path;
    }

    /**
     * Search for the absolute path of the view file.
     * @param string $view
     * @return string|false
     */
    private function getViewPath($view) {
        foreach ($this->basedirs as $basedir) {
            // Try handle view as full path with extension
            if (false !== $path = realpath("$basedir/$view")) {
                return $path;
            }
            // Handle param without extension and with dots as directory separator
            if (false !== $path = realpath("$basedir/" . str_replace('.', '/', $view) . '.phtml')) {
                return $path;
            }
        }
        trigger_error("View file <b>$view</b> not found!", E_USER_ERROR);

        return false;
    }

    /**
     * Actually render the view.
     * @return string
     */
    private function doRender() {
        extract($this->data);
        ob_start();
        include $this->path;
        $html = ob_get_clean();
        if ($this->compressHTML) {
            $html = $this->minify($html);
        }
        $html = $this->specialTags($html);

        return $html;
    }

    /**
     * Use Minify project's html compressor
     * @param string $html
     * @return string
     */
    private function minify($html) {
        $options = [
                'useNewLines'     => false,
                'isXhtml'         => false,
                'jsCleanComments' => false,
        ];

        return CompressHTML::minify($html, $options);
    }

    /**
     * Replace special tags with values
     *
     *     {script_total_time}
     *     {memory_usage}
     *     <meta name="ascii-art" content="yes">
     *
     * @param string $html Prepared rendered html
     * @return string
     */
    private function specialTags($html) {
        // Script total time
        $tag = '{script_total_time}';

        if (strstr($html, $tag)) {
            $time = formatSeconds(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']);
            $html = str_replace($tag, $time, $html);
        }

        // PHP Memory usage
        $tag = '{memory_usage}';

        if (strstr($html, $tag)) {
            $memory = formatBytes(memory_get_usage());
            $html = str_replace($tag, $memory, $html);
        }

        // Giant ASCII Art
        $tag = '<meta name="ascii-art" content="yes">';

        if (strstr($html, $tag)) {
            $ascii_path = SYSDIR . '/storage/ascii_art.html';
            $html = file_exists($ascii_path) ? str_replace($tag, file_get_contents($ascii_path), $html)
                    : str_replace($tag, "", $html);
        }

        return $html;
    }
}
