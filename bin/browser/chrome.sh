#!/usr/bin/env bash

set -ex

MACHINE_FAMILY=$1
DRIVER_VERSION=$2

if [[ "$DRIVER_VERSION" == "latest" ]]; then
    DRIVER_VERSION=$(curl -sS https://chromedriver.storage.googleapis.com/LATEST_RELEASE)
fi

mkdir -p chromedriver

if [[ $MACHINE_FAMILY == "windows" ]]; then
    PLATFORM="win32"
fi

if [[ $MACHINE_FAMILY == "linux" ]]; then
    PLATFORM="linux64"
fi

if [[ $MACHINE_FAMILY == "mac" ]]; then
    PLATFORM="mac64"
fi

wget -q -t 3 "https://chromedriver.storage.googleapis.com/${DRIVER_VERSION}/chromedriver_${PLATFORM}.zip" -O driver.zip
unzip -qo driver.zip -d chromedriver/

./chromedriver/chromedriver --port=4444 --verbose --enable-chrome-logs --whitelisted-ips=
