#!/usr/bin/env bash

set -ex

MACHINE_FAMILY=$1
DRIVER_VERSION=$2

if [[ $MACHINE_FAMILY != "mac" ]]; then
    echo "Only MacOS is supported"
    exit 1
fi

defaults read /Applications/Safari.app/Contents/Info CFBundleShortVersionString
/usr/bin/safaridriver -p 4444 --diagnose
