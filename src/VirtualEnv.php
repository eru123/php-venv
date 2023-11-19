<?php

namespace eru123\venv;

use Exception;

class VirtualEnv
{
    protected static $venv = [];

    /**
     * Get specific key from Array or all keys
     * @param array $array The array to get key from
     * @param string|array|null $key The key to get or null to get all keys
     * @param mixed $default The default value to return if key not found
     * @return mixed
     */
    public static function get(array $array, string|array $key = null, $default = null)
    {
        if (is_array($key)) {
            foreach ($key as $k) {
                $tmp = static::get($array, $k, null);
                if (!is_null($tmp)) {
                    return $tmp;
                }
            }
            return $default;
        }

        if (is_null($key) || empty($key)) {
            return $array;
        }

        if (isset($array[$key])) {
            return $array[$key];
        }

        if (
            preg_replace_callback('/\{([^\}]+)\}/', function ($matches) use (&$array) {
                $key = $matches[1];
                $value = static::get($array, $key);
                if (is_array($value)) {
                    $value = static::get($value, $key);
                }
                return $value;
            }, $key) !== $key
        ) {
            $key = preg_replace_callback('/\{([^\}]+)\}/', function ($matches) use (&$array) {
                $key = $matches[1];
                $value = static::get($array, $key);
                if (is_array($value)) {
                    $value = static::get($value, $key);
                }
                return $value;
            }, $key);
        }

        foreach (explode('.', $key) as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return $default;
            }
            $array = $array[$segment];
        }

        return $array;
    }

    /**
     * Set key-value pair to Array
     * @param array $array The array to set key-value pair
     * @param string $key The key to set, can be dot notation
     * @param mixed $value The value to set
     * @return array
     */
    public static function set(array &$array, string $key, $value)
    {
        if (is_null($key) || empty($key)) {
            return $array = $value;
        }

        if (
            preg_replace_callback('/\{([^\}]+)\}/', function ($matches) use (&$array) {
                $key = $matches[1];
                $value = self::get($array, $key);
                if (is_array($value)) {
                    $value = self::get($value, $key);
                }
                return $value;
            }, $key) !== $key
        ) {
            $key = preg_replace_callback('/\{([^\}]+)\}/', function ($matches) use (&$array) {
                $key = $matches[1];
                $value = self::get($array, $key);
                if (is_array($value)) {
                    $value = self::get($value, $key);
                }
                return $value;
            }, $key);
        }

        $keys = explode('.', $key);

        while (count($keys) > 1) {
            $key = array_shift($keys);

            if (!isset($array[$key]) || !is_array($array[$key])) {
                $array[$key] = [];
            }

            $array = &$array[$key];
        }

        $array[array_shift($keys)] = $value;

        return $array;
    }

    /**
     * Check if key exists in Array
     * @param array $array The array to check key exists
     * @param string $key The key to check, can be dot notation
     * @return bool
     */
    public static function has(array $array, string $key)
    {
        if (empty($array) || is_null($key) || empty($key)) {
            return false;
        }

        if (array_key_exists($key, $array)) {
            return true;
        }

        foreach (explode('.', $key) as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return false;
            }
            $array = $array[$segment];
        }

        return true;
    }

    /**
     * Remove key-value pair from Array
     * @param array $array The array to remove key-value pair from
     * @param string $key The key to remove, can be dot notation
     * @return array
     */
    public static function forget(array &$array, string $key)
    {
        if (is_null($key) || empty($key)) {
            return $array = [];
        }

        if (array_key_exists($key, $array)) {
            unset($array[$key]);
            return $array;
        }

        $keys = explode('.', $key);

        while (count($keys) > 1) {
            $key = array_shift($keys);

            if (!isset($array[$key]) || !is_array($array[$key])) {
                return $array;
            }

            $array = &$array[$key];
        }

        unset($array[array_shift($keys)]);

        return $array;
    }

    /**
     * Set key-value pair to virtual env
     * @param string $key The key to set, can be dot notation
     * @param mixed $value The value to set
     * @return void
     */
    public static function venv_set(string $key, $value): void
    {
        if (empty($key)) {
            return;
        }

        static::set(static::$venv, $key, $value);
    }

    /**
     * Get specific key from virtual env or all keys
     * @param string|array|null $key The key to get or null to get all keys
     * @param mixed $default The default value to return if key not found
     * @return mixed
     */
    public static function venv_get(string|array $key = null, $default = null)
    {
        return self::get(static::$venv, $key, $default);
    }

    /**
     * Move all key-value pair from $_ENV to virtual env
     * @return void
     */
    public static function venv_protect(): void
    {
        $envs = getenv();
        $envs = array_merge(is_array($envs) ? $envs : [], is_array($_ENV) ? $_ENV : []);
        foreach ($envs as $key => $value) {
            self::venv_set($key, $value);
        }

        if (function_exists('putenv')) {
            foreach ($envs as $key => $value) {
                putenv($key);
            }
            putenv('PHP_VENV_MESSAGE=PHP ENV is protected by venv, all environment variables are moved to virtual environment');
        }
    }

    /**
     * Load .env file from path or directory to virtual env
     */
    public static function load_env(string|null|array $path, bool $strict = true, bool|string $env_mode = false): array|false
    {
        if (is_array($path)) {
            foreach ($path as $k => $v) {
                static::venv_set($k, $v);
            }
            return static::venv_get();
        }

        $path = realpath($path);

        if (!$path && $strict) {
            throw new Exception('Invalid path: ' . htmlspecialchars($path));
        }

        if (!$path) {
            return false;
        }

        $envf = [];
        if (is_dir($path)) {
            $files = scandir($path);
            if ($files && !empty($env_mode)) {
                foreach ($files as $file) {
                    if (preg_match("/^$env_mode(\..*)\.env$/i", $file)) {
                        $envf[] = $path . DIRECTORY_SEPARATOR . $file;
                    }
                }
            }
            unset($files);
        } else {
            $envf[] = $path;
        }

        sort($envf);
        foreach ($envf as $f) {
            $fr = file($f, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($fr as $line) {
                try {
                    $sym = ['//', '--', '#', ';'];
                    $ch0 = strlen($line) > 0 ? substr(trim($line), 0, 1) : false;
                    foreach ($sym as $s) {
                        if ($ch0 === $s) {
                            continue 2;
                        }
                    }

                    static::venv_set(...static::env_parse_line($line, $strict));
                } catch (Exception $e) {
                    !$strict || throw new Exception("Error parsing $f file: " . $e->getMessage());
                }
            }
        }

        return static::venv_get();
    }

    /**
     * Parse line from .env file
     */
    public static function env_parse_line(string $line, bool $strict = false): array
    {
        $linearr = explode('=', $line, 2);
        if (count($linearr) === 1) {
            return [$linearr[0], ''];
        }
        list($name, $value) = $linearr;
        $name = trim(strval($name));
        $value = trim(strval($value));

        if ($strict && preg_match('/[^a-z0-9_.]/i', $name)) {
            throw new Exception("Invalid environment variable name: {$name}");
        }

        if (preg_match('/^(true|false|null|\d+|\d+.\d+)$/i', $value)) {
            return [$name, json_decode(strtolower($value))];
        }

        $value = preg_replace_callback('/\${([a-z0-9_.]+)}/i', function ($matches) use ($strict) {
            if (is_null(static::venv_get($matches[1])) && $strict) {
                throw new Exception("Environment variable [{$matches[1]}] not found.");
            }

            return static::venv_get($matches[1], '');
        }, $value);

        if (preg_match('/^"(.+)"$/', $value)) {
            $value = preg_replace('/^"(.+)"$/', '$1', $value);
        } elseif (preg_match("/^'(.+)'$/", $value)) {
            $value = preg_replace("/^'(.+)'$/", '$1', $value);
        }

        return [$name, $value];
    }

    /**
     * Merge array to virtual env
     * @param array ...$array
     * @return void
     */
    public static function venv_merge(array ...$array): void
    {
        foreach ($array as $arr) {
            if (!is_array($arr) || array_keys($arr) !== range(0, count($arr) - 1)) {
                continue;
            }
            
            foreach ($arr as $k => $v) {
                static::$venv[$k] = $v;
            }
        }
    }
}
