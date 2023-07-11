<?php

namespace OAndreyev\Mink\Driver;

use Behat\MinkExtension\ServiceContainer\Driver\Selenium2Factory;
use Symfony\Component\DependencyInjection\Definition;

class WebDriverFactory extends Selenium2Factory
{
    public function getDriverName()
    {
        return 'webdriver';
    }

    /**
     * @param array<string, mixed> $config
     *
     * @return Definition
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
     * Guess capabilities from environment.
     *
     * @return array<string, mixed>
     */
    protected function guessCapabilities()
    {
        if (getenv('CI')) {
            return [
                'tunnel-identifier' => getenv('GITHUB_RUN_ID'),
                'build' => getenv('GITHUB_RUN_NUMBER'),
                'tags' => ['GitHub Actions', 'PHP '.PHP_VERSION],
            ];
        }

        return [
            'tags' => [php_uname('n'), 'PHP '.PHP_VERSION],
        ];
    }
}
