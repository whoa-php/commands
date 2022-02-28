<?php

/**
 * Copyright 2015-2020 info@neomerx.com
 * Modification Copyright 2021-2022 info@whoaphp.com
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

declare(strict_types=1);

namespace Whoa\Tests\Commands;

use Composer\Composer;
use Composer\Config as ComposerConfig;
use Composer\Package\RootPackageInterface;
use Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Whoa\Commands\CommandConstants;
use Whoa\Commands\Exceptions\ConfigurationException;
use Whoa\Commands\WhoaCommand;
use Whoa\Commands\Traits\CacheFilePathTrait;
use Whoa\Commands\Traits\CommandSerializationTrait;
use Whoa\Commands\Traits\CommandTrait;
use Whoa\Contracts\Application\ApplicationConfigurationInterface;
use Whoa\Contracts\Application\CacheSettingsProviderInterface;
use Whoa\Contracts\Commands\CommandInterface;
use Whoa\Contracts\Container\ContainerInterface as WhoaContainerInterface;
use Whoa\Contracts\Exceptions\ThrowableHandlerInterface;
use Whoa\Contracts\FileSystem\FileSystemInterface;
use Whoa\Contracts\Http\ThrowableResponseInterface;
use Whoa\Tests\Commands\Data\TestApplication;
use Whoa\Tests\Commands\Data\TestCliRoutesConfigurator;
use Whoa\Tests\Commands\Data\TestCommand;
use Mockery;
use Mockery\MockInterface;
use ReflectionException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @package Whoa\Tests\Commands
 */
class WhoaCommandTest extends TestCase
{
    use CacheFilePathTrait;
    use CommandSerializationTrait;
    use CommandTrait;

    /** @var bool */
    private static bool $executedFlag = false;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        static::$executedFlag = false;
        TestCliRoutesConfigurator::clearTestFlags();
    }

    /**
     * Test basic command behaviour.
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testCommand(): void
    {
        $name = TestCliRoutesConfigurator::COMMAND_NAME_1;
        $command = $this->createCommandMock($name);

        $command->shouldReceive('createContainer')->once()->withAnyArgs()->andReturn($this->createContainerMock());

        /** @var WhoaCommand $command */

        $this->assertEquals($name, $command->getName());

        $input = Mockery::mock(InputInterface::class);
        $output = Mockery::mock(OutputInterface::class);
        $composer = Mockery::mock(Composer::class);

        /** @var InputInterface $input */
        /** @var OutputInterface $output */
        /** @var Composer $composer */

        $command->setComposer($composer);

        $this->assertFalse(TestCliRoutesConfigurator::areHandlersExecuted1());
        $this->assertFalse(TestCliRoutesConfigurator::areHandlersExecuted2());

        $command->execute($input, $output);

        $this->assertTrue(static::$executedFlag);
        $this->assertTrue(TestCliRoutesConfigurator::areHandlersExecuted1());
        $this->assertFalse(TestCliRoutesConfigurator::areHandlersExecuted2());
    }

    /**
     * Test if container creation fails.
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testContainerCreationFails(): void
    {
        $command = $this->createCommandMock();

        $command->shouldReceive('createContainer')
            ->once()->withAnyArgs()->andThrow(new Exception('Oops, container failed'));

        /** @var WhoaCommand $command */

        $input = Mockery::mock(InputInterface::class);
        $output = Mockery::mock(OutputInterface::class);
        $composer = Mockery::mock(Composer::class);

        $output->shouldReceive('writeln')->once()->withAnyArgs()->andReturnUndefined();

        /** @var InputInterface $input */
        /** @var OutputInterface $output */
        /** @var Composer $composer */

        $command->setComposer($composer);

        $exception = null;
        try {
            $command->execute($input, $output);
        } catch (Exception $exception) {
        }
        $this->assertNotNull($exception);
        $this->assertStringStartsWith('Oops, container failed', $exception->getMessage());

        $this->assertFalse(static::$executedFlag);
    }

    /**
     * Test custom error handler.
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testCustomErrorHandler(): void
    {
        $command = $this->createCommandMock('name', [self::class, 'callbackWithThrow']);

        $handler = Mockery::mock(ThrowableHandlerInterface::class);
        $response = Mockery::mock(ThrowableResponseInterface::class);
        $container = $this->createContainerMock();
        $container->shouldReceive('has')->withArgs([ThrowableHandlerInterface::class])->andReturn(true);
        $container->shouldReceive('get')->withArgs([ThrowableHandlerInterface::class])->andReturn($handler);
        $handler->shouldReceive('createResponse')->once()->withAnyArgs()->andReturn($response);
        $response->shouldReceive('getBody')->once()->withAnyArgs()->andReturn('does not matter');


        $command->shouldReceive('createContainer')->once()->withAnyArgs()->andReturn($container);

        /** @var WhoaCommand $command */

        $input = Mockery::mock(InputInterface::class);
        $output = Mockery::mock(OutputInterface::class);
        $composer = Mockery::mock(Composer::class);

        $output->shouldReceive('writeln')->once()->withAnyArgs()->andReturnUndefined();

        /** @var InputInterface $input */
        /** @var OutputInterface $output */
        /** @var Composer $composer */

        $command->setComposer($composer);

        $exception = null;
        try {
            $command->execute($input, $output);
        } catch (Exception $exception) {
        }
        $this->assertNotNull($exception);
        $this->assertStringStartsWith('Oops, command thrown and exception', $exception->getMessage());

        $this->assertTrue(static::$executedFlag);
    }

    /**
     * Test trait method.
     * @throws Exception
     */
    public function testGetCommandsCacheFilePath(): void
    {
        /** @var MockInterface $composer */
        $composer = Mockery::mock(Composer::class);

        $fileName = 'composer.json';
        /** @var RootPackageInterface $rootPackage */
        $rootPackage = Mockery::mock(RootPackageInterface::class);
        $rootPackage->shouldReceive('getExtra')->once()->withNoArgs()->andReturn([
            CommandConstants::COMPOSER_JSON__EXTRA__APPLICATION => [
                CommandConstants::COMPOSER_JSON__EXTRA__APPLICATION__COMMANDS_CACHE => $fileName,
            ],
        ]);
        $composer->shouldReceive('getPackage')->once()->withNoArgs()->andReturn($rootPackage);

        $vendorDir = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'vendor';
        /** @var MockInterface $composerConfig */
        $composerConfig = Mockery::mock(ComposerConfig::class);
        $composerConfig->shouldReceive('get')->once()->with('vendor-dir')->andReturn($vendorDir);
        $composer->shouldReceive('getConfig')->once()->withNoArgs()->andReturn($composerConfig);

        /** @var Composer $composer */
        $path = $this->getCommandsCacheFilePath($composer);
        $expected = realpath($vendorDir . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . $fileName);
        $this->assertEquals($expected, $path);
    }

    /**
     * Test trait method.
     * @throws Exception
     */
    public function testCommandSerialization(): void
    {
        $this->assertNotEmpty($this->commandClassToArray(TestCommand::class));
    }

    /**
     * Test trait method.
     * @throws Exception
     */
    public function testCreateContainer(): void
    {
        /** @var MockInterface $composer */
        $composer = Mockery::mock(Composer::class);

        $vendorDir = __DIR__ . DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR . 'vendor';
        /** @var MockInterface $composerConfig */
        $composerConfig = Mockery::mock(ComposerConfig::class);
        $composerConfig->shouldReceive('get')->with('vendor-dir')->andReturn($vendorDir);

        $composer->shouldReceive('getConfig')->once()->withNoArgs()->andReturn($composerConfig);

        $extra = [
            CommandConstants::COMPOSER_JSON__EXTRA__APPLICATION => [
                CommandConstants::COMPOSER_JSON__EXTRA__APPLICATION__CLASS => TestApplication::class,
            ],
        ];
        /** @var RootPackageInterface $rootPackage */
        $rootPackage = Mockery::mock(RootPackageInterface::class);
        $rootPackage->shouldReceive('getExtra')->withNoArgs()->andReturn($extra);
        $composer->shouldReceive('getPackage')->once()->withNoArgs()->andReturn($rootPackage);

        /** @var Composer $composer */
        $this->assertNotNull($this->createContainer($composer));
    }

    /**
     * Test trait method.
     * @throws ReflectionException
     */
    public function testCreateContainerForInvalidAppClass(): void
    {
        $this->expectException(ConfigurationException::class);

        /** @var MockInterface $composer */
        $composer = Mockery::mock(Composer::class);

        $vendorDir = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'vendor';
        /** @var MockInterface $composerConfig */
        $composerConfig = Mockery::mock(ComposerConfig::class);
        $composerConfig->shouldReceive('get')->once()->with('vendor-dir')->andReturn($vendorDir);

        $composer->shouldReceive('getConfig')->once()->withNoArgs()->andReturn($composerConfig);

        $extra = [
            CommandConstants::COMPOSER_JSON__EXTRA__APPLICATION => [
                CommandConstants::COMPOSER_JSON__EXTRA__APPLICATION__CLASS => self::class, // <-- invalid App class
            ],
        ];
        /** @var MockInterface $rootPackage */
        $rootPackage = Mockery::mock(RootPackageInterface::class);
        $rootPackage->shouldReceive('getExtra')->once()->withNoArgs()->andReturn($extra);

        $composer->shouldReceive('getPackage')->once()->withNoArgs()->andReturn($rootPackage);

        /** @var Composer $composer */

        $this->createContainer($composer);
    }

    /**
     * @return void
     */
    public static function callback1(): void
    {
        static::$executedFlag = true;
    }

    /**
     * @return void
     *
     * @throws Exception
     */
    public static function callbackWithThrow(): void
    {
        static::$executedFlag = true;

        throw new Exception('Oops, command thrown and exception');
    }

    /**
     * @param string $name
     * @param array $callable
     * @return MockInterface
     */
    private function createCommandMock(
        string $name = 'name',
        array $callable = [self::class, 'callback1']
    ): MockInterface {
        $description = 'description';
        $help = 'help';
        $argName1 = 'arg1';
        $arguments = [
            [
                CommandInterface::ARGUMENT_NAME => $argName1,
            ],
        ];
        $optName1 = 'opt1';
        $options = [
            [
                CommandInterface::OPTION_NAME => $optName1,
            ],
        ];

        /** @var MockInterface $command */
        $command = Mockery::mock(
            WhoaCommand::class . '[createContainer]',
            [$name, $description, $help, $arguments, $options, $callable]
        );
        $command->shouldAllowMockingProtectedMethods();

        return $command;
    }

    /**
     * @return MockInterface
     */
    private function createContainerMock(): MockInterface
    {
        $container = Mockery::mock(WhoaContainerInterface::class);

        // add some app settings to container
        $routesFolder = implode(DIRECTORY_SEPARATOR, [__DIR__, 'Data',]);
        $container
            ->shouldReceive('get')->once()
            ->with(CacheSettingsProviderInterface::class)
            ->andReturnSelf();
        $container
            ->shouldReceive('getApplicationConfiguration')->once()
            ->withNoArgs()
            ->andReturn([
                ApplicationConfigurationInterface::KEY_ROUTES_FOLDER => $routesFolder,
                ApplicationConfigurationInterface::KEY_ROUTES_FILE_MASK => '*.php',
            ]);
        // add FileSystem to container
        $container
            ->shouldReceive('get')->once()
            ->with(FileSystemInterface::class)
            ->andReturnSelf();
        $container
            ->shouldReceive('exists')->once()
            ->with($routesFolder)
            ->andReturn(true);

        return $container;
    }
}
