<?php namespace ys\main;

class Image {
    /**
     * Predefined (named) image sizes.
     * @var array
     */
    protected $namedSizes = [];
    /**
     * Jpeg quality from 0 (worst) to 100.
     * @var int
     */
    protected $jpegQuality = 80;
    /**
     * PNG compression level from 0 (no compression) to 9.
     * @var int
     */
    protected $pngQuality = 0;
    /**
     * 0 or 1, interlaced or baseline image type.
     * On jpeg this takes as progressive or standard.
     * @var int
     */
    protected $interlace = 0;
    /**
     * Directory name for resized images.
     * False (default): save image next to current image.
     * @var bool|string
     */
    protected $thumbDir = false;
    /**
     * Base path to all images.
     * @var string
     */
    protected $basePath;
    /**
     * Current image path.
     * @var string
     */
    protected $imgPath;
    /**
     * Directory of current image.
     * @var string
     */
    protected $imgDir;
    /**
     * Original image resource.
     * @var resource  GD image stream
     */
    protected $img;
    /**
     * Original image width.
     * @var int
     */
    protected $imgW;
    /**
     * Original image height.
     * @var int
     */
    protected $imgH;
    /**
     * Image type.
     * @var int
     */
    protected $type;
    /**
     * Crop flag.
     * @var bool
     */
    protected $crop;
    /**
     * Destination image resource.
     * @var resource  GD image stream
     */
    protected $dest;
    /**
     * Destination image width.
     * @var int
     */
    protected $destW;
    /**
     * Destination image height.
     * @var int
     */
    protected $destH;
    /**
     * Destination image path.
     * @var string
     */
    protected $destPath;

    /**
     * Constructor
     * Checks and sets base path.
     *
     * @param string $basePath (optional) Relative to this file.
     * @return Image  $this
     */
    public function __construct($basePath = DOCROOT) {
        $this->setBasePath($basePath);
    }

    /**
     * Base path setter.
     *
     * @param string $basePath Absolute path here!
     * @return self object
     */
    public function setBasePath($basePath) {
        $path = realpath($basePath);

        if ( ! $path) {
            trigger_error(__CLASS__ . ": Invalid base path `{$basePath}`.", E_USER_ERROR);
        }

        $this->basePath = $path;

        return $this;
    }

    /**
     * Add a named size to registered ones.
     *
     * @param string $name
     * @param array  $size
     * @return self object
     */
    public function addNamedSize($name, $size) {
        $this->namedSizes[$name] = $size;

        return $this;
    }

    /**
     * Set Jpeg quality.
     *
     * @param int $jpegQuality
     * @return self object
     */
    public function setJpegQuality($jpegQuality) {
        $this->jpegQuality = $jpegQuality;

        return $this;
    }

    /**
     * Set png quality.
     *
     * @param int $pngQuality
     * @return self object
     */
    public function setPngQuality($pngQuality) {
        $this->pngQuality = $pngQuality;

        return $this;
    }

    /**
     * Switch interlace bit.
     *
     * @return self object
     */
    public function switchInterlace() {
        $this->interlace = ($this->interlace == 0) ? 1 : 0;

        return $this;
    }

    /**
     * Set extra directory for resized images.
     *
     * @param mixed $dir
     * @return Image
     */
    public function setThumbDir($dir) {
        if (is_string($dir)) {
            $this->thumbDir = $dir;
        } elseif (true === $dir) {
            $this->thumbDir = '_';
        } else {
            $this->thumbDir = false;
        }

        return $this;
    }

    /**
     * Set path, directory, width and height
     * of the current image to work with.
     *
     * @param string $imgPath Relative to base path.
     * @return self object
     */
    public function set($imgPath) {
        $path = realpath($this->basePath . '/' . $imgPath);

        if ( ! $path) {
            trigger_error(__CLASS__ . ": Invalid image path `{$imgPath}`.", E_USER_ERROR);
        }

        $this->imgPath = $path;

        $this->imgDir = dirname($path);

        list($this->imgW, $this->imgH, $this->type) = getimagesize($path);

        return $this;
    }

    /**
     * Set destination image params.
     *
     * @param array $args
     * @return self object
     */
    public function size($args) {
        $this->destW = isset($args[0]) ? $args[0] : null;
        $this->destH = isset($args[1]) ? $args[1] : null;
        $this->crop = isset($args[2]) ? $args[2] : false;

        $this->calculateSize();

        return $this;
    }

    /**
     * Set named size.
     *
     * @param string $name
     * @return self object
     */
    public function sizename($name) {
        if ( ! isset($this->namedSizes[$name])) {
            trigger_error(__CLASS__ . ": Unknown size name `{$name}`", E_USER_ERROR);
        }

        $this->size($this->namedSizes[$name]);

        return $this;
    }

    /**
     * Get resied image url.
     *
     * @return string
     */
    public function url() {
        if ( ! $this->validateSize()) {
            // Return with original image url.
            return $this->pathToUrl($this->imgPath);
        }

        $this->setDestPath();

        if ( ! file_exists($this->destPath)) {
            $this->create();
            $this->resize();
            $this->save();
        }

        return $this->pathToUrl($this->destPath);
    }

    /**
     * Show image.
     */
    public function show() {
        $this->create();

        if ( ! $this->validateSize()) {
            // Set destination resource to original.
            $this->dest = $this->img;
        } else {
            $this->resize();
        }

        header('Content-Type: ' . image_type_to_mime_type($this->type));

        $this->output();
    }

    /**
     * Checking size params.
     *
     * @return bool
     */
    protected function validateSize() {
        if ($this->destW > $this->imgW || $this->destH > $this->imgH) {
            // No upscaling.
            return false;
        }

        if ($this->crop && (is_null($this->destW) || is_null($this->destH))) {
            // Crop mode needs both width and height.
            return false;
        }

        if ( ! $this->crop && is_null($this->destW) && is_null($this->destH)) {
            // Non-crop mode needs at least one of width or height.
            return false;
        }

        return true;
    }

    /**
     * Calculate size values from the given ones.
     */
    protected function calculateSize() {
        if ($this->crop) {
            // Width and height is exact the given values.
            return;
        }

        $ratio = $this->imgW / $this->imgH;

        if ( ! is_null($this->destW) && is_null($this->destH)) {
            $this->destH = floor($this->destW / $ratio);
        } elseif ( ! is_null($this->destW) && ! is_null($this->destH)) {
            $destH = floor($this->destW / $ratio);

            // Check if calculated height went out of range.
            if ($destH > $this->destH) {
                $this->destW = floor($this->destH * $ratio);
            } else {
                $this->destH = $destH;
            }
        } elseif (is_null($this->destW) && ! is_null($this->destH)) {
            $this->destW = floor($this->destH * $ratio);
        }
    }

    /**
     * Sets destPath property with some string concatenation.
     */
    protected function setDestPath() {
        $name = pathinfo($this->imgPath, PATHINFO_FILENAME);
        $ext = pathinfo($this->imgPath, PATHINFO_EXTENSION);

        $this->destPath = $this->destDir() . "/{$name}-{$this->destW}x{$this->destH}.{$ext}";
    }

    /**
     * Detect and create if non-exists destination directory.
     *
     * @return string
     */
    protected function destDir() {
        if ( ! is_string($this->thumbDir)) {
            return $this->imgDir;
        }

        $dir = $this->imgDir . '/' . $this->thumbDir;

        if ( ! is_dir($dir)) {
            mkdir($dir);
        }

        return $dir;
    }

    /**
     * Convert path to url, by strip basepath
     * and convert slashes to the right direction.
     *
     * @param string $path
     * @return string
     */
    protected function pathToUrl($path) {
        return str_replace([$this->basePath, '\\'], ["", '/'], $path);
    }

    /**
     * Create image resource from original image.
     */
    protected function create() {
        switch ($this->type) {
            case IMAGETYPE_GIF:
                $this->img = imagecreatefromgif($this->imgPath);
                break;
            case IMAGETYPE_JPEG:
                $this->img = imagecreatefromjpeg($this->imgPath);
                break;
            case IMAGETYPE_PNG:
                $this->img = imagecreatefrompng($this->imgPath);
                break;
            default:
                trigger_error(__CLASS__ . ': Unsupported image type.', E_USER_ERROR);
        }
    }

    /**
     * Resize image.
     */
    protected function resize() {
        // Create original image resource.
        $this->create();

        // Create image resource to destinaion.
        $this->dest = imagecreatetruecolor($this->destW, $this->destH);

        // Set interlace bit.
        imageinterlace($this->dest, $this->interlace);

        // Fill up background.
        switch ($this->type) {
            case IMAGETYPE_GIF:
                $background = imagecolorallocatealpha($this->dest, 255, 255, 255, 1);
                imagecolortransparent($this->dest, $background);
                imagefill($this->dest, 0, 0, $background);
                imagesavealpha($this->dest, true);
                break;
            case IMAGETYPE_JPEG:
                $background = imagecolorallocate($this->dest, 255, 255, 255);
                imagefilledrectangle($this->dest, 0, 0, $this->destW, $this->destH, $background);
                break;
            case IMAGETYPE_PNG:
                imagealphablending($this->dest, false);
                imagesavealpha($this->dest, true);
                break;
        }

        // Copy the right area to the destination.
        list($left, $top, $copyW, $copyH) = $this->crop ? $this->getCropInfo() : [0, 0, $this->imgW, $this->imgH];

        imagecopyresampled($this->dest, $this->img, 0, 0, $left, $top, $this->destW, $this->destH, $copyW, $copyH);

        // Sharpen image (if needed).
        $this->sharpen();
    }

    /**
     * Calculate crop values.
     *
     * @return array [x, y, w, h]
     */
    protected function getCropInfo() {
        $ratio = $this->imgW / $this->imgH;
        $destRatio = $this->destW / $this->destH;

        // Need cut from the edges
        if ($ratio > $destRatio) {
            $top = 0;
            $copyH = $this->imgH;
            $copyW = floor($this->imgH * $destRatio);
            $left = floor(($this->imgW - $copyW) / 2);
        } // Need cut from top and bottom
        else {
            $left = 0;
            $copyW = $this->imgW;
            $copyH = floor($this->imgW / $destRatio);
            $top = floor(($this->imgH - $copyH) / 2);
        }

        return [$left, $top, $copyW, $copyH];
    }

    /**
     * Sharpen image if downscale was significant.
     */
    protected function sharpen() {
        // Count percentage of downscaling.
        $ratioW = $this->destW / $this->imgW;
        $ratioH = $this->destH / $this->imgH;
        $ratioAvg = ($ratioW + $ratioH) / 2;
        $percent = round($ratioAvg, 2) * 100;

        // No need to sharpen.
        if ($percent > 60) {
            return;
        }

        // Really soft sharpen matrix.
        $udfMatrix = [
                [-1.2, -1.0, -1.2],
                [-1.0, 21.0, -1.0],
                [-1.2, -1.0, -1.2],
        ];

        $divisor = array_sum(array_map('array_sum', $udfMatrix));
        $offset = 0;

        imageconvolution($this->dest, $udfMatrix, $divisor, $offset);
    }

    /**
     * Save image.
     */
    protected function save() {
        $this->output($this->destPath);
    }

    /**
     * Send image to output file or direct http.
     *
     * @param null|string $path
     */
    protected function output($path = null) {
        switch ($this->type) {
            case IMAGETYPE_GIF:
                imagegif($this->dest, $path);
                break;
            case IMAGETYPE_JPEG:
                imagejpeg($this->dest, $path, $this->jpegQuality);
                break;
            case IMAGETYPE_PNG:
                imagepng($this->dest, $path, $this->pngQuality);
                break;
        }
    }
}
