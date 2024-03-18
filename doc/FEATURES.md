# Features

## Translation command

A command can Translate a Content for you

`php bin/console ibexa:automated:translate [contentId] [serviceName] --from=eng-GB --to=fre-FR`


## Adding your own Remote Translation Service

This bundle enables you to provide your own Translation mechanism.

To do so, you need to:

- create a service that implements Ibexa\Contracts\AutomatedTranslation\Client\ClientInterface
- implements the methods
- tag this service: `ibexa.automated_translation.client`
