<?php

use eru123\venv\VirtualEnv;

/**
 * Load .env file from path or directory to virtual env
 * @param string|null|array $path The .env file path or directory path, use array for multiple files or directories
 * @param bool $strict If true, throw exception if .env file not found
 * @param bool|string $env_mode Env file prefix (e.g. test.env, dev.env, prod.env), it will use .env if false
 * @return array|false Return array if success, false if failed
 */
function venv_load(string|null|array $path, bool $strict = true, bool|string $env_mode = false): array|false
{
    return VirtualEnv::load_env($path, $strict, $env_mode);
}

/**
 * Get specific key from virtual env or all keys
 * @param string|null $key The key name, use null to get all
 * @param mixed $default The default value if key not found
 * @return mixed Return the value if key found, default value if key not found
 */
function venv_get(string $key = null, $default = null): mixed
{
    return VirtualEnv::venv_get($key, $default);
}

/**
 * Set key-value pair to virtual env
 * @param string $key The key name
 * @param mixed $value The value
 * @return void
 */
function venv_set(string $key, $value): void
{
    VirtualEnv::venv_set($key, $value);
}

function venv_forget(string $key): void
{
    VirtualEnv::forget(VirtualEnv::$venv, $key);
}

function venv(string $key = null, $default = null)
{
    return VirtualEnv::venv_get($key, $default);
}

function venv_protect(): void
{
    VirtualEnv::venv_protect();
}