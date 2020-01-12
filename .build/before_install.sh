#!/usr/bin/env bash

set -ex

# removing xdebug
phpenv config-rm xdebug.ini

if [[ $TRAVIS_PHP_VERSION = "7.4"* ]]; then
    # installing missing zip for 7.4
    pecl install zip
    echo "extension=zip.so" > ./ext-zip.ini
    phpenv config-add ./ext-zip.ini
fi;