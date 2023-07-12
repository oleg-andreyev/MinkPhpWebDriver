#!/usr/bin/env bash

set -ex

MACHINE_FAMILY=$1
DRIVER_VERSION=$2

if [[ $MACHINE_FAMILY != "mac" ]]; then
    echo "Can be executed only on MacOS"
    exit 1;
fi

rm -rf ~/Library/Logs/com.apple.WebDriver/*

if [[ "$USE_SAFARI_TECHNOLOGY_PREVIEW" == true ]]; then
  /Applications/Safari\ Technology\ Preview.app/Contents/MacOS/safaridriver -p 4444 --diagnose &
else
  /usr/bin/safaridriver -p 4444 --diagnose &
fi;

SAFARIDRIVER_PID=$!

function stop {
    kill $SAFARIDRIVER_PID
}

trap stop SIGINT
trap stop ERR

# generate first log
sleep 5
curl 127.0.0.1:4444 -vvv

tail -F ~/Library/Logs/com.apple.WebDriver/*
