<?php

use eru123\venv\VirtualEnv;

/**
 * Load .env file from path or directory to virtual env
 */
function venv_load(string|null|array $path, bool $strict = true, bool|string $env_mode = false): array|false
{
    return VirtualEnv::load_env($path, $strict, $env_mode);
}

/**
 * Get specific key from virtual env or all keys
 */
function venv_get(string|array $key = null, $default = null): mixed
{
    return VirtualEnv::venv_get($key, $default);
}

/**
 * Set key-value pair to virtual env
 */
function venv_set(string $key, $value): void
{
    VirtualEnv::venv_set($key, $value);
}

/**
 * Remove key-value pair from virtual env
 */
function venv_forget(string $key): void
{
    VirtualEnv::forget(VirtualEnv::$venv, $key);
}

/**
 * Get specific key from virtual env or all keys
 */
function venv(string|array $key = null, $default = null)
{
    return VirtualEnv::venv_get($key, $default);
}

/**
 * Move all key-value pair from $_ENV to virtual env
 */
function venv_protect(): void
{
    VirtualEnv::venv_protect();
}

/**
 * Merge array to virtual env
 */
function venv_merge(array ...$array): void
{
    VirtualEnv::venv_merge(...$array);
}