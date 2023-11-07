# venv
Load env files virtually and isolated to $_ENV to add security layer on your data, with added extra tooling for convenience

### Installation
```bash
composer require eru123/venv
```

### Basic Usage
`.env` file
```bash
APP_ENV=development

# variable in env file will only work if the 
# variable that was called is already defined.
# NOTE: variable will only work on .env file
DB_NAME=${APP_ENV}
```
`index.php` file
```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

# Protect system environment variables
# Move all system env variables to virtual env
venv_protect();

# load .env file
venv_load(__DIR__ . '/.env');

# Set app array
venv_set('app', [
    'version' => '1.0.0',
    'name' => 'php-venv',
]);

# Modify app.name value using dot notation
venv_set('app.name', 'php-venv2');

# Merge an array to virtual env
# Note: dot notation will not work on this function, and might override array values
# Note: array will be ignored if all elements are not key-value pair (can be potential bug if not used properly)
venv_merge(['key' => 'pair'], ['this array will be ignored'], ['key2' => 'pair2']);

# Get all virtual env variables
print_r(venv());

# Get app.version value using dot notation
print_r(venv('app.version'));
```

`output`
```bash
Array
(
    [APP_ENV] => development
    [DB_NAME] => development
    [app] => Array
        (
            [version] => 1.0.0
            [name] => php-venv2
        )
    [key] => pair
    [key2] => pair2
)
1.0.0
```

### Advanced Usage
#### Multiple keys check
If you want to use a primary, secondary, and so on keys in case the first key is not defined, you can pass keys in array format as the first parameter in `venv()` function.
```bash
$value = venv(['key1', 'key2', 'key3'], 'default value in case all 3 keys are not defined');
```