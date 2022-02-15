<?php

namespace OAndreyev\Mink\Tests\Driver;

use Behat\Mink\Tests\Driver\AbstractConfig;
use Behat\Mink\Tests\Driver\Js\WindowTest;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Exception\WebDriverException;
use Facebook\WebDriver\Firefox\FirefoxDriver;
use Facebook\WebDriver\Firefox\FirefoxOptions;
use Facebook\WebDriver\Firefox\FirefoxProfile;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use OAndreyev\Mink\Driver\WebDriver;

class WebDriverConfig extends AbstractConfig
{
    /**
     * @var WebDriver
     */
    private $driver;

    public static function getInstance(): self
    {
        return new self();
    }

    /**
     * {@inheritdoc}
     */
    public function createDriver()
    {
        $browser = getenv('BROWSER_NAME') ?: 'firefox';
        $driverOptions = getenv('DRIVER_OPTIONS') ? \json_decode(getenv('DRIVER_OPTIONS'), true) : [];
        $seleniumHost = $_SERVER['DRIVER_URL'];

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Failed to parse DRIVER_OPTIONS: ' . json_last_error_msg());
        }

        if ($browser === 'firefox') {
            $desiredCapabilities = DesiredCapabilities::firefox();
        } else if ($browser === 'chrome' || $browser === 'msedge') {
            $desiredCapabilities = DesiredCapabilities::chrome();
            if ($browser === 'msedge') {
                $desiredCapabilities->setBrowserName('msedge');
            }
        } else {
            $desiredCapabilities = new DesiredCapabilities();
        }

        $capabilityMap = [
            'firefox' => FirefoxOptions::CAPABILITY,
            'chrome' => ChromeOptions::CAPABILITY_W3C,
            'msedge' => ChromeOptions::CAPABILITY_W3C,
        ];

        if (isset($capabilityMap[$browser])) {
            $options = $desiredCapabilities->getCapability($capabilityMap[$browser]);
            if ($browser === 'chrome' || $browser === 'msedge') {
                if (!$options) {
                    $options = new ChromeOptions();
                }
                $options = $this->buildChromeOptions($desiredCapabilities, $options, $driverOptions);
            } else if ($browser === 'firefox') {
                if (!$options) {
                    $options = new FirefoxOptions();
                }
                $options = $this->buildFirefoxOptions($desiredCapabilities, $options, $driverOptions);
            }

            $desiredCapabilities->setCapability($capabilityMap[$browser], $options);
        }

        $driver = new WebDriver($browser, [], $seleniumHost);
        $driver->setDesiredCapabilities($desiredCapabilities);

        // https://developer.mozilla.org/en-US/docs/Web/WebDriver/Commands/SetTimeouts
        $driver->setTimeouts(array('implicit' => 0, 'pageLoad' => 300000, 'script' => 30000));

        return $this->driver = $driver;
    }

    /**
     * {@inheritdoc}
     */
    public function skipMessage($testCase, $test)
    {
        $desiredCapabilities = $this->driver->getDesiredCapabilities();
        $chromeOptions = $desiredCapabilities->getCapability(ChromeOptions::CAPABILITY_W3C);

        $headless = $desiredCapabilities->getBrowserName() === 'chrome'
            && $chromeOptions instanceof ChromeOptions
            && in_array('headless', $chromeOptions->toArray()['args'] ?? [], true);

        if (
            'Behat\Mink\Tests\Driver\Js\WindowTest' === $testCase
            && (0 === strpos($test, 'testWindowMaximize'))
            && ('true' === getenv('CI') || $headless)
        ) {
            return 'Maximizing the window does not work when running the browser in Xvfb/Headless.';
        }

        return parent::skipMessage($testCase, $test);
    }

    /**
     * {@inheritdoc}
     */
    protected function supportsCss()
    {
        return true;
    }

    /**
     * @param ChromeOptions $optionsOrProfile
     * @param array         $driverOptions
     *
     * @return ChromeOptions
     */
    private function buildChromeOptions(DesiredCapabilities $desiredCapabilities, ChromeOptions $optionsOrProfile, array $driverOptions = [])
    {
        $binary = $driverOptions['binary'] ?? null;
        $optionsOrProfile->setBinary($binary);

        $args = $driverOptions['args'] ?? [];
        $optionsOrProfile->addArguments($args);

        return $optionsOrProfile;

        // TODO
        //$capability->addEncodedExtension();
        //$capability->addExtension();
        //$capability->addEncodedExtensions();
        //$capability->addExtensions();
    }

    /**
     * @param DesiredCapabilities $desiredCapabilities
     * @param FirefoxOptions      $firefoxOptions
     * @param array               $driverOptions
     *
     * @return FirefoxOptions
     */
    private function buildFirefoxOptions(DesiredCapabilities $desiredCapabilities, FirefoxOptions $firefoxOptions, array $driverOptions)
    {
        if (isset($driverOptions['binary'])) {
            $firefoxOptions->setOption('binary', $driverOptions['binary']);
        }
        if (isset($driverOptions['log'])) {
            $firefoxOptions->setOption('log', $driverOptions['log']);
        }
        if (isset($driverOptions['args'])) {
            $firefoxOptions->addArguments($driverOptions['args']);
        }

        return $firefoxOptions;

//        $preferences = $driverOptions['preference'] ?? [];
//        foreach ($preferences as $key => $preference) {
//            $firefoxProfile->setPreference($key, $preference);
//            // TODO
//            // $capability->setRdfFile($key, $preference);
//            // $capability->addExtensionDatas($key, $preference);
//            // $capability->addExtension($key, $preference);
//        }
//        return $firefoxProfile;
    }
}
