<?php

namespace OAndreyev\Mink\Tests\Driver\Custom;

use Behat\Mink\Session;
use Behat\Mink\Tests\Driver\TestCase;
use OAndreyev\Mink\Driver\WebDriver;

class TimeoutTest extends TestCase
{
    /** @var Session */
    private $session;

    /** @var WebDriver */
    private $driver;

    protected function setUp()
    {
        parent::setUp();
        $this->session = $this->getSession();
        $this->driver = $this->session->getDriver();
    }

    /**
     * @expectedException \Behat\Mink\Exception\DriverException
     */
    public function testInvalidTimeoutSettingThrowsException()
    {
        $this->session->start();
        $this->driver->setTimeouts(array('invalid' => 0));
    }

    public function testShortTimeoutDoesNotWaitForElementToAppear()
    {
        $this->driver->setTimeouts(array('implicit' => 0));

        $this->session->visit($this->pathTo('/js_test.html'));
        $this->findById('waitable')->click();

        $element = $this->session->getPage()->find('css', '#waitable > div');

        $this->assertNull($element);
    }

    public function testLongTimeoutWaitsForElementToAppear()
    {
        $this->driver->setTimeouts(array('implicit' => 5000));

        $this->session->visit($this->pathTo('/js_test.html'));
        $this->findById('waitable')->click();
        $element = $this->session->getPage()->find('css', '#waitable > div');

        $this->assertNotNull($element);
    }

    /**
     * @expectedException \Behat\Mink\Exception\DriverException
     */
    public function testPageLoadTimeout()
    {
        $this->driver->setTimeouts(array('pageLoad' => 1));
        $this->session->visit($this->pathTo('/page_load.php?sleep=2'));
    }

    /**
     * @expectedException \Behat\Mink\Exception\DriverException
     */
    public function testPageReloadTimeout()
    {
        $this->session->visit($this->pathTo('/page_load.php?sleep=2'));
        $this->driver->setTimeouts(array('pageLoad' => 1));
        $this->session->reload();
    }

    /**
     * @expectedException \Behat\Mink\Exception\DriverException
     */
    public function testScriptTimeout()
    {
        $this->driver->setTimeouts(array('script' => 1));
        $this->session->visit($this->pathTo('/js_test.html'));

        // @see https://w3c.github.io/webdriver/#execute-async-script
        $this->driver->executeAsyncScript(
            'var callback = arguments[arguments.length - 1];
            setTimeout(
                function(){
                    callback();
                 },
                2000
            );'
        );
    }
}
