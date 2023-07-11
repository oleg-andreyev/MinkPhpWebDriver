<?php

namespace OAndreyev\Mink\Tests\Driver\Custom;

use Behat\Mink\Tests\Driver\TestCase;

class WindowNameTest extends TestCase
{
    public function testWindowNames(): void
    {
        $session = $this->getSession();
        $session->start();

        $windowNames = $session->getWindowNames();
        $this->assertArrayHasKey(0, $windowNames);

        $windowName = $session->getWindowName();

        $this->assertIsString($windowName);
        $this->assertContains($windowName, $windowNames, 'The current window name is one of the available window names.');
    }

    public function testReopenWindow(): void
    {
        $this->getSession()->visit($this->pathTo('/window.html'));
        $session = $this->getSession();
        $page = $session->getPage();
        $webAssert = $this->getAssertSession();

        $page->clickLink('Popup #1');
        $session->switchToWindow('popup_1');
        $el = $webAssert->elementExists('css', '#text');
        $this->assertSame('Popup#1 div text', $el->getText());

        $session->executeScript('window.close();');

        $session->switchToWindow(null);

        $page->clickLink('Popup #1');
        $session->switchToWindow('popup_1');
        $el = $webAssert->elementExists('css', '#text');
        $this->assertSame('Popup#1 div text', $el->getText());
    }

    public function testSwitchWindowAfterReset(): void
    {
        $session = $this->getSession();
        $page = $session->getPage();
        $webAssert = $this->getAssertSession();

        $session->restart();
        $session->visit($this->pathTo('/window.html'));
        $page->clickLink('Popup #1');
        $session->switchToWindow('popup_1');
        $el = $webAssert->elementExists('css', '#text');
        $this->assertSame('Popup#1 div text', $el->getText());

        $session->restart();
        $session->visit($this->pathTo('/window.html'));
        $page->clickLink('Popup #2');
        $session->switchToWindow('popup_2');
        $el = $webAssert->elementExists('css', '#text');
        $this->assertSame('Popup#2 div text', $el->getText());
    }
}
