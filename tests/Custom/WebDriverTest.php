<?php

namespace OAndreyev\Mink\Tests\Driver\Custom;

use Behat\Mink\Tests\Driver\TestCase;
use OAndreyev\Mink\Driver\WebDriver;

class WebDriverTest extends TestCase
{
    public function testGetWebDriverSessionId(): void
    {
        $session = $this->getSession();
        $session->start();
        /** @var WebDriver $driver */
        $driver = $session->getDriver();
        $this->assertNotEmpty($driver->getWebDriverSessionId(), 'Started session has an ID');

        $driver = new WebDriver();
        $this->assertNull($driver->getWebDriverSessionId(), 'Not started session don\'t have an ID');
    }
}
