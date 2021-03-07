#!/usr/bin/env bash

set -ex

MACHINE_FAMILY=$1
DRIVER_VERSION=$2

if [[ "$DRIVER_VERSION" == "latest" ]]; then
    DRIVER_VERSION=$(curl -sS https://api.github.com/repos/mozilla/geckodriver/releases/latest | grep -E -o 'tag_name([^,]+)' | tr -d \" | tr -d " " | cut -d':' -f2)
fi

mkdir -p geckodriver

EXTENSION="tar.gz"

if [[ $MACHINE_FAMILY == "windows" ]]; then
    PLATFORM="win64"
    EXTENSION="zip"
fi

if [[ $MACHINE_FAMILY == "linux" ]]; then
    PLATFORM="linux64"
fi

if [[ $MACHINE_FAMILY == "mac" ]]; then
    PLATFORM="macos"
fi

wget -q -t 3 "https://github.com/mozilla/geckodriver/releases/download/${DRIVER_VERSION}/geckodriver-$DRIVER_VERSION-${PLATFORM}.${EXTENSION}" -O "driver.${EXTENSION}"

if [[ "$EXTENSION" == "tar.gz" ]]; then
    tar -xf driver.tar.gz -C ./geckodriver/;
else
    unzip -qo driver.zip -d geckodriver/
fi;

./geckodriver/geckodriver --host 127.0.0.1 -vv --port 4444
