#!/usr/bin/env bash

set -ex

# install xvfb
sudo apt install xvfb

# removing xdebug
phpenv config-rm xdebug.ini

if [[ $TRAVIS_PHP_VERSION = "7.4"* ]]; then
    sudo apt update
    # https://github.com/php/php-src/blob/00821b807ce7953fd6a831f9a28ec7a2fcb1ce36/ext/zip/config.m4#L16
    sudo apt install -y unzip libssl-dev libzip-dev
    # installing missing zip for 7.4
    pecl install zip
    echo "extension=zip.so" > ./ext-zip.ini
    phpenv config-add ./ext-zip.ini
fi;