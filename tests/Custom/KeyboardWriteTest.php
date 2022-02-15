<?php declare(strict_types = 1);

namespace OAndreyev\Mink\Tests\Driver\Custom;

use Behat\Mink\Tests\Driver\TestCase;
use Facebook\WebDriver\WebDriverKeys;

class KeyboardWriteTest extends TestCase
{
    /**
     * @dataProvider keyboardEventsDataProvider
     *
     * @return void
     */
    public function testKeyboardEvents($inputValue, $inputExpected, $consoleExpected): void
    {
        if (getenv('BROWSER_NAME') === 'firefox') {
            $this->markTestSkipped('\OAndreyev\Mink\Tests\Driver\Custom\KeyboardWriteTest::testKeyboardEvents is skipped due to weird output from Firefox');
        }

        $this->getSession()->visit($this->pathTo('/keyboard_test.html'));
        $webAssert = $this->getAssertSession();

        $input = $webAssert->elementExists('css', '#test-target');
        $event = $webAssert->elementExists('css', '#console-log');

        $input->setValue($inputValue);
        $result = $input->getValue();
        $consoleLog = $event->getHtml();

        $this->assertEquals($inputExpected, $result);
        $this->assertEquals($consoleExpected, $consoleLog);
    }

    /**
     * @return \Generator
     *
     * @psalm-return \Generator<int, array{0: string, 1: '%'|'TEST'|'Test', 2: 'Key "Shift" pressed  [event: keydown]
Key "%" pressed  [event: keydown]
Key "%" pressed and released  [event: keypress]
Key "%" input  [event: input]
Key "%" released  [event: keyup]
Key "Shift" released  [event: keyup]
'|'Key "Shift" pressed  [event: keydown]
Key "T" pressed  [event: keydown]
Key "T" pressed and released  [event: keypress]
Key "T" input  [event: input]
Key "T" released  [event: keyup]
Key "E" pressed  [event: keydown]
Key "E" pressed and released  [event: keypress]
Key "E" input  [event: input]
Key "E" released  [event: keyup]
Key "S" pressed  [event: keydown]
Key "S" pressed and released  [event: keypress]
Key "S" input  [event: input]
Key "S" released  [event: keyup]
Key "T" pressed  [event: keydown]
Key "T" pressed and released  [event: keypress]
Key "T" input  [event: input]
Key "T" released  [event: keyup]
Key "Shift" released  [event: keyup]
'|'Key "Shift" pressed  [event: keydown]
Key "T" pressed  [event: keydown]
Key "T" pressed and released  [event: keypress]
Key "T" input  [event: input]
Key "T" released  [event: keyup]
Key "Shift" released  [event: keyup]
Key "e" pressed  [event: keydown]
Key "e" pressed and released  [event: keypress]
Key "e" input  [event: input]
Key "e" released  [event: keyup]
Key "s" pressed  [event: keydown]
Key "s" pressed and released  [event: keypress]
Key "s" input  [event: input]
Key "s" released  [event: keyup]
Key "t" pressed  [event: keydown]
Key "t" pressed and released  [event: keypress]
Key "t" input  [event: input]
Key "t" released  [event: keyup]
'}, mixed, void>
     */
    public function keyboardEventsDataProvider(): \Generator
    {
        yield [
            "Test",
            "Test",
            "Key \"Shift\" pressed  [event: keydown]
Key \"T\" pressed  [event: keydown]
Key \"T\" pressed and released  [event: keypress]
Key \"T\" input  [event: input]
Key \"T\" released  [event: keyup]
Key \"Shift\" released  [event: keyup]
Key \"e\" pressed  [event: keydown]
Key \"e\" pressed and released  [event: keypress]
Key \"e\" input  [event: input]
Key \"e\" released  [event: keyup]
Key \"s\" pressed  [event: keydown]
Key \"s\" pressed and released  [event: keypress]
Key \"s\" input  [event: input]
Key \"s\" released  [event: keyup]
Key \"t\" pressed  [event: keydown]
Key \"t\" pressed and released  [event: keypress]
Key \"t\" input  [event: input]
Key \"t\" released  [event: keyup]
"
        ];

        yield [
            WebDriverKeys::SHIFT . 't' . WebDriverKeys::SHIFT . 'est',
            "Test",
            "Key \"Shift\" pressed  [event: keydown]
Key \"T\" pressed  [event: keydown]
Key \"T\" pressed and released  [event: keypress]
Key \"T\" input  [event: input]
Key \"T\" released  [event: keyup]
Key \"Shift\" released  [event: keyup]
Key \"e\" pressed  [event: keydown]
Key \"e\" pressed and released  [event: keypress]
Key \"e\" input  [event: input]
Key \"e\" released  [event: keyup]
Key \"s\" pressed  [event: keydown]
Key \"s\" pressed and released  [event: keypress]
Key \"s\" input  [event: input]
Key \"s\" released  [event: keyup]
Key \"t\" pressed  [event: keydown]
Key \"t\" pressed and released  [event: keypress]
Key \"t\" input  [event: input]
Key \"t\" released  [event: keyup]
"
        ];

        yield [
            WebDriverKeys::SHIFT . '5',
            '%',
            "Key \"Shift\" pressed  [event: keydown]
Key \"%\" pressed  [event: keydown]
Key \"%\" pressed and released  [event: keypress]
Key \"%\" input  [event: input]
Key \"%\" released  [event: keyup]
Key \"Shift\" released  [event: keyup]
"
        ];

        yield [
            WebDriverKeys::SHIFT . 'test',
            'TEST',
            "Key \"Shift\" pressed  [event: keydown]
Key \"T\" pressed  [event: keydown]
Key \"T\" pressed and released  [event: keypress]
Key \"T\" input  [event: input]
Key \"T\" released  [event: keyup]
Key \"E\" pressed  [event: keydown]
Key \"E\" pressed and released  [event: keypress]
Key \"E\" input  [event: input]
Key \"E\" released  [event: keyup]
Key \"S\" pressed  [event: keydown]
Key \"S\" pressed and released  [event: keypress]
Key \"S\" input  [event: input]
Key \"S\" released  [event: keyup]
Key \"T\" pressed  [event: keydown]
Key \"T\" pressed and released  [event: keypress]
Key \"T\" input  [event: input]
Key \"T\" released  [event: keyup]
Key \"Shift\" released  [event: keyup]
"
        ];
    }
}
