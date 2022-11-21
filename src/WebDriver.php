<?php

/*
 * This file is part of the Behat\Mink.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace OAndreyev\Mink\Driver;

use Behat\Mink\Driver\CoreDriver;
use Behat\Mink\Exception\DriverException;
use Behat\Mink\Exception\UnsupportedDriverActionException;
use Facebook\WebDriver\Cookie;
use Facebook\WebDriver\Exception\NoSuchCookieException;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\ScriptTimeoutException;
use Facebook\WebDriver\Exception\TimeOutException;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\LocalFileDetector;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\RemoteWebElement;
use Facebook\WebDriver\WebDriverAlert;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverDimension;
use Facebook\WebDriver\WebDriverElement;
use Facebook\WebDriver\WebDriverKeys as Keys;
use Facebook\WebDriver\WebDriverRadios;
use Facebook\WebDriver\WebDriverSelect;
use Facebook\WebDriver\Exception\ElementNotInteractableException;

/**
 * WebDriver driver.
 *
 * @author Oleg Andreyev <oleg@andreyev.lv>
 */
class WebDriver extends CoreDriver
{
    const MODIFIER_KEYS = [
        Keys::SHIFT, Keys::CONTROL, Keys::ALT, Keys::META, Keys::COMMAND,
        Keys::LEFT_ALT, Keys::LEFT_CONTROL, Keys::LEFT_SHIFT
    ];

    /**
     * The WebDriver instance
     *
     * @var RemoteWebDriver
     */
    private $webDriver;

    /**
     * @var string
     */
    private $browserName;

    /**
     * @var DesiredCapabilities|null
     */
    private $desiredCapabilities;

    /**
     * The timeout configuration
     *
     * @var array
     */
    private $timeouts = array();

    /**
     * Wd host
     *
     * @var string
     */
    private $wdHost;

    /**
     * @var string
     */
    private $rootWindow;

    /**
     * @var array<string, string>
     */
    private $windows = [];

    /**
     * Instantiates the driver.
     *
     * @param string $browserName         Browser name
     * @param array  $desiredCapabilities The desired capabilities
     * @param string $wdHost              The WebDriver host
     */
    public function __construct($browserName = 'firefox', $desiredCapabilities = null, $wdHost = 'http://localhost:4444/wd/hub')
    {
        $this->wdHost = $wdHost;
        $this->browserName = $browserName;

        if ($browserName === 'firefox') {
            $this->desiredCapabilities = DesiredCapabilities::firefox();
        } else if ($browserName === 'chrome') {
            $this->desiredCapabilities = DesiredCapabilities::chrome();
        } else {
            $this->desiredCapabilities = new DesiredCapabilities();
        }

        if ($desiredCapabilities) {
            foreach ($desiredCapabilities as $key => $val) {
                $this->desiredCapabilities->setCapability($key, $val);
            }
        }
    }

    /**
     * Sets the timeouts to apply to the webdriver session
     *
     * @param array $timeouts The session timeout settings: Array of {script, implicit, page} => time in milliseconds
     */
    public function setTimeouts(array $timeouts)
    {
        // TODO: driver does not have getTimeouts
        $this->timeouts = $timeouts;

        if ($this->isStarted()) {
            $this->applyTimeouts();
        }
    }

    /**
     * Applies timeouts to the current session
     */
    private function applyTimeouts()
    {
        // @see https://w3c.github.io/webdriver/#set-timeouts
        $timeouts = $this->webDriver->manage()->timeouts();
        if (isset($this->timeouts['implicit'])) {
            $timeouts->implicitlyWait($this->timeouts['implicit']);
        } else if (isset($this->timeouts['pageLoad'])) {
            $timeouts->pageLoadTimeout($this->timeouts['pageLoad']);
        } else if (isset($this->timeouts['script'])) {
            $timeouts->setScriptTimeout($this->timeouts['script']);
        } else {
            throw new DriverException('Invalid timeout option');
        }
    }

    /**
     * Sets the browser name
     *
     * @param string $browserName the name of the browser to start, default is 'firefox'
     */
    protected function setBrowserName($browserName = 'firefox')
    {
        $this->browserName = $browserName;
    }

    /**
     * Sets the desired capabilities - called on construction.  If null is provided, will set the
     * defaults as desired.
     *
     * See http://code.google.com/p/selenium/wiki/DesiredCapabilities
     *
     * @param DesiredCapabilities|array|null $desiredCapabilities
     *
     * @throws DriverException
     */
    public function setDesiredCapabilities($desiredCapabilities = null)
    {
        if ($this->isStarted()) {
            throw new DriverException('Unable to set desiredCapabilities, the session has already started');
        }

        if (is_array($desiredCapabilities)) {
            $desiredCapabilities = new DesiredCapabilities($desiredCapabilities);
        } else if ($desiredCapabilities === null) {
            $desiredCapabilities = new DesiredCapabilities();
        }

        $this->desiredCapabilities = $desiredCapabilities;
    }

    /**
     * Gets the desiredCapabilities
     *
     * @return DesiredCapabilities
     */
    public function getDesiredCapabilities()
    {
        return $this->desiredCapabilities;
    }

    /**
     * @return WebDriver
     */
    public function getWebDriver()
    {
        return $this->webDriver;
    }

    /**
     * Returns the default capabilities
     *
     * @return array
     */
    public static function getDefaultCapabilities()
    {
        return array(
            'browserName' => 'firefox',
            'name'        => 'Behat Test',
        );
    }

    /**
     * Executes JS on a given element - pass in a js script string and {{ELEMENT}} will
     * be replaced with a reference to the result of the $xpath query
     *
     * @example $this->executeJsOnXpath($xpath, 'return {{ELEMENT}}.childNodes.length');
     *
     * @param string  $xpath  the xpath to search with
     * @param string  $script the script to execute
     * @param Boolean $sync   whether to run the script synchronously (default is TRUE)
     *
     * @return mixed
     */
    private function executeJsOnXpath($xpath, $script, $sync = true)
    {
        $element = $this->findElement($xpath);
        return $this->executeJsOnElement($element, $script, $sync);
    }

    /**
     * Executes JS on a given element - pass in a js script string and {{ELEMENT}} will
     * be replaced with a reference to the element
     *
     * @example $this->executeJsOnXpath($xpath, 'return {{ELEMENT}}.childNodes.length');
     *
     * @param WebDriverElement $element the webdriver element
     * @param string           $script  the script to execute
     * @param Boolean          $sync    whether to run the script synchronously (default is TRUE)
     *
     * @return mixed
     */
    private function executeJsOnElement(WebDriverElement $element, $script, $sync = true)
    {
        $script = str_replace('{{ELEMENT}}', 'arguments[0]', $script);

        if ($sync) {
            return $this->webDriver->executeScript($script, [$element]);
        }

        return $this->webDriver->executeAsyncScript($script, [$element]);
    }

    /**
     * {@inheritdoc}
     */
    public function start()
    {
        if ($this->webDriver) {
            return $this->webDriver;
        }

        try {
            $this->webDriver = RemoteWebDriver::create($this->wdHost, $this->desiredCapabilities);
            if (\count($this->timeouts)) {
                $this->applyTimeouts();
            }
            $this->rootWindow = $this->webDriver->getWindowHandle();
            $this->windows = [];
        } catch (\Exception $e) {
            throw new DriverException('Could not open connection: ' . $e->getMessage(), 0, $e);
        }

        if (!$this->webDriver) {
            throw new DriverException('Could not connect to a WebDriver server');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isStarted()
    {
        return $this->webDriver !== null;
    }

    /**
     * {@inheritdoc}
     */
    public function stop()
    {
        if (!$this->webDriver) {
            throw new DriverException('Could not connect to a Selenium 2 / WebDriver server');
        }

        try {
            $this->webDriver->quit();
            $this->webDriver = null;
        } catch (\Exception $e) {
            throw new DriverException('Could not close connection', 0, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function reset()
    {
        $this->webDriver->manage()->deleteAllCookies();
        // TODO: resizeWindow does not accept NULL
        $this->maximizeWindow();
        // reset timeout
        $this->timeouts = [];
    }

    /**
     * {@inheritdoc}
     */
    public function visit($url)
    {
        try {
            $this->webDriver->navigate()->to($url);
        } catch (TimeOutException $e) {
            throw new DriverException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentUrl()
    {
        return $this->webDriver->getCurrentURL();
    }

    /**
     * {@inheritdoc}
     */
    public function reload()
    {
        try {
            $this->webDriver->navigate()->refresh();
        } catch (TimeOutException $e) {
            throw new DriverException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function forward()
    {
        $this->webDriver->navigate()->forward();
    }

    /**
     * {@inheritdoc}
     */
    public function back()
    {
        $this->webDriver->navigate()->back();
    }

    /**
     * {@inheritdoc}
     */
    public function switchToWindow($name = null)
    {
        if ($this->browserName === 'firefox') {
            // Firefox stores window IDs rather than window names and does not provide a working way to map the ids to
            // names.
            // Each time we switch to a window, we fetch the list of window IDs, and attempt to map them.
            // This involves switching to that window and fetching the window.name.
            // @see https://github.com/mozilla/geckodriver/issues/149
            $handles = [];
            foreach ($this->getWindowNames() as $id) {
                if ($id === $this->rootWindow) {
                    // Do not put the root window into the list of handles.
                    continue;
                }

                $title = array_search($id, $this->windows, true);
                if ($title !== false) {
                    // This window is current and the name already stored.
                    // Use the currently stored id from $this->windows to avoid switching window unnecessarily.
                    $handles[$title] = $id;
                } else {
                    // This window title is unknown. Switch to the window by ID and find the name.
                    $this->webDriver->switchTo()->window($id);
                    $title = $this->evaluateScript('window.name');

                    $handles[$title] = $id;
                }
            }

            // Store the window name => id mappings.
            $this->windows = $handles;

            if (null === $name) {
                $name = $this->rootWindow;
            } else if (array_key_exists($name, $this->windows)) {
                $name = $this->windows[$name];
            }
        }

        $this->webDriver->switchTo()->window($name);
    }

    /**
     * {@inheritdoc}
     */
    public function switchToIFrame($name = null)
    {
        if ($name) {
            $element = $this->webDriver->findElement(WebDriverBy::name($name));
            $this->webDriver->switchTo()->frame($element);
        } else {
            $this->webDriver->switchTo()->defaultContent();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setCookie($name, $value = null)
    {
        if (null === $value) {
            $this->webDriver->manage()->deleteCookieNamed($name);

            return;
        }

        $cookie = new Cookie($name, \rawurlencode($value));
        $this->webDriver->manage()->addCookie($cookie);
    }

    /**
     * {@inheritdoc}
     */
    public function getCookie($name)
    {
        try {
            $cookie = $this->webDriver->manage()->getCookieNamed($name);
        } catch (NoSuchCookieException $e) {
            return null;
        }

        return \rawurldecode($cookie->getValue());
    }

    /**
     * {@inheritdoc}
     */
    public function getContent()
    {
        $source = $this->webDriver->getPageSource();
        return str_replace(array("\r", "\r\n", "\n"), \PHP_EOL, $source);
    }

    /**
     * {@inheritdoc}
     */
    public function getScreenshot($save_as = null)
    {
        return $this->webDriver->takeScreenshot($save_as);
    }

    /**
     * {@inheritdoc}
     */
    public function getWindowNames()
    {
        return $this->webDriver->getWindowHandles();
    }

    /**
     * {@inheritdoc}
     */
    public function getWindowName()
    {
        return $this->webDriver->getWindowHandle();
    }

    /**
     * {@inheritdoc}
     */
    public function findElementXpaths($xpath)
    {
        $nodes = $this->webDriver->findElements(WebDriverBy::xpath($xpath));

        $elements = array();
        foreach ($nodes as $i => $node) {
            $elements[] = sprintf('(%s)[%d]', $xpath, $i + 1);
        }

        return $elements;
    }

    /**
     * {@inheritdoc}
     */
    public function getTagName($xpath)
    {
        $element = $this->findElement($xpath);
        return $element->getTagName();
    }

    /**
     * {@inheritdoc}
     */
    public function getText($xpath)
    {
        $element = $this->findElement($xpath);
        $text = $element->getText();

        $text = (string) str_replace(array("\r", "\r\n", "\n"), ' ', $text);

        return $text;
    }

    /**
     * {@inheritdoc}
     */
    public function getHtml($xpath)
    {
        return $this->executeJsOnXpath($xpath, 'return {{ELEMENT}}.innerHTML;');
    }

    /**
     * {@inheritdoc}
     */
    public function getOuterHtml($xpath)
    {
        return $this->executeJsOnXpath($xpath, 'return {{ELEMENT}}.outerHTML;');
    }

    /**
     * {@inheritdoc}
     */
    public function getAttribute($xpath, $name)
    {
        $element = $this->findElement($xpath);

        /**
         * If attribute is present but does not have value, it's considered as Boolean Attributes https://html.spec.whatwg.org/#boolean-attributes
         * but here result may be unexpected in case of <element my-attr/>, my-attr should return TRUE, but it will return "empty string"
         *
         * @see https://w3c.github.io/webdriver/#get-element-attribute
         */
        $hasAttribute = $this->hasAttribute($element, $name);
        if ($hasAttribute) {
            $value = $element->getAttribute($name);
        } else {
            $value = null;
        }

        return $value;
    }

    /**
     * @param WebDriverElement $element
     * @param string           $name
     *
     * @return bool
     */
    private function hasAttribute(WebDriverElement $element, $name)
    {
        return $this->executeJsOnElement($element, "return {{ELEMENT}}.hasAttribute('$name')");
    }

    /**
     * {@inheritdoc}
     */
    public function getValue($xpath)
    {
        $element = $this->findElement($xpath);
        $elementName = strtolower($element->getTagName());
        $elementType = strtolower($element->getAttribute('type') ?: 'text');

        // Getting the value of a checkbox returns its value if selected.
        if ('input' === $elementName && 'checkbox' === $elementType) {
            return $element->isSelected() ? $element->getAttribute('value') : null;
        }

        if ('input' === $elementName && 'radio' === $elementType) {
            $radios = new WebDriverRadios($element);
            try {
                return $radios->getFirstSelectedOption()->getAttribute('value');
            } catch (NoSuchElementException $e) {
                // TODO: Need to distinguish missing element and no radio selected
                if ($e->getMessage() === 'No radio buttons are selected') {
                    return null;
                }

                throw $e;
            }
        }

        // Using $element->attribute('value') on a select only returns the first selected option
        // even when it is a multiple select, so a custom retrieval is needed.
        if ('select' === $elementName) {
            $select = new WebDriverSelect($element);
            if ($select->isMultiple()) {
                return \array_map(function (WebDriverElement $element) {
                    return $element->getAttribute('value');
                }, $select->getAllSelectedOptions());
            }

            try {
                return $select->getFirstSelectedOption()->getAttribute('value');
            } catch (NoSuchElementException $e) {
                // TODO: Need to distinguish missing element and no option selected
                if ($e->getMessage() === 'No options are selected') {
                    return '';
                }

                throw $e;
            }
        }

        return $element->getAttribute('value');
    }

    /**
     * {@inheritdoc}
     */
    public function setValue($xpath, $value)
    {
        $element = $this->findElement($xpath);
        $elementName = strtolower($element->getTagName());

        if ('select' === $elementName) {
            $select = new WebDriverSelect($element);

            if (is_array($value)) {
                $select->deselectAll();
                foreach ($value as $option) {
                    $select->selectByValue($option);
                }

                return;
            }

            $select->selectByValue($value);

            return;
        }

        if ('input' === $elementName) {
            $elementType = strtolower($element->getAttribute('type') ?: 'text');

            if (in_array($elementType, array('submit', 'image', 'button', 'reset'))) {
                throw new DriverException(sprintf('Impossible to set value an element with XPath "%s" as it is not a select, textarea or textbox', $xpath));
            }

            if ('checkbox' === $elementType) {
                if ($element->isSelected() xor (bool) $value) {
                    $this->clickOnElement($element);
                }

                return;
            }

            if ('radio' === $elementType) {
                $radios = new WebDriverRadios($element);
                $radios->selectByValue($value);
                return;
            }

            if ('file' === $elementType) {
                $this->attachFile($xpath, $value);
                return;
            }

            // WebDriver does not support setting value in color inputs.
            // Each OS will show native color picker
            // See https://code.google.com/p/selenium/issues/detail?id=7650
            if ('color' === $elementType) {
                $this->executeJsOnElement($element, sprintf('return {{ELEMENT}}.value = "%s"', $value));
                return;
            }

            // See https://developer.mozilla.org/en-US/docs/Web/API/HTMLInputElement
            if ('date' === $elementType || 'time' === $elementType) {
                $date = date(DATE_ATOM, strtotime($value));
                $this->executeJsOnElement($element, sprintf('return {{ELEMENT}}.valueAsDate = new Date("%s")', $date));
                return;
            }
        }

        $value = (string) $value;

        if (in_array($elementName, array('input', 'textarea'))) {
            $existingValueLength = strlen($element->getAttribute('value'));
            // Add the TAB key to ensure we unfocus the field as browsers are triggering the change event only
            // after leaving the field.
            $value = str_repeat(Keys::BACKSPACE . Keys::DELETE, $existingValueLength) . $value;
        }

        $element->sendKeys($value);

        // Trigger a change event.
        $script = <<<EOF
{{ELEMENT}}.dispatchEvent(new Event("change", {
    bubbles: true,
    cancelable: false,
}));
EOF;

        $this->executeJsOnXpath($xpath, $script);
    }

    /**
     * {@inheritdoc}
     */
    public function check($xpath)
    {
        $element = $this->findElement($xpath);
        $this->ensureInputType($element, $xpath, 'checkbox', 'check');

        if ($element->isSelected()) {
            return;
        }

        $this->clickOnElement($element);
    }

    /**
     * {@inheritdoc}
     */
    public function uncheck($xpath)
    {
        $element = $this->findElement($xpath);
        $this->ensureInputType($element, $xpath, 'checkbox', 'uncheck');

        if (!$element->isSelected()) {
            return;
        }

        $this->clickOnElement($element);
    }

    /**
     * {@inheritdoc}
     */
    public function isChecked($xpath)
    {
        return $this->isSelected($xpath);
    }

    /**
     * {@inheritdoc}
     */
    public function selectOption($xpath, $value, $multiple = false)
    {
        $element = $this->findElement($xpath);
        $tagName = strtolower($element->getTagName());

        if ('input' === $tagName && 'radio' === strtolower($element->getAttribute('type') ?: '')) {
            $element = new WebDriverRadios($element);
            $element->selectByValue($value);
            return;
        }

        if ('select' === $tagName) {
            $element = new WebDriverSelect($element);
            if (!$multiple && $element->isMultiple()) {
                $element->deselectAll();
            }

            try {
                $element->selectByValue($value);
            } catch (NoSuchElementException $e) {
                // option may not have value attribute, so try to select by visible text
                $element->selectByVisibleText($value);
            }

            return;
        }

        throw new DriverException(sprintf('Impossible to select an option on the element with XPath "%s" as it is not a select or radio input', $xpath));
    }

    /**
     * {@inheritdoc}
     */
    public function isSelected($xpath)
    {
        $element = $this->findElement($xpath);
        return $element->isSelected();
    }

    /**
     * {@inheritdoc}
     */
    public function click($xpath)
    {
        $element = $this->findElement($xpath);
        $this->clickOnElement($element);
    }

    /**
     * Attempt to ensure that the node is in the viewport.
     *
     * @param WebDriverElement $element
     */
    private function scrollElementIntoViewIfRequired(WebDriverElement $element)
    {
        $js = <<<EOF
    var node = {{ELEMENT}};

    var rect = node.getBoundingClientRect();
    var nodeAtRect = document.elementFromPoint(rect.left + (rect.width / 2), rect.top + (rect.height / 2));

    if (!node.contains(nodeAtRect)) {
        node.scrollIntoView();
    }
EOF;
        $this->executeJsOnElement($element, $js);
    }

    private function clickOnElement(WebDriverElement $element)
    {
        if ($this->browserName === 'firefox') {
            // TODO: Raise a bug against geckodrvier.
            // Firefox does not move cursor over an element in breach of https://w3c.github.io/webdriver/#element-click
            // section 8.Otherwise.
            $this->scrollElementIntoViewIfRequired($element);
            $this->mouseOverElement($element);
        }

        if ($this->browserName === 'firefox') {
            try {
                $element->click();
            } catch (ElementNotInteractableException $e) {
                // There is a bug in Geckodriver which means that it is unable to click any link which contains a block
                // level node. See https://github.com/mozilla/geckodriver/issues/653.
                // The workaround is to click on a descendant node instead.
                $children = $element->findElements(WebDriverBy::xpath('./*'));
                foreach ($children as $child) {
                    // Call ourselves recursively surpressing the same ElementNotInteractableException exception until
                    // we run out of potential children to click.
                    try {
                        $this->clickOnElement($child);
                        return;
                    } catch (ElementNotInteractableException $e) {
                    }
                }

                throw $e;
            }
        } else {
            $element->click();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function doubleClick($xpath)
    {
        $element = $this->findElement($xpath);
        $this->webDriver->action()->doubleClick($element)->perform();
    }

    /**
     * {@inheritdoc}
     */
    public function rightClick($xpath)
    {
        $element = $this->findElement($xpath);
        $this->webDriver->action()->contextClick($element)->perform();
    }

    /**
     * {@inheritdoc}
     */
    public function attachFile($xpath, $path)
    {
        $element = $this->findElement($xpath);
        $this->ensureInputType($element, $xpath, 'file', 'attach a file on');

        $element->setFileDetector(new LocalFileDetector());
        return $element->sendKeys($path);
    }

    /**
     * {@inheritdoc}
     */
    public function isVisible($xpath)
    {
        $element = $this->findElement($xpath);
        return $element->isDisplayed();
    }

    /**
     * {@inheritdoc}
     */
    public function mouseOver($xpath)
    {
        $element = $this->findElement($xpath);
        $this->webDriver->action()->moveToElement($element)->perform();
    }

    private function mouseOverElement(WebDriverElement $element)
    {
        $this->webDriver->action()->moveToElement($element)->perform();
    }

    /**
     * {@inheritdoc}
     */
    public function focus($xpath)
    {
        $element = $this->findElement($xpath);
        $action = $this->webDriver->action();

        $action->moveToElement($element)->perform();

        // @see https://developer.mozilla.org/en-US/docs/Web/API/HTMLElement/focus
        $this->executeJsOnElement($element, 'return {{ELEMENT}}.focus()');
    }

    /**
     * {@inheritdoc}
     */
    public function blur($xpath)
    {
        $element = $this->findElement($xpath);

        // @see https://developer.mozilla.org/en-US/docs/Web/API/HTMLElement/blur
        $this->executeJsOnElement($element, 'return {{ELEMENT}}.blur()');
    }

    /**
     * {@inheritdoc}
     */
    public function keyPress($xpath, $char, $modifier = null)
    {
        $this->sendKey($xpath, $char, $modifier);
    }

    /**
     * Performs a modifier key press. Does not release the modifier key - subsequent interactions
     * may assume it's kept pressed.
     * Note that the modifier key is <b>never</b> released implicitly - either
     * <i>keyUp(theKey)</i> or <i>sendKeys(Keys.NULL)</i>
     * must be called to release the modifier.
     *
     * @param string $xpath
     * @param string $key Either {@link Keys::SHIFT}, {@link Keys::ALT} or {@link Keys::CONTROL}.
     *                    If the provided key is none of those, {@link InvalidArgumentException} is thrown.
     * @param null $modifier @deprecated
     *
     * @throws \InvalidArgumentException
     *
     * @return void
     */
    public function keyDown($xpath, $key, $modifier = null)
    {
        // Own implementation of https://github.com/php-webdriver/php-webdriver/pull/803
        $element = $this->findElement($xpath);

        $action = $this->webDriver->action();
        $keyModifier = $this->keyModifier($key);

        if (!in_array($keyModifier, self::MODIFIER_KEYS, true)) {
            throw new \InvalidArgumentException('Key Down / Up events only make sense for modifier keys.');
        }

        $action->keyDown($element, $keyModifier);
        $action->perform();
    }

    /**
     * Performs a modifier key release. Releasing a non-depressed modifier key will yield undefined
     * behaviour.
     *
     * @param string $xpath
     * @param string $key Either {@link Keys::SHIFT}, {@link Keys::ALT} or {@link Keys::CONTROL}.
     *                    If the provided key is none of those, {@link InvalidArgumentException} is thrown.
     *
     * @param null $modifier @deprecated
     *
     * @throws \InvalidArgumentException
     *
     * @return void
     */
    public function keyUp($xpath, $key, $modifier = null)
    {
        // Own implementation of https://github.com/php-webdriver/php-webdriver/pull/803
        $element = $this->findElement($xpath);

        $action = $this->webDriver->action();
        $keyModifier = $this->keyModifier($key);

        if (!in_array($keyModifier, self::MODIFIER_KEYS, true)) {
            throw new \InvalidArgumentException('Key Down / Up events only make sense for modifier keys.');
        }

        $action->keyUp($element, $keyModifier);
        $action->perform();
    }

    /**
     * {@inheritdoc}
     */
    public function dragTo($sourceXpath, $destinationXpath)
    {
        $source = $this->findElement($sourceXpath);
        $destination = $this->findElement($destinationXpath);
        $action = $this->webDriver->action();

        $action->dragAndDrop($source, $destination);
        $action->perform();
    }

    /**
     * {@inheritdoc}
     */
    public function executeScript($script)
    {
        if (preg_match('/^function[\s\(]/', $script)) {
            $script = preg_replace('/;$/', '', $script);
            $script = '(' . $script . ')';
        }

        $this->webDriver->executeScript($script);
    }

    public function executeAsyncScript($script)
    {
        if (preg_match('/^function[\s\(]/', $script)) {
            $script = preg_replace('/;$/', '', $script);
            $script = '(' . $script . ')';
        }

        try {
            $this->webDriver->executeAsyncScript($script);
        } catch (ScriptTimeoutException $e) {
            throw new DriverException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function evaluateScript($script)
    {
        if (0 !== strpos(trim($script), 'return ')) {
            $script = 'return ' . $script;
        }

        return $this->webDriver->executeScript($script);
    }

    /**
     * {@inheritdoc}
     */
    public function wait($timeout, $condition)
    {
        $seconds = $timeout / 1000.0;
        $wait = $this->webDriver->wait($seconds);

        if (is_string($condition)) {
            $script = "return $condition;";
            $condition = static function (RemoteWebDriver $driver) use ($script) {
                return $driver->executeScript($script);
            };
        }

        try {
            return (bool) $wait->until($condition);
        } catch (TimeOutException $e) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function resizeWindow($width, $height, $name = null)
    {
        $dimension = new WebDriverDimension($width, $height);
        if ($name) {
            throw new UnsupportedDriverActionException('Named windows are not supported yet');
        }

        $this->webDriver->manage()->window()->setSize($dimension);
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm($xpath)
    {
        $element = $this->findElement($xpath);
        $element->submit();
    }

    /**
     * {@inheritdoc}
     */
    public function maximizeWindow($name = null)
    {
        if ($name) {
            throw new UnsupportedDriverActionException('Named window is not supported');
        }

        $this->webDriver->manage()->window()->maximize();
    }

    /**
     * Returns Session ID of WebDriver or `null`, when session not started yet.
     *
     * @return string|null
     */
    public function getWebDriverSessionId()
    {
        if (!$this->isStarted()) {
            return null;
        }

        return $this->webDriver->getSessionID();
    }

    /**
     * @param string $xpath
     *
     * @return RemoteWebElement
     */
    private function findElement($xpath)
    {
        return $this->webDriver->findElement(WebDriverBy::xpath($xpath));
    }

    /**
     * Ensures the element is a checkbox
     *
     * @param WebDriverElement $element
     * @param string           $xpath
     * @param string           $type
     * @param string           $action
     *
     * @throws DriverException
     */
    private function ensureInputType(WebDriverElement $element, $xpath, $type, $action)
    {
        if ('input' !== strtolower($element->getTagName()) || $type !== strtolower($element->getAttribute('type') ?: 'text')) {
            $message = 'Impossible to %s the element with XPath "%s" as it is not a %s input';

            throw new DriverException(sprintf($message, $action, $xpath, $type));
        }
    }

    /**
     * Converts alt/ctrl/shift/meta to corresponded Keys::* constant
     *
     * @param string $modifier
     *
     * @return string
     */
    private function keyModifier($modifier)
    {
        if ($modifier === 'alt') {
            $modifier = Keys::ALT;
        } else if ($modifier === 'left alt') {
            $modifier = Keys::LEFT_ALT;
        } else if ($modifier === 'ctrl') {
            $modifier = Keys::CONTROL;
        } else if ($modifier === 'left ctrl') {
            $modifier = Keys::LEFT_CONTROL;
        } else if ($modifier === 'shift') {
            $modifier = Keys::SHIFT;
        } else if ($modifier === 'left shift') {
            $modifier = Keys::LEFT_SHIFT;
        } else if ($modifier === 'meta') {
            $modifier = Keys::META;
        } else if ($modifier === 'command') {
            $modifier = Keys::COMMAND;
        }

        return $modifier;
    }

    /**
     * Decodes char
     *
     * @param int|string $char if int is passed it will be converted to char using `chr` function
     *
     * @return string
     */
    private function decodeChar($char)
    {
        if (\is_numeric($char)) {
            return \chr($char);
        }

        return $char;
    }

    /**
     * @param $xpath
     * @param $char
     * @param $modifier
     */
    private function sendKey($xpath, $char, $modifier)
    {
        $element = $this->findElement($xpath);
        $char = $this->decodeChar($char);
        $element->sendKeys(($modifier ? $this->keyModifier($modifier) : '') . $char);
    }

    public function getCurrentPromptOrAlert(): ?WebDriverAlert
    {
        if (!$this->isStarted()) {
            return null;
        }

        return $this->webDriver->switchTo()->alert();
    }
}
