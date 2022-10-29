<?php

namespace OAndreyev\Mink\Tests\Driver\Custom;

use Behat\Mink\Exception\DriverException;
use Behat\Mink\Session;
use Behat\Mink\Tests\Driver\OnNotSuccessfulTrait;
use Behat\Mink\Tests\Driver\TestCase;
use OAndreyev\Mink\Driver\WebDriver;

class TimeoutTest extends TestCase
{
    use OnNotSuccessfulTrait;

    /** @var Session */
    private $session;

    /** @var WebDriver */
    private $driver;

    /**
     * @before
     */
    protected function before()
    {
        $this->session = $this->getSession();
        $this->driver = $this->session->getDriver();
    }

    protected function tearDown(): void
    {
        // https://developer.mozilla.org/en-US/docs/Web/WebDriver/Commands/SetTimeouts
        $this->driver->setTimeouts(array('implicit' => 0, 'pageLoad' => 300000, 'script' => 30000));
    }


    public function testInvalidTimeoutSettingThrowsException()
    {
        $this->expectException(DriverException::class);
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

    public function testPageLoadTimeout()
    {
        $this->expectException(DriverException::class);
        $this->driver->setTimeouts(array('pageLoad' => 1));
        $this->session->visit($this->pathTo('/page_load.php?sleep=2'));
    }

    public function testPageReloadTimeout()
    {
        $this->expectException(DriverException::class);
        $this->session->visit($this->pathTo('/page_load.php?sleep=2'));
        $this->driver->setTimeouts(array('pageLoad' => 1));
        $this->session->reload();
    }

    public function testScriptTimeout()
    {
        $this->expectException(DriverException::class);
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
