# Application Passwords for WordPress

[![Build Status](https://travis-ci.org/wordpress/application-passwords.svg?branch=master)](https://travis-ci.org/wordpress/application-passwords)

Creates unique passwords for applications to authenticate users without revealing their main passwords.


## Install

Install from the official [WordPress.org plugin repository](https://wordpress.org/plugins/application-passwords/) by searching for "Application Passwords" under "Plugins" → "Add New" in your WordPress dashboard.

### Install Using Composer

	composer require georgestephanis/application-passwords


## Documentation

See the [readme.txt](readme.txt) for usage instructions.


## Development Environment

Included is a local devolopment environment using [Docker](https://www.docker.com) with an optional [Vagrant](https://www.vagrantup.com) wrapper for network isolation and ZeroConf for automatic [application-passwords.local](http://application-passwords.local) discovery. Run `docker-compose up -d` to start the Docker containers on your host machine or `vagrant up` to start it in a [VirtualBox](https://www.virtualbox.org) environment.

### Scripts

- `npm install` to setup project dependencies including the Composer dependencies via the `postinstall` hook. Configures a `pre-commit` hook that lints all JS and PHP code before each commit.

- `npm run build` to create a release in the `dist` directory. We include Composer files since the same bundle is used for the Composer package.

- `npm run deploy` to deploy the plugin to the [WordPress.org plugin respository](https://wordpress.org/plugins/application-passwords/).


## Contribute

- Translate the plugin [into your language](https://translate.wordpress.org/projects/wp-plugins/application-passwords/).
- Report issues, suggest features and contribute code [on GitHub](https://github.com/WordPress/application-passwords).


## Credits

Created by [George Stephanis](https://github.com/georgestephanis). View [all contributors](https://github.com/WordPress/application-passwords/graphs/contributors).
