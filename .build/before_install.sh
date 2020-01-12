#!/usr/bin/env bash

set -ex

# install xvfb
sudo apt install xvfb

# removing xdebug
phpenv config-rm xdebug.ini

if [[ $TRAVIS_PHP_VERSION = "7.4"* ]]; then
    sudo apt update
    sudo apt install libzip-dev
    # installing missing zip for 7.4
    pecl install zip
    echo "extension=zip.so" > ./ext-zip.ini
    phpenv config-add ./ext-zip.ini
fi;