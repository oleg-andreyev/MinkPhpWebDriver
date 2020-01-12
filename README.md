MinkFacebookWebDriver
=================================

Initially it's started as [PR](https://github.com/minkphp/MinkSelenium2Driver/pull/304) to MinkSelenium2Driver

Major updates include:
 - Switch to using `facebook/webdriver`
 - Update minimum php version to 7.0
 - Tested against latest Google Chrome and Mozilla Firefox both in GUI and Headless modes

## Using the MinkFacebookWebDriver with Behat

Subclass `Behat\MinkExtension\ServiceContainer\MinkExtension` and add the new driver factory.

```php
<?php

namespace OAndreyev\BehatExtension;

use Behat\MinkExtension\ServiceContainer\MinkExtension as BaseMinkExtension;
use OAndreyev\Mink\Driver\WebDriverFactory;

class MinkExtension extends BaseMinkExtension
{
    public function __construct()
    {
        parent::__construct();
        $this->registerDriverFactory(new WebDriverFactory());
    }
}
```

Add this extension to your `behat.yml` (see below)

## Running chromedriver or geckodriver

- Google Chrome
    - Go to https://chromedriver.chromium.org/downloads and download required version
    - Start driver `./chromedriver --port=4444 --verbose --whitelisted-ips=`
    
- Mozilla Firefox
    - Go to https://github.com/mozilla/geckodriver/releases and download required version
    - Start driver `./geckodriver --host 127.0.0.1 -vv --port 4444`
- Docker
    - `docker run --rm --network=host -p 4444:4444 selenium/standalone-chrome`
    - `docker run --rm --network=host -p 4444:4444 selenium/standalone-firefox`

- Set the wd_host to this server instead 
```yaml
default:
suites: []
extensions:
  OAndreyev\BehatExtension\MinkExtension:
    default_session: webdriver
    javascript_session: webdriver
    webdriver:
      browser: chrome
      wd_host: "http://127.0.0.1:4444"
```
## Testing

```bash
# BROWSER_NAME=firefox GECKODRIVER_VERSION=latest ./.build/before_script.sh
$ BROWSER_NAME=chrome CHROMEDRIVER_VERSION=latest ./.build/before_script.sh
$ ./vendor/bin/simple-phpunit
```

This will download latest driver for specified browser and will execute phpunit

## Copyright

Copyright (c) 2019 Oleg Andreyev <oleg@andreyev.lv>
