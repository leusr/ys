<?php namespace ys\main;

/*
|--------------------------------------------------------------------------
| Config getters and setters
|--------------------------------------------------------------------------
|
| The config data located at SYSDIR/config in php files and in
| associative arrays. Config data has only get method, they are
| designed to be static configuration data.
|
| The settings located at SYSDIR/storage/settings in json format.
| Settings can be saved, modified, as well as read. If a setting
| was save, calling flushSettings() method is important close to
| the end of php running time.
|
| The rest is same: while getting data, key must be dotted format,
| e.g.: `example.data.key` will search `example.(json|php)` file, and
| from that returns with the `key` value of `data` array.
|
| At saving data it is allowed to have only a dotless single key
| (as filename), and passing an array (or object) to save.
|
*/

class Config {
    private static $instance = null;
    private static $data = [];
    private static $mod = false;

    // @formatter:off
    public function __construct() {}
    private function __clone() {}
    private function __wakeup() {}
    // @formatter:on

    /**
     * @return null|Config
     */
    public static function getInstance() {
        if ( ! isset(self::$instance)) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * Get/set setting.
     * @param string     $key
     * @param mixed|null $data
     * @return mixed|null|false
     */
    public function setting($key, $data) {
        if (isset($data)) {
            return $this->storeSetting($key, $data);
        } else {
            return $this->getSetting($key);
        }
    }

    /**
     * Get config value.
     * @param string $key
     * @return mixed|null
     */
    public function getConfig($key) {
        return $this->getValue($key, $this->load('config', $key));
    }

    /**
     * Save new and modified settings to files
     */
    public function flushSettings() {
        if (self::$mod === false) {
            return;
        }

        foreach (self::$mod as $group) {
            file_put_contents(SYSDIR . "/storage/settings/$group.json",
                              json_encode(self::$data['settings'][$group],
                                          JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        }
    }

    /**
     * Get setting value.
     * @param string $key
     * @return mixed|null
     */
    private function getSetting($key) {
        return $this->getValue($key, $this->load('settings', $key));
    }

    /**
     * Preload data and/or just returns with it from memory.
     * @param string $type config|settings
     * @param string $key
     * @return array|null
     */
    private function load($type, $key) {
        $group = $this->keyFirst($key);

        if ( ! isset(self::$data[$type][$group])) {
            self::$data[$type][$group] = $type === 'config' ? $this->getArray($key) : $this->getJson($key);
        }

        return self::$data[$type][$group];
    }

    /**
     * Load config group from config/*.php.
     * @param string $key
     * @return array
     */
    private function getArray($key) {
        $path = realpath(SYSDIR . '/config/' . $this->keyFirst($key) . '.php');

        return $path ? include $path : [];
    }

    /**
     * Load settings group from storage/settings/*.json.
     * @param string $key
     * @return array
     */
    private function getJson($key) {
        $path = realpath(SYSDIR . '/storage/settings/' . $this->keyFirst($key) . '.json');

        return $path ? json_decode(file_get_contents($path), true) : [];
    }

    /**
     * Get value from preloaded datagroup.
     * @param string $key
     * @param array  $arr
     * @return mixed|null
     */
    private function getValue($key, $arr) {
        return $this->getDeepValue($this->trimFirst($key), $arr);
    }

    /**
     * Set datagroup array.
     * @param string $key
     * @param mixed  $data
     * @return array
     */
    private function setValue($key, $data) {
        return $this->setDeepValue($this->trimFirst($key), $data);
    }

    /**
     * Recoursive function to get the searched final deep value.
     * @param string $key
     * @param array  $arr
     * @return mixed|null
     */
    private function getDeepValue($key, $arr) {
        if (strstr($key, '.') !== false) {
            $k = $this->keyFirst($key);
            if ( ! isset($arr[$k])) {
                return null;
            }

            return $this->getDeepValue($this->trimFirst($key), $arr[$k]);
        }

        return isset($arr[$key]) ? $arr[$key] : null;
    }

    /**
     * Recoursive function to set deep associative arrays.
     * @param string $key
     * @param mixed  $data
     * @return array
     */
    private function setDeepValue($key, $data) {
        if (strstr($key, '.') !== false) {
            return $this->setDeepValue($this->trimLast($key), [$this->keyLast($key) => $data]);
        }

        return [$key => $data];
    }

    /**
     * Trim first part of a dotted key.
     * If key does not contain a dot, returns empty string.
     * @param string $key
     * @return string
     */
    private function trimFirst($key) {
        return strstr($key, '.') !== false ? substr($key, strpos($key, '.') + 1) : "";
    }

    /**
     * Trim last part of a dotted key.
     * If key does not contain a dot, returns the key untouched.
     * @param string $key
     * @return string
     */
    private function trimLast($key) {
        return strstr($key, '.') !== false ? substr($key, 0, strrpos($key, '.')) : $key;
    }

    /**
     * Get first part of a dotted key.
     * If key does not contain a dot, returns the key untouched.
     * @param string $key
     * @return string
     */
    private function keyFirst($key) {
        return strstr($key, '.') !== false ? substr($key, 0, strpos($key, '.')) : $key;
    }

    /**
     * Get last part of a dotted key.
     * If key does not contain a dot, returns empty string.
     * @param string $key
     * @return string
     */
    private function keyLast($key) {
        return strstr($key, '.') !== false ? substr($key, strrpos($key, '.') + 1) : "";
    }

    /**
     * Get $i level of a dotted key. (unused)
     * E.g.: ('some.setting.key', 1) returns with 'setting'.
     * @param string $key
     * @param int    $i
     * @return string
     */
    private function keyLevel($key, $i) {
        $parts = explode('.', $key);

        return isset($parts[$i]) ? $parts[$i] : "";
    }

    /**
     * Count how deep is the key.
     * @param string $key
     * @return int
     */
    private function countLevels($key) {
        return count(explode('.', $key));
    }

    /**
     * @param string $key
     * @param mixed  $data
     * @return bool
     */
    private function storeSetting($key, $data) {
        if ( ! $this->validateKeyData($key, $data)) {
            return false;
        }

        $group = $this->keyFirst($key);
        $path = SYSDIR . "/storage/settings/{$group}.json";

        if (isset(self::$data['settings'][$group])) {
            self::$data['settings'][$group]
                    = array_merge_recursive(self::$data['settings'][$group], $this->setValue($key, $data));
        } else {
            self::$data['settings'][$group] = realpath($path) ? array_merge_recursive($this->load('settings', $key),
                                                                                      $this->setValue($key, $data))
                    : $this->setValue($key, $data);
        }

        if ( ! is_array(self::$mod)) {
            self::$mod = [$group];
        } elseif ( ! in_array($group, self::$mod)) {
            self::$mod[] = $group;
        }
    }

    /**
     * Check some key/data consistencies.
     * @param string $key
     * @param mixed  $data
     * @return bool
     */
    private function validateKeyData($key, $data) {
        if ($this->countLevels($key) < 2 && ! is_array($data) && ! is_object($data)) {
            trigger_error("Trying to store a single value without proper key. `{$key}` was given.", E_USER_WARNING);

            return false;
        }

        return true;
    }
}
