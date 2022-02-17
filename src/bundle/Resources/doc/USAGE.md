# Usage

> All the configuration is SiteAccessAware then you can have different one depending on the SiteAccess

## Basic Configuration

```yaml
# config/packages/ibexa.yaml

ibexa_automated_translation:
    system:
        default:
            configurations:
                google:
                    apiKey: "google-api-key"
                deepl:
                    authKey: "deepl-pro-key"
```

