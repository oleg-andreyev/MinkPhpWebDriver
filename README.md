MinkPhpWebDriver
=================================

Initially it's started as [PR](https://github.com/minkphp/MinkSelenium2Driver/pull/304) to MinkSelenium2Driver

Major updates include:
 - Switch to using `facebook/webdriver`
 - Update minimum php version to 8.0
 - Tested against the latest Google Chrome and Mozilla Firefox both in GUI and Headless modes

## Setup

Install `oleg-andreyev/mink-phpwebdriver`
```bash
composer require oleg-andreyev/mink-phpwebdriver
```

## Behat Extension 
https://github.com/oleg-andreyev/MinkPhpWebdriverExtension

## Testing

```bash
./bin/start_webdriver.sh &
# ./bin/start_driver.sh <browser> <version>
./bin/start_driver.sh chrome latest &
BROWSER_NAME=chrome ./bin/phpunit
```

This will download the latest driver for specified browser and will execute phpunit

## Running GitHub Acton locally
Follow https://github.com/shivammathur/setup-php#local-testing-setup

## Copyright

Copyright (c) 2023 Oleg Andreyev <oleg@andreyev.lv>
