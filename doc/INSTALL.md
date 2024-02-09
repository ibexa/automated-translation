# Installation

## Requirements

* eZ Platform 4.x
* PHP 7.4+

## Installation steps

Run `composer require ibexa/automated-translation` to install the bundle and its dependencies:

### Change bundle's position in the configuration

The new bundle is automatically enabled in the configuration thanks to Flex. Even though, it's important and required to move `Ibexa\Bundle\AutomatedTranslation\IbexaAutomatedTranslationBundle::class => ['all' => true]` before `Ibexa\Bundle\AdminUi\IbexaAdminUiBundle::class => ['all' => true],` due to the templates loading order.

```php
<?php

return [
    ...
    Ibexa\Bundle\AutomatedTranslation\IbexaAutomatedTranslationBundle::class => ['all' => true],
    Ibexa\Bundle\AdminUi\IbexaAdminUiBundle::class => ['all' => true],
    ...
];
```

