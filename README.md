MinkPhpWebDriver
=================================

Initially it's started as [PR](https://github.com/minkphp/MinkSelenium2Driver/pull/304) to MinkSelenium2Driver

Major updates include:
 - Switch to using `facebook/webdriver`
 - Update minimum php version to 7.2
 - Tested against the latest Google Chrome and Mozilla Firefox both in GUI and Headless modes

## Setup

Install via `oleg-andreyev/mink-phpwebdriver-extension`
```bash
$ composer require --dev oleg-andreyev/mink-phpwebdriver-extension
```

Add this extension to your `behat.yml` (see below)

- Set the wd_host to this server instead 
```yaml
default:
    extensions:
        OAndreyev\MinkPhpWebdriverExtension: ~
        Behat\MinkExtension:
            default_session: webdriver
            webdriver:
                wd_host: "http://0.0.0.0:4444/wd/hub"
                browser: 'chrome'
```
## Testing

```bash
$ ./bin/start_webdriver.sh &
#./bin/start_driver.sh <browser> <version>
$ ./bin/start_driver.sh chrome latest &
$ BROWSER_NAME=chrome ./vendor/bin/simple-phpunit
```

This will download the latest driver for specified browser and will execute phpunit

## Copyright

Copyright (c) 2019 Oleg Andreyev <oleg@andreyev.lv>
