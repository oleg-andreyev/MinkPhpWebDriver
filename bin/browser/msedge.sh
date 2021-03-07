#!/usr/bin/env bash

set -ex

MACHINE_FAMILY=$1
DRIVER_VERSION=$2

if [[ "$DRIVER_VERSION" == "latest" ]]; then
    DRIVER_VERSION=$(curl -sS https://msedgewebdriverstorage.blob.core.windows.net/edgewebdriver/LATEST_STABLE -o - | cut -b3-23)
fi

mkdir -p msedgedriver

if [[ $MACHINE_FAMILY == "windows" ]]; then
    PLATFORM="win64"
fi

if [[ $MACHINE_FAMILY == "linux" ]]; then
    PLATFORM="linux64"
fi

if [[ $MACHINE_FAMILY == "mac" ]]; then
    PLATFORM="mac64"
fi

wget -q -t 3 "https://msedgedriver.azureedge.net/${DRIVER_VERSION}/edgedriver_${PLATFORM}.zip" -O driver.zip
unzip -qo driver.zip -d msedgedriver/

./msedgedriver/msedgedriver --port=4444 --verbose --enable-chrome-logs --whitelisted-ips=
