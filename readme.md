# SilverStripe Bundled UserForms

## Overview

Bundles the requirements for UserForms using the [requirements resolver module](https://github.com/xini/silverstripe-requirements-resolver).

It also supports the use of forms using the [elementa userforms module] (https://github.com/dnadesign/silverstripe-elemental-userforms).

## Requirements

SilverStripe CMS 5, see [composer.json](composer.json)

Note: this version is compatible with Silverstripe 5. For Silverstripe 4, please see the [1 release line](https://github.com/xini/silverstripe-bundled-userforms/tree/1).

## Installation

Install the module using composer:

```
composer require innoweb/silverstripe-bundled-userforms dev-master
```
Then run dev/build.

## Usage

The module is automatically applied and loads the requirements for UserForms according to the global requirements definition of the requirements resolver module instead of the default ones.

No configuration is required.

## License

BSD 3-Clause License, see [License](license.md)