#!/usr/bin/env bash

set -ex

if [[ -n "$1" ]]; then
    BROWSER_NAME="$1"
fi

if [[ -n "$2" ]]; then
    DRIVER_VERSION="$2"
    CHROMEDRIVER_VERSION=$DRIVER_VERSION
    GECKODRIVER_VERSION=$DRIVER_VERSION
    EDGEDRIVER_VERSION=$DRIVER_VERSION
fi

mkdir -p ./logs
rm -rf ./logs/*

UNAME=$(uname -s)

case "$UNAME" in
*NT*) machine=windows ;;
Linux*) machine=linux ;;
Darwin*) machine=mac ;;
esac

if [ -z $BROWSER_NAME ]; then
    echo "Environment variable BROWSER_NAME must be defined"
    exit 1
fi

if [[ $BROWSER_NAME == "chrome" && -z $CHROMEDRIVER_VERSION ]]; then
    echo "Environment variable CHROMEDRIVER_VERSION must be defined"
    exit 1
fi

if [[ $BROWSER_NAME == "firefox" && -z $GECKODRIVER_VERSION ]]; then
    echo "Environment variable GECKODRIVER_VERSION must be defined"
    exit 1
fi

if [[ $BROWSER_NAME == "msedge" && -z $EDGEDRIVER_VERSION ]]; then
    echo "Environment variable EDGEDRIVER_VERSION must be defined"
    exit 1
fi

if [[ "$BROWSER_NAME" == "chrome" && "$CHROMEDRIVER_VERSION" == "latest" ]]; then
    CHROMEDRIVER_VERSION=$(curl -sS https://chromedriver.storage.googleapis.com/LATEST_RELEASE)
fi

if [[ "$BROWSER_NAME" == "msedge" && "$EDGEDRIVER_VERSION" == "latest" ]]; then
    EDGEDRIVER_VERSION=$(curl -sS https://msedgewebdriverstorage.blob.core.windows.net/edgewebdriver/LATEST_STABLE -o - | tr -cd '\11\12\15\40-\176')
fi

if [[ "$BROWSER_NAME" == "chrome" ]]; then
    mkdir -p chromedriver

    if [[ $machine == "windows" ]]; then
        machine="win32"
    fi

    if [[ $machine == "linux" ]]; then
        machine="linux64"
    fi

    if [[ $machine == "mac" ]]; then
        machine="mac64"
    fi

    wget -q -t 3 "https://chromedriver.storage.googleapis.com/${CHROMEDRIVER_VERSION}/chromedriver_${machine}.zip" -O driver.zip
    unzip -qo driver.zip -d chromedriver/
fi

if [[ "$BROWSER_NAME" == "msedge" ]]; then
    mkdir -p msedgedriver

    if [[ $machine == "windows" ]]; then
        machine="win32"
    fi

    if [[ $machine == "linux" ]]; then
        machine="linux64"
    fi

    if [[ $machine == "mac" ]]; then
        machine="mac64"
    fi

    wget -q -t 3 "https://msedgedriver.azureedge.net/${EDGEDRIVER_VERSION}/edgedriver_win64.zip" -O driver.zip
    unzip -qo driver.zip -d msedgedriver/
fi

if [[ "$BROWSER_NAME" == "firefox" && "$GECKODRIVER_VERSION" == "latest" ]]; then
    GECKODRIVER_VERSION=$(curl -sS https://api.github.com/repos/mozilla/geckodriver/releases/latest | grep -E -o 'tag_name([^,]+)' | tr -d \" | tr -d " " | cut -d':' -f2)
fi

if [[ "$BROWSER_NAME" == "firefox" ]]; then
    mkdir -p geckodriver

    extension="tar.gz"

    if [[ $machine == "linux" ]]; then
        machine="linux64"
    fi

    if [[ $machine == "windows" ]]; then
        machine="win64"
        extension="zip"
    fi

    if [[ $machine == "mac" ]]; then
        machine="macos"
    fi

    wget -q -t 3 "https://github.com/mozilla/geckodriver/releases/download/${GECKODRIVER_VERSION}/geckodriver-$GECKODRIVER_VERSION-${machine}.${extension}" -O "driver.${extension}"

    if [[ "$extension" == "tar.gz" ]]; then
        tar -xf driver.tar.gz -C ./geckodriver/;
    else
        unzip -qo driver.zip -d geckodriver/
    fi;
fi

if [[ "$BROWSER_NAME" == "msedge" ]]; then
    ./msedgedriver/msedgedriver --port=4444 --verbose --enable-chrome-logs --whitelisted-ips=
elif [[ "$BROWSER_NAME" == "chrome" ]]; then
    ./chromedriver/chromedriver --port=4444 --verbose --enable-chrome-logs --whitelisted-ips=
elif [[ "$BROWSER_NAME" == "firefox" ]]; then
    ./geckodriver/geckodriver --host 127.0.0.1 -vv --port 4444
fi
