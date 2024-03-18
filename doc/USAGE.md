# 
Add a new configuration file with below information
```
#app/config/ibexa_automated_translation.yaml

ibexa_automated_translation:
    system:
        default:
            configurations:
                google:
                    apiKey: '%env(GOOGLE_API_KEY)%'
                deepl:
                    authKey: '%env(DEEPL_API_KEY)%'
```
Add your API key(s) in .env file

```
GOOGLE_API_KEY=...
DEEPL_API_KEY=...
```