# Distributor Tests

This document discusses unit tests.

## Initial Setup

### Setup instructions

"cd" into `plugins/distributor` directory and run the following:

1. Install [PHPUnit](http://phpunit.de/) via Composer by running:
    ```
    $ composer install
    ```

2. Install WordPress and the WP Unit Test lib using the `install.sh` script:
    ```
    $ tests/bin/install-wp-tests.sh <db-name> <db-user> <db-password> [db-host]
    ```

You may need to quote strings with backslashes to prevent them from being processed by the shell or other programs.

Example:

    $ tests/bin/install-wp-tests.sh distributor_tests root root

    #  ditributor_tests is the database name and root is both the MySQL user and its password.

**Important**: The `<db-name>` database will be created if it doesn't exist and all data will be removed during testing.

## Running Unit Tests

Change to the plugin root directory and type:

    $ vendor/bin/phpunit

The tests will execute and you'll be presented with a summary.

You can run specific tests by providing the path and filename to the test class:

    $ vendor/bin/phpunit tests/php/ConnectionsTest.php

A text code coverage summary can be displayed using the `--coverage-text` option:

    $ vendor/bin/phpunit --coverage-text
