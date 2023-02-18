<?php

namespace OAndreyev\Mink\Tests\Driver\Custom;

use Behat\Mink\Exception\DriverException;
use Behat\Mink\Tests\Driver\TestCase;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use OAndreyev\Mink\Driver\WebDriver;

class DesiredCapabilitiesTest extends TestCase
{
    public function testGetDesiredCapabilities(): void
    {
        $expectedCaps = [
            'browserName' => 'firefox',
            'version' => '30',
            'platform' => 'ANY',
            'browserVersion' => '30',
            'browser' => 'firefox',
            'name' => 'Selenium2 Mink Driver Test',
            'deviceOrientation' => 'portrait',
            'deviceType' => 'tablet',
            'selenium-version' => '2.45.0',
        ];

        $driver = new WebDriver('firefox', $expectedCaps);
        $this->assertNotEmpty($driver->getDesiredCapabilities(), 'desiredCapabilities empty');
        $this->assertInstanceOf(DesiredCapabilities::class, $driver->getDesiredCapabilities());
        $toArray = $driver->getDesiredCapabilities()->toArray();
        foreach ($expectedCaps as $key => $v) {
            $this->assertEquals($expectedCaps[$key], $toArray[$key]);
        }
    }

    public function testSetDesiredCapabilities(): void
    {
        $this->expectException(DriverException::class);
        $this->expectExceptionMessage('Unable to set desiredCapabilities, the session has already started');

        $caps = [
            'browserName' => 'firefox',
            'version' => '30',
            'platform' => 'ANY',
            'browserVersion' => '30',
            'browser' => 'firefox',
            'name' => 'Selenium2 Mink Driver Test',
            'deviceOrientation' => 'portrait',
            'deviceType' => 'tablet',
            'selenium-version' => '2.45.0',
        ];
        $session = $this->getSession();
        $session->start();

        /** @var WebDriver $driver */
        $driver = $session->getDriver();
        $driver->setDesiredCapabilities($caps);
    }
}
