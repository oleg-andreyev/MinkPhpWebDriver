#!/usr/bin/env bash

set -ex

travis_retry composer update --no-interaction $COMPOSER_FLAGS