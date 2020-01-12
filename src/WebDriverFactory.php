<?php

namespace OAndreyev\Mink\Driver;

use Behat\MinkExtension\ServiceContainer\Driver\Selenium2Factory;
use Symfony\Component\DependencyInjection\Definition;

class WebDriverFactory extends Selenium2Factory
{
    /**
     * {@inheritdoc}
     */
    public function getDriverName()
    {
        return 'webdriver';
    }

    /**
     * {@inheritdoc}
     */
    public function buildDriver(array $config)
    {
        // Merge capabilities
        $extraCapabilities = $config['capabilities']['extra_capabilities'];
        unset($config['capabilities']['extra_capabilities']);
        $capabilities = array_replace($this->guessCapabilities(), $extraCapabilities, $config['capabilities']);

        // Build driver definition
        return new Definition(WebDriver::class, [
            $config['browser'],
            $capabilities,
            $config['wd_host'],
        ]);
    }

    /**
     * Guess capabilities from environment
     *
     * @return array
     */
    protected function guessCapabilities()
    {
        if (getenv('TRAVIS_JOB_NUMBER')) {
            return [
                'tunnel-identifier' => getenv('TRAVIS_JOB_NUMBER'),
                'build' => getenv('TRAVIS_BUILD_NUMBER'),
                'tags' => ['Travis-CI', 'PHP ' . phpversion()],
            ];
        }

        if (getenv('JENKINS_HOME')) {
            return [
                'tunnel-identifier' => getenv('JOB_NAME'),
                'build' => getenv('BUILD_NUMBER'),
                'tags' => ['Jenkins', 'PHP ' . phpversion(), getenv('BUILD_TAG')],
            ];
        }

        return [
            'tags' => [php_uname('n'), 'PHP ' . phpversion()],
        ];
    }
}