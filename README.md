MinkPhpWebDriver
=================================

Initially it's started as [PR](https://github.com/minkphp/MinkSelenium2Driver/pull/304) to MinkSelenium2Driver

Major updates include:
 - Switch to using `facebook/webdriver`
 - Update minimum php version to 7.2
 - Tested against the latest Google Chrome and Mozilla Firefox both in GUI and Headless modes

## Using the MinkPhpWebDriver with Behat

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
$ ./bin/start_webdriver.sh &
#./bin/start_driver.sh <browser> <version>
$ ./bin/start_driver.sh chrome latest &
$ BROWSER_NAME=chrome ./vendor/bin/simple-phpunit
```

This will download the latest driver for specified browser and will execute phpunit

## Copyright

Copyright (c) 2019 Oleg Andreyev <oleg@andreyev.lv>
