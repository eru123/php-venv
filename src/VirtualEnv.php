<?php

namespace eru123\venv;

use Exception;

class VirtualEnv
{
    protected static $venv = [];
    public static function get(array $array, string $key = null, $default = null)
    {
        if (is_null($key) || empty($key)) {
            return $array;
        }

        if (isset($array[$key])) {
            return $array[$key];
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

        foreach (explode('.', $key) as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return $default;
            }
            $array = $array[$segment];
        }

        return $array;
    }

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

    public static function venv_set(string $key, $value): void
    {
        if (empty($key)) {
            return;
        }

        static::set(static::$venv, $key, $value);
    }

    public static function venv_get(string $key = null, $default = null)
    {
        return self::get(static::$venv, $key, $default);
    }

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
        }
    }

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
}
