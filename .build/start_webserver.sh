#!/usr/bin/env bash

set -ex

ATTEMPT=0
until $(echo | nc localhost 4444); do
    if [ $ATTEMPT -gt 5 ]; then
        echo "Failed to start $BROWSER_NAME driver"
        cat ./logs/webdriver.log
        exit 1;
    fi;
    sleep 1;
    echo "Waiting for $BROWSER_NAME driver on port 4444...";
    ATTEMPT=$((ATTEMPT + 1))
done;
echo "$BROWSER_NAME driver started"

if [ "$machine" = "linux" ]; then
    ./vendor/bin/mink-test-server &> ./logs/mink-test-server.log &
else
    php -S localhost:8002 -t ./vendor/mink/driver-testsuite/web-fixtures &> ./logs/mink-test-server.log &
fi;

WEBSERVER_PID=$!

ATTEMPT=0
until $(echo | nc localhost 8002); do
        if [ $ATTEMPT -gt 5 ]; then
        echo "Failed to php server driver"
        cat ./logs/mink-test-server.log
        exit 1;
    fi;
    sleep 1;
    echo waiting for PHP server on port 8002...;
    ATTEMPT=$((ATTEMPT + 1))
done;
echo "PHP server started"
