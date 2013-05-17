# Inventory

## Installation

Checkout the project using git or the Github plugin for PhpStorm.

Install [composer](http://getcomposer.org/doc/00-intro.md) and run:

```bash
composer install
```

## Run

Install [Virtual Box](https://www.virtualbox.org/wiki/Downloads) and [Vagrant](http://www.vagrantup.com/):

```bash
vagrant up
```

The website is accessible at [http://localhost:8080/](http://localhost:8080/).

PhpMyAdmin is accessible at [http://localhost:8080/phpmyadmin/](http://localhost:8080/phpmyadmin/).

SSH to the virtual machine:

```bash
vagrant ssh
```

Destroy the VM:

```bash
vagrant destroy
```

### Tests

```bash
phpunit
```

## Migration from SVN

SVN revision migrated for each package:

- Core: 12213
- UI: 12205
- Unit: 12214
- User: 12212
- TEC: 12083
- Calc: 11087
- Exec: 11090
- Export: 12202
- Classif: 12212
- Keyword: 12212
- Techno: 12212
- Doc: 12202
- DW: 12222
- Algo: 12124
