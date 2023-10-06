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

# load .env file
venv_load(__DIR__ . '/.env');

# Set app array
venv_set('app', [
    'version' => '1.0.0',
    'name' => 'php-venv',
]);

# Modify app.name value using dot notation
venv_set('app.name', 'php-venv2');

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

)
1.0.0
```
