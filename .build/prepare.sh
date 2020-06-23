#!/usr/bin/env bash

set -ex

if [[ $TRAVIS_PHP_VERSION != "nightly" ]]; then
    # removing xdebug
    phpenv config-rm xdebug.ini
fi;

if [[ $TRAVIS_PHP_VERSION = "7.4"* ]]; then
    sudo apt update
    # https://github.com/php/php-src/blob/00821b807ce7953fd6a831f9a28ec7a2fcb1ce36/ext/zip/config.m4#L16
    # xenial ships with libzip 1.0.1 https://launchpad.net/ubuntu/xenial/+source/libzip
    # xenial ships with libssl 1.0.2g https://launchpad.net/ubuntu/xenial/+source/openssl
    wget https://launchpad.net/ubuntu/+archive/primary/+files/libssl1.1_1.1.0g-2ubuntu4_amd64.deb
    wget https://launchpad.net/ubuntu/+archive/primary/+files/libssl-dev_1.1.0g-2ubuntu4_amd64.deb
    wget https://launchpad.net/ubuntu/+archive/primary/+files/libzip5_1.5.1-0ubuntu1_amd64.deb
    wget https://launchpad.net/ubuntu/+archive/primary/+files/libzip-dev_1.5.1-0ubuntu1_amd64.deb
    sudo dpkg -i libssl1.1_1.1.0g-2ubuntu4_amd64.deb
    sudo dpkg -i libssl-dev_1.1.0g-2ubuntu4_amd64.deb
    sudo dpkg -i libzip5_1.5.1-0ubuntu1_amd64.deb
    sudo dpkg -i libzip-dev_1.5.1-0ubuntu1_amd64.deb
    sudo apt install -f
    sudo apt install -y unzip
fi;