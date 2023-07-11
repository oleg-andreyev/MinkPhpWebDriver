<?php

namespace OAndreyev\Mink\Tests\Driver;

use Behat\Mink\Tests\Driver\AbstractConfig;
use Facebook\WebDriver\Chrome\ChromeOptions;
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

    public static function getInstance(): self
    {
        return new self();
    }

    public function createDriver()
    {
        $browser = getenv('BROWSER_NAME') ?: 'firefox';
        $driverOptions = getenv('DRIVER_OPTIONS') ? \json_decode(getenv('DRIVER_OPTIONS'), true) : [];
        $seleniumHost = $_SERVER['DRIVER_URL'];

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new \RuntimeException('Failed to parse DRIVER_OPTIONS: '.json_last_error_msg());
        }

        if ('firefox' === $browser) {
            $desiredCapabilities = DesiredCapabilities::firefox();
        } elseif ('chrome' === $browser || 'msedge' === $browser) {
            $desiredCapabilities = DesiredCapabilities::chrome();
            if ('msedge' === $browser) {
                $desiredCapabilities->setBrowserName('msedge');
            }
        } else {
            $desiredCapabilities = new DesiredCapabilities();
        }

        $capabilityMap = [
            'firefox' => FirefoxDriver::PROFILE,
            'chrome' => ChromeOptions::CAPABILITY_W3C,
            'msedge' => ChromeOptions::CAPABILITY_W3C,
        ];

        if (isset($capabilityMap[$browser])) {
            $optionsOrProfile = $desiredCapabilities->getCapability($capabilityMap[$browser]);
            if ('chrome' === $browser || 'msedge' === $browser) {
                if (!$optionsOrProfile) {
                    $optionsOrProfile = new ChromeOptions();
                }
                $optionsOrProfile = $this->buildChromeOptions($desiredCapabilities, $optionsOrProfile, $driverOptions);
            } elseif ('firefox' === $browser) {
                $optionsOrProfile = new FirefoxProfile();
                $optionsOrProfile = $this->buildFirefoxProfile($desiredCapabilities, $optionsOrProfile, $driverOptions);
            }

            $desiredCapabilities->setCapability($capabilityMap[$browser], $optionsOrProfile);
        }

        $driver = new WebDriver($browser, [], $seleniumHost);
        $driver->setDesiredCapabilities($desiredCapabilities);

        // https://developer.mozilla.org/en-US/docs/Web/WebDriver/Commands/SetTimeouts
        $driver->setTimeouts(['implicit' => 0, 'pageLoad' => 300000, 'script' => 30000]);

        return $this->driver = $driver;
    }

    public function skipMessage($testCase, $test)
    {
        $desiredCapabilities = $this->driver->getDesiredCapabilities();
        $chromeOptions = $desiredCapabilities->getCapability(ChromeOptions::CAPABILITY_W3C);

        $headless = 'chrome' === $desiredCapabilities->getBrowserName()
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

    protected function supportsCss()
    {
        return true;
    }

    /**
     * @param array<string, mixed> $driverOptions
     */
    private function buildChromeOptions(DesiredCapabilities $desiredCapabilities, ChromeOptions $optionsOrProfile, array $driverOptions = []): ChromeOptions
    {
        $binary = $driverOptions['binary'] ?? null;
        $optionsOrProfile->setBinary($binary);

        $args = $driverOptions['args'] ?? [];
        $optionsOrProfile->addArguments($args);

        return $optionsOrProfile;

        // TODO
        // $capability->addEncodedExtension();
        // $capability->addExtension();
        // $capability->addEncodedExtensions();
        // $capability->addExtensions();
    }

    /**
     * @param array<string, mixed> $driverOptions
     */
    private function buildFirefoxProfile(DesiredCapabilities $desiredCapabilities, FirefoxProfile $optionsOrProfile, array $driverOptions): FirefoxProfile
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
            $optionsOrProfile->setPreference($key, $preference);
            // TODO
            // $capability->setRdfFile($key, $preference);
            // $capability->addExtensionDatas($key, $preference);
            // $capability->addExtension($key, $preference);
        }

        return $optionsOrProfile;
    }
}
