# SilverStripe Bundled UserForms

## Overview

Bundles the Javascript requirements for UserForms into one single script and defers its loading if no conditional Javascript is present for the form.

It also supports the use of forms using the [elementa userforms module] (https://github.com/dnadesign/silverstripe-elemental-userforms).

## Requirements

SilverStripe CMS 6, see [composer.json](composer.json)

## Installation

Install the module using composer:

```
composer require innoweb/silverstripe-bundled-userforms
```
Then run dev/build.

## Usage

The module is automatically applied and loads a bundled version of the requirements for UserForms instead of the default ones.

If you want to load the default scripts for a certain `UserForm` class, use the following config:

```yaml
Your\Custom\UserFormClass:
  block_default_userforms_js: false
```

## License

BSD 3-Clause License, see [License](license.md)
