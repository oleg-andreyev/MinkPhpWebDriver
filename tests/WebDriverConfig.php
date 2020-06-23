<?php

namespace OAndreyev\Mink\Tests\Driver;

use Behat\Mink\Tests\Driver\AbstractConfig;
use Behat\Mink\Tests\Driver\Js\WindowTest;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Exception\WebDriverException;
use Facebook\WebDriver\Firefox\FirefoxDriver;
use Facebook\WebDriver\Firefox\FirefoxProfile;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use OAndreyev\Mink\Driver\WebDriver;

class WebDriverConfig extends AbstractConfig
{
    /**
     * @var WebDriver
     */
    private $driver;

    public static function getInstance()
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
        } else if ($browser === 'chrome') {
            $desiredCapabilities = DesiredCapabilities::chrome();
        } else {
            $desiredCapabilities = new DesiredCapabilities();
        }

        $capabilityMap = [
            'firefox' => FirefoxDriver::PROFILE,
            'chrome' => ChromeOptions::CAPABILITY
        ];

        if (isset($capabilityMap[$browser])) {
            $optionsOrProfile = $desiredCapabilities->getCapability($capabilityMap[$browser]);
            if ($browser === 'chrome') {
                if (!$optionsOrProfile) {
                    $optionsOrProfile = new class extends ChromeOptions {
                        public function toArray()
                        {
                            $result = parent::toArray();
                            if (empty($result['binary'])) {
                                unset($result['binary']);
                            }

                            if (count($result) === 0) {
                                // The selenium server expects a 'dictionary' instead of a 'list' when
                                // reading the chrome option. However, an empty array in PHP will be
                                // converted to a 'list' instead of a 'dictionary'. To fix it, we always
                                // set the 'binary' to avoid returning an empty array.
                                $result = new \ArrayObject();
                            }

                            return $result;
                        }
                    };
                }
                $capability = $this->buildChromeOptions($desiredCapabilities, $optionsOrProfile, $driverOptions);
            } else if ($browser === 'firefox') {
                $capability = $this->buildFirefoxProfile($desiredCapabilities, $optionsOrProfile, $driverOptions);
            }

            $desiredCapabilities->setCapability($capabilityMap[$browser], $capability);
        }

        $driver = new WebDriver($browser, [], $seleniumHost);
        $driver->setDesiredCapabilities($desiredCapabilities);

        return $this->driver = $driver;
    }

    /**
     * {@inheritdoc}
     */
    public function skipMessage($testCase, $test)
    {
//        if (
//            'Behat\Mink\Tests\Driver\Form\Html5Test' === $testCase
//            && 'testHtml5Types' === $test
//        ) {
//            return 'WebDriver does not support setting value in color inputs. See https://code.google.com/p/selenium/issues/detail?id=7650';
//        }

        $desiredCapabilities = $this->driver->getDesiredCapabilities();
        $chromeOptions = $desiredCapabilities->getCapability(ChromeOptions::CAPABILITY_W3C);

        $headless = $desiredCapabilities->getBrowserName() === 'chrome'
            && $chromeOptions instanceof ChromeOptions
            && in_array('headless', $chromeOptions->toArray()['args'] ?? [], true);

        if (
            'Behat\Mink\Tests\Driver\Js\WindowTest' === $testCase
            && (0 === strpos($test, 'testWindowMaximize'))
            && ('true' === getenv('TRAVIS') || $headless)
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
     * @param ChromeOptions|null $capability
     * @param array $driverOptions
     *
     * @return ChromeOptions
     */
    private function buildChromeOptions(DesiredCapabilities $desiredCapabilities, ChromeOptions $capability, array $driverOptions)
    {
        $binary = $driverOptions['binary'] ?? null;
        $capability->setBinary($binary);

        $args = $driverOptions['args'] ?? [];
        $capability->addArguments($args);
        return $capability;

        // TODO
        //$capability->addEncodedExtension();
        //$capability->addExtension();
        //$capability->addEncodedExtensions();
        //$capability->addExtensions();
    }

    /**
     * @param FirefoxProfile $capability
     * @param array $driverOptions
     *
     * @return FirefoxProfile
     * @throws WebDriverException
     */
    private function buildFirefoxProfile(DesiredCapabilities $desiredCapabilities, FirefoxProfile $capability, array $driverOptions)
    {
        if (isset($driverOptions['binary'])) {
            $firefoxOptions = $desiredCapabilities->getCapability('moz:firefoxOptions');
            if (empty($firefoxOptions)) {
                $firefoxOptions = [];
            }
            $firefoxOptions = array_merge($firefoxOptions, ['binary' => $driverOptions['binary']]);
            $desiredCapabilities->setCapability('moz:firefoxOptions', $firefoxOptions);
        }
        if (isset($driverOptions['log'])) {
            $firefoxOptions = $desiredCapabilities->getCapability('moz:firefoxOptions');
            if (empty($firefoxOptions)) {
                $firefoxOptions = [];
            }
            $firefoxOptions = array_merge($firefoxOptions, ['log' => $driverOptions['log']]);
            $desiredCapabilities->setCapability('moz:firefoxOptions', $firefoxOptions);
        }
        if (isset($driverOptions['args'])) {
            $firefoxOptions = $desiredCapabilities->getCapability('moz:firefoxOptions');
            if (empty($firefoxOptions)) {
                $firefoxOptions = [];
            }
            $firefoxOptions = array_merge($firefoxOptions, ['args' => $driverOptions['args']]);
            $desiredCapabilities->setCapability('moz:firefoxOptions', $firefoxOptions);
        }
        $preferences = isset($driverOptions['preference']) ? $driverOptions['preference'] : [];
        foreach ($preferences as $key => $preference) {
            $capability->setPreference($key, $preference);
            // TODO
            // $capability->setRdfFile($key, $preference);
            // $capability->addExtensionDatas($key, $preference);
            // $capability->addExtension($key, $preference);
        }
        return $capability;
    }
}
