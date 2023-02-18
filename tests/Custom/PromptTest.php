<?php

declare(strict_types=1);

namespace OAndreyev\Mink\Tests\Driver\Custom;

use Behat\Mink\Tests\Driver\TestCase;
use OAndreyev\Mink\Driver\WebDriver;

class PromptTest extends TestCase
{
    public function testPromptSendKeysAndAccept(): void
    {
        $session = $this->getSession();
        $session->visit($this->pathTo('/prompt.html'));
        /** @var WebDriver $driver */
        $driver = $session->getDriver();

        $alert = $driver->getCurrentPromptOrAlert();
        self::assertEquals('Can you handle this?', $alert->getText());

        // @see https://bugs.chromium.org/p/chromedriver/issues/detail?id=1120#c11
        $alert->sendKeys('yes');
        $alert->accept();

        $element = $session->getPage()->find('css', '#prompt_result');
        self::assertEquals('Prompt Result: yes', $element->getText());
    }

    public function testPromptDismiss(): void
    {
        $session = $this->getSession();
        $session->visit($this->pathTo('/prompt.html'));
        /** @var WebDriver $driver */
        $driver = $session->getDriver();

        $alert = $driver->getCurrentPromptOrAlert();
        self::assertEquals('Can you handle this?', $alert->getText());

        // @see https://bugs.chromium.org/p/chromedriver/issues/detail?id=1120#c11
        $alert->sendKeys('yes');
        $alert->dismiss();

        $element = $session->getPage()->find('css', '#prompt_result');
        self::assertNull($element);
    }
}
