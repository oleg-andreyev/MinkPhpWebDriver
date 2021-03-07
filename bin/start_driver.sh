#!/usr/bin/env bash

set -ex

usage() {
    echo "Usage: ./bin/start_driver.sh <browser> [version]"
}

CURRENT_DIR=$(dirname $0)

if [[ -z "$1" ]]; then
    usage;
    exit 1;
else
    BROWSER_NAME="$1"
fi

if [[ -n "$2" ]]; then
    DRIVER_VERSION="$2"
else
    DRIVER_VERSION="latest"
fi

mkdir -p ./logs
rm -rf ./logs/*

UNAME=$(uname -s)

case "$UNAME" in
*NT*) MACHINE_FAMILY=windows ;;
Linux*) MACHINE_FAMILY=linux ;;
Darwin*) MACHINE_FAMILY=mac ;;
esac

if [ -z "$BROWSER_NAME" ]; then
    echo "Environment variable BROWSER_NAME must be defined"
    exit 1
fi

if [[ ! -f "$CURRENT_DIR/browser/$BROWSER_NAME.sh" || ! -x "$CURRENT_DIR/browser/$BROWSER_NAME.sh" ]]; then
    echo "File '$CURRENT_DIR/browser/$BROWSER_NAME.sh' does not exists or is not executable"
    exit 1
fi;

exec "$CURRENT_DIR/browser/$BROWSER_NAME.sh" "$MACHINE_FAMILY" "$DRIVER_VERSION"
