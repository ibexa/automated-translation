# Ibexa Automated Translation

Learn more about [Ibexa DXP](https://www.ibexa.co/products).

## COPYRIGHT
Copyright (C) 1999-2024 Ibexa AS (formerly eZ Systems AS). All rights reserved.

## LICENSE
This source code is available separately under the following licenses:

A - Ibexa Business Use License Agreement (Ibexa BUL),
version 2.4 or later versions (as license terms may be updated from time to time)
Ibexa BUL is granted by having a valid Ibexa DXP (formerly eZ Platform Enterprise) subscription,
as described at: https://www.ibexa.co/product
For the full Ibexa BUL license text, please see:
https://www.ibexa.co/software-information/licenses-and-agreements (latest version applies)

AND

B - GNU General Public License, version 2
Grants an copyleft open source license with ABSOLUTELY NO WARRANTY. For the full GPL license text, please see:
https://www.gnu.org/licenses/old-licenses/gpl-2.0.html

# Installation

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

# Usage

> All the configuration is SiteAccessAware then you can have different one depending on the SiteAccess

## Basic Configuration

```yaml
# config/packages/ibexa_automated_translation.yaml

ibexa_automated_translation:
    system:
        default:
            configurations:
                google:
                    apiKey: "google-api-key"
                deepl:
                    authKey: "deepl-pro-key"
```
