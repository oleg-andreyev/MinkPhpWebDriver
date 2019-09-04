mkdir ./logs

if [[ "$BROWSER_NAME" = "chrome" && "$CHROMEDRIVER_VERSION" = "latest" ]]; then CHROMEDRIVER_VERSION=$(curl -sS https://chromedriver.storage.googleapis.com/LATEST_RELEASE); fi
if [[ "$BROWSER_NAME" = "chrome" ]]; then mkdir chromedriver; wget -q -t 3 "https://chromedriver.storage.googleapis.com/$CHROMEDRIVER_VERSION/chromedriver_linux64.zip"; unzip chromedriver_linux64 -d chromedriver; fi

if [[ "$BROWSER_NAME" = "firefox" && "$GECKODRIVER_VERSION" = "latest" ]]; then  GECKODRIVER_VERSION=$(curl -sS https://api.github.com/repos/mozilla/geckodriver/releases/latest | grep tag_name | cut -d' ' -f4 |  tr -d \" | tr -d ,); fi
if [[ "$BROWSER_NAME" = "firefox" ]]; then mkdir geckodriver; wget -q -t 3 "https://github.com/mozilla/geckodriver/releases/download/$GECKODRIVER_VERSION/geckodriver-$GECKODRIVER_VERSION-linux64.zip"; unzip "geckodriver-$GECKODRIVER_VERSION-linux64.zip" -d geckodriver; fi


if [ "$START_XVFB" = "1" ]; then sh -e /etc/init.d/xvfb start; fi;

if [ "$BROWSER_NAME" = "chrome" ]; then
  ./chromedriver/chromedriver --port=4444 --url-base=wd/hub --verbose &> ./logs/webdriver.log &
elif [ "$BROWSER_NAME" = "firefox" ]; then
  ./geckodriver/geckodriver --host 127.0.0.1 --port 4444 &> ./logs/webdriver.log &
else
  docker run --rm --network=host -p 4444:4444 "selenium/standalone-firefox:$SELENIUM_DRIVER" &> ./logs/selenium.log &
fi;

until $(echo | nc localhost 4444); do sleep 1; echo Waiting for WebDriver on port 4444...; done; echo "ChromeDriver started"

travis_retry ./vendor/bin/mink-test-server &> ./logs/mink-test-server.log &

until $(echo | nc localhost 8002); do sleep 1; echo waiting for PHP server on port 8002...; done; echo "PHP server started"
