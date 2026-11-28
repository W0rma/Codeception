<?php

declare(strict_types=1);

namespace Codeception\Lib;

use Codeception\Attribute\Group;
use Codeception\Codecept;
use ReflectionProperty;

class ParamsLoaderTest extends \Codeception\Test\Unit
{
    #[Group('core')]
    public function testPhpConstantsInYamlFile()
    {
        $params = ParamsLoader::load(codecept_data_dir('params/params_constants.yml'));

        $this->assertSame(PHP_INT_MAX, $params['global']);
        $this->assertSame(Codecept::VERSION, $params['class']);
    }

    #[Group('core')]
    public function testDotEnvFileDoesNotOverrideEnvironmentVariables()
    {
        $this->setEnvironmentVariable('CODECEPT_DOTENV_EXISTING', 'from_env');

        $params = ParamsLoader::load(codecept_data_dir('params/existing_vars.env'));

        $this->assertSame('from_file', $params['CODECEPT_DOTENV_EXISTING']);
        $this->assertSame('from_file', $params['CODECEPT_DOTENV_NEW']);
        $this->assertSame('from_env', $params['CODECEPT_DOTENV_REFERENCE']);
        $this->assertSame('from_file', $params['CODECEPT_DOTENV_NEW_REFERENCE']);

        $this->assertSame('from_env', $_SERVER['CODECEPT_DOTENV_EXISTING']);
        $this->assertSame('from_env', $_ENV['CODECEPT_DOTENV_EXISTING']);
        $this->assertSame('from_env', getenv('CODECEPT_DOTENV_EXISTING'));
        $this->assertSame('from_env', ParamsLoader::load('env')['CODECEPT_DOTENV_EXISTING']);

        $this->assertSame('from_file', $_SERVER['CODECEPT_DOTENV_NEW']);
        $this->assertSame('from_file', $_ENV['CODECEPT_DOTENV_NEW']);
        $this->assertSame('from_env', $_SERVER['CODECEPT_DOTENV_REFERENCE']);
        $this->assertSame('from_file', $_SERVER['CODECEPT_DOTENV_NEW_REFERENCE']);
    }

    #[Group('core')]
    public function testLaterDotEnvFileOverridesVariablesOfEarlierOne()
    {
        $this->setEnvironmentVariable('CODECEPT_DOTENV_EXISTING', 'from_env');

        ParamsLoader::load(codecept_data_dir('params/existing_vars.env'));
        $params = ParamsLoader::load(codecept_data_dir('params/existing_vars_override.env'));

        $this->assertSame('from_second_file', $params['CODECEPT_DOTENV_NEW']);
        $this->assertSame('from_second_file', $_SERVER['CODECEPT_DOTENV_NEW']);
        $this->assertSame('from_second_file', $_ENV['CODECEPT_DOTENV_NEW']);

        $this->assertSame('from_second_file', $params['CODECEPT_DOTENV_EXISTING']);
        $this->assertSame('from_env', $_SERVER['CODECEPT_DOTENV_EXISTING']);
    }

    protected function _after(): void
    {
        $names = [
            'CODECEPT_DOTENV_EXISTING',
            'CODECEPT_DOTENV_NEW',
            'CODECEPT_DOTENV_REFERENCE',
            'CODECEPT_DOTENV_NEW_REFERENCE',
        ];
        foreach ($names as $name) {
            unset($_SERVER[$name], $_ENV[$name]);
            putenv($name);
        }
        (new ReflectionProperty(ParamsLoader::class, 'dotEnvRepository'))->setValue(null, null);
    }

    private function setEnvironmentVariable(string $name, string $value): void
    {
        $_SERVER[$name] = $value;
        $_ENV[$name] = $value;
        putenv("{$name}={$value}");
    }
}
