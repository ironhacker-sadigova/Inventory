# Inventory

To see the current version installed, visit the url: `/version.php`.

## Installation

Checkout the project using git or the Github plugin for PhpStorm.

Install [composer](http://getcomposer.org/doc/00-intro.md) and run:

```shell
composer install
```

Install npm and run:

```shell
npm install
```

Install [Bower](http://bower.io/#installing-bower) and run:

```shell
bower install
```

Install [Grunt](http://gruntjs.com/getting-started) and run:

```shell
grunt
```

This will generate the minified JS and CSS in `public/build/`.

Alternatively, there is an `install.sh` script that will do all the commands above.

Set up config files

```shell
cp application/configs/application.ini.default application/configs/application.ini
cp public/.htaccess.default public/.htaccess
cp application/configs/env.php.default application/configs/env.php
nano application/configs/env.php
```

Set up file rights

```shell
chmod 777 data/documents
chmod -R 777 data/logs
chmod 777 data/proxies
chmod -R 777 public/cache
chmod 777 public/temp
```

## Configure PhpStorm

Mark the following directories as "excluded":

- `vendor`
- `data/logs`
- `data/proxies`
- `behat/fixtures`
- `node_modules`
- `public/build`

Re-include the `vendor` directory in PHP "External Libraries" (to get auto-completion).

Add a "file watcher" for CSS files with the command `grunt cssmin:app`.
Add another "file watcher" for JS files with the command `grunt uglify:app`.
Don't forget to exclude the `public/build` directory, else it will run in an infinite loop!

## Run with Vagrant

Install [Virtual Box](https://www.virtualbox.org/wiki/Downloads) and [Vagrant](http://www.vagrantup.com/):

```shell
vagrant up
```

The website is accessible at [http://localhost:8000/inventory/](http://localhost:8000/inventory/).

PhpMyAdmin is accessible at [http://localhost:8000/phpmyadmin/](http://localhost:8000/phpmyadmin/).

SSH to the virtual machine:

```shell
vagrant ssh
cd /vagrant
```

Destroy the VM:

```shell
vagrant destroy
```

## Commands

Commands are run with `bin/inventory`, or `bin/tests` for the unit tests environment.

- `account:create "My Company"`: creates a new account
- `acl:rebuild`: rebuilds the ACL from the roles
- `cache:clear`: clear the caches
- `db:create`: creates the database (empty)
- `db:update`: update the database schema
- `db:populate [data-set]`: create and populates the database with a data set
- `export:rebuild`: rebuilds the export files

## Tests

```shell
cd inventory/
phpunit
```

## Logs

- **error.log**: application log
- **queries.log**: query log
- **worker.log**: while running in worker mode, logs are on the standard output, but redirected to this file by supervisor
