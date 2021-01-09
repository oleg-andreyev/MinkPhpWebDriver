<?php declare(strict_types = 1);

namespace OAndreyev\Mink\Tests\Driver;

use Behat\Mink\Session;
use Behat\Mink\Tests\Driver\TestCase;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Test;
use PHPUnit\Framework\TestListener;
use PHPUnit\Framework\TestSuite;
use PHPUnit\Framework\Warning;
use PHPUnit\Runner\AfterTestFailureHook;

class ScreenshotListener implements TestListener
{
    public function addError(Test $test, \Throwable $t, float $time): void
    {
        $this->makeScreenshot($test);
    }

    public function addWarning(Test $test, Warning $e, float $time): void
    {
    }

    public function addFailure(Test $test, AssertionFailedError $e, float $time): void
    {
        $this->makeScreenshot($test);
    }

    public function addIncompleteTest(Test $test, \Throwable $t, float $time): void
    {
    }

    public function addRiskyTest(Test $test, \Throwable $t, float $time): void
    {
    }

    public function addSkippedTest(Test $test, \Throwable $t, float $time): void
    {
    }

    public function startTestSuite(TestSuite $suite): void
    {
    }

    public function endTestSuite(TestSuite $suite): void
    {
    }

    public function startTest(Test $test): void
    {
    }

    public function endTest(Test $test, float $time): void
    {
    }

    private function makeScreenshot(Test $test): void
    {
        /** @var Session $session */
        $session = \Closure::bind(function () {
            /** @var TestCase $this */
            return $this->getSession();
        }, $test, $test)();

        if (!$session->isStarted()) {
            return;
        }

        $filename = str_replace(['#', ' ', '.', ','], '_', $test->getName());
        $session->getDriver()->getScreenshot(getcwd() . '/logs/' . $filename . '.png');
    }
}
