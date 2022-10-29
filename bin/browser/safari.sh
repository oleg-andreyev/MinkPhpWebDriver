#!/usr/bin/env bash

set -ex

MACHINE_FAMILY=$1
DRIVER_VERSION=$2

if [[ $MACHINE_FAMILY != "mac" ]]; then
    echo "Can be executed only on MacOS"
    exit 1;
fi

rm -rf ~/Library/Logs/com.apple.WebDriver/*

/usr/bin/safaridriver --port=4444 --diagnose &
SAFARIDRIVER_PID=$!

function stop {
    kill $SAFARIDRIVER_PID
}

trap stop SIGINT

tail -f ~/Library/Logs/com.apple.WebDriver/* /dev/null
