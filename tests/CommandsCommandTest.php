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
use Mockery\MockInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Whoa\Commands\CommandsCommand;
use Whoa\Commands\Traits\CacheFilePathTrait;
use Whoa\Commands\Traits\CommandSerializationTrait;
use Whoa\Commands\Traits\CommandTrait;
use Whoa\Contracts\Application\ApplicationConfigurationInterface as S;
use Whoa\Contracts\Application\CacheSettingsProviderInterface;
use Whoa\Contracts\Commands\CommandStorageInterface;
use Whoa\Contracts\FileSystem\FileSystemInterface;
use Whoa\Tests\Commands\Data\TestCommand;
use Mockery;
use Mockery\Mock;
use Psr\Container\ContainerInterface;
use ReflectionException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @package Whoa\Tests\Commands
 */
class CommandsCommandTest extends TestCase
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
    }

    /**
     * Test execution for Connect.
     * @throws ReflectionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testConnect()
    {
        $container = $this->createContainerMock([
            FileSystemInterface::class => ($fileSystem = $this->createFileSystemMock()),
            CommandStorageInterface::class => ($this->createCommandStorageMock()),
        ]);

        /** @var MockInterface $command */
        $command = Mockery::mock(CommandsCommand::class . '[createContainer,getCommandsCacheFilePath]');
        $command->shouldAllowMockingProtectedMethods();
        $command->shouldReceive('createContainer')->once()->withAnyArgs()->andReturn($container);
        $command->shouldReceive('getCommandsCacheFilePath')->once()->withAnyArgs()->andReturn('path_to_cache_file');
        /** @var CommandsCommand $command */
        $command->setComposer($this->createComposerMock());

        /** @var MockInterface $fileSystem */
        $fileSystem->shouldReceive('write')->once()->withAnyArgs()->andReturnUndefined();

        $command->execute(
            $this->createInputMock(CommandsCommand::ACTION_CONNECT),
            $this->createOutputMock(2)
        );

        // actual check would be performed by Mockery on test completion.
        $this->assertTrue(true);
    }

    /**
     * Test execution for Connect.
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public function testConnectWithInvalidCommand()
    {
        $container = $this->createContainerMock([
            FileSystemInterface::class => ($fileSystem = $this->createFileSystemMock()),
            CommandStorageInterface::class => ($this->createCommandStorageMockWithInvalidCommand()),
        ]);

        /** @var MockInterface $command */
        $command = Mockery::mock(CommandsCommand::class . '[createContainer,getCommandsCacheFilePath]');
        $command->shouldAllowMockingProtectedMethods();
        $command->shouldReceive('createContainer')->once()->withAnyArgs()->andReturn($container);
        /** @var CommandsCommand $command */
        $command->setComposer($this->createComposerMock());

        $command->execute(
            $this->createInputMock(CommandsCommand::ACTION_CONNECT),
            $this->createOutputMock(2)
        );

        // actual check would be performed by Mockery on test completion.
        $this->assertTrue(true);
    }

    /**
     * Test execution for Connect.
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public function testConnectWithEmptyCachePath()
    {
        $container = $this->createContainerMock([
            FileSystemInterface::class => ($fileSystem = $this->createFileSystemMock()),
            CommandStorageInterface::class => ($this->createCommandStorageMock()),
        ]);

        /** @var MockInterface $command */
        $command = Mockery::mock(CommandsCommand::class . '[createContainer,getCommandsCacheFilePath]');
        $command->shouldAllowMockingProtectedMethods();
        $command->shouldReceive('createContainer')->once()->withAnyArgs()->andReturn($container);
        $command->shouldReceive('getCommandsCacheFilePath')->once()->withAnyArgs()->andReturn(''); // <-- empty path
        /** @var CommandsCommand $command */
        $command->setComposer($this->createComposerMock());

        $command->execute(
            $this->createInputMock(CommandsCommand::ACTION_CONNECT),
            $this->createOutputMock(2)
        );

        // actual check would be performed by Mockery on test completion.
        $this->assertTrue(true);
    }

    /**
     * Test execution for Create.
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public function testCreate()
    {
        $folder = 'some_folder';
        $cmdClass = 'NewCommandClass';
        $classPath = $folder . DIRECTORY_SEPARATOR . $cmdClass . '.php';
        $tplPath = realpath(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'src', 'SampleCommand.txt']));
        $tplContent = file_get_contents($tplPath);

        $container = $this->createContainerMock([
            FileSystemInterface::class => ($fileSystem = $this->createFileSystemMock()),
            CacheSettingsProviderInterface::class => $this->createCacheSettingsProviderMock($folder),
        ]);

        /** @var MockInterface $command */
        $command = Mockery::mock(CommandsCommand::class . '[createContainer,getCommandsCacheFilePath]');
        $command->shouldAllowMockingProtectedMethods();
        $command->shouldReceive('createContainer')->once()->withAnyArgs()->andReturn($container);
        /** @var CommandsCommand $command */
        $command->setComposer($this->createComposerMock());

        /** @var MockInterface $fileSystem */
        $fileSystem->shouldReceive('isFolder')->once()->with($folder)->andReturn(true);
        $fileSystem->shouldReceive('exists')->once()->with($classPath)->andReturn(false);
        $fileSystem->shouldReceive('read')->once()->with($tplPath)->andReturn($tplContent);
        $fileSystem->shouldReceive('write')->once()->withAnyArgs()->andReturnUndefined();

        $command->execute(
            $this->createInputMock(CommandsCommand::ACTION_CREATE, $cmdClass),
            $this->createOutputMock()
        );

        // actual check would be performed by Mockery on test completion.
        $this->assertTrue(true);
    }

    /**
     * Test execution for Create.
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public function testCreateNoArgClass()
    {
        $cmdClass = null;

        $container = $this->createContainerMock([]);

        /** @var MockInterface $command */
        $command = Mockery::mock(CommandsCommand::class . '[createContainer,getCommandsCacheFilePath]');
        $command->shouldAllowMockingProtectedMethods();
        $command->shouldReceive('createContainer')->once()->withAnyArgs()->andReturn($container);
        /** @var CommandsCommand $command */
        $command->setComposer($this->createComposerMock());

        $command->execute(
            $this->createInputMock(CommandsCommand::ACTION_CREATE, $cmdClass),
            $this->createOutputMock(1)
        );

        // actual check would be performed by Mockery on test completion.
        $this->assertTrue(true);
    }

    /**
     * Test execution for Create.
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public function testCreateInvalidCommandsFolder()
    {
        $folder = 'some_folder';
        $cmdClass = 'NewCommandClass';

        $container = $this->createContainerMock([
            FileSystemInterface::class => ($fileSystem = $this->createFileSystemMock()),
            CacheSettingsProviderInterface::class => $this->createCacheSettingsProviderMock($folder),
        ]);

        /** @var MockInterface $command */
        $command = Mockery::mock(CommandsCommand::class . '[createContainer,getCommandsCacheFilePath]');
        $command->shouldAllowMockingProtectedMethods();
        $command->shouldReceive('createContainer')->once()->withAnyArgs()->andReturn($container);
        /** @var CommandsCommand $command */
        $command->setComposer($this->createComposerMock());

        /** @var MockInterface $fileSystem */
        $fileSystem->shouldReceive('isFolder')->once()->with($folder)->andReturn(false); // <-- invalid folder

        $command->execute(
            $this->createInputMock(CommandsCommand::ACTION_CREATE, $cmdClass),
            $this->createOutputMock(1)
        );

        // actual check would be performed by Mockery on test completion.
        $this->assertTrue(true);
    }

    /**
     * Test execution for Create.
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public function testCreateCommandAlreadyExists()
    {
        $folder = 'some_folder';
        $cmdClass = 'NewCommandClass';
        $classPath = $folder . DIRECTORY_SEPARATOR . $cmdClass . '.php';

        $container = $this->createContainerMock([
            FileSystemInterface::class => ($fileSystem = $this->createFileSystemMock()),
            CacheSettingsProviderInterface::class => $this->createCacheSettingsProviderMock($folder),
        ]);

        /** @var MockInterface $command */
        $command = Mockery::mock(CommandsCommand::class . '[createContainer,getCommandsCacheFilePath]');
        $command->shouldAllowMockingProtectedMethods();
        $command->shouldReceive('createContainer')->once()->withAnyArgs()->andReturn($container);
        /** @var CommandsCommand $command */
        $command->setComposer($this->createComposerMock());

        /** @var MockInterface $fileSystem */
        $fileSystem->shouldReceive('isFolder')->once()->with($folder)->andReturn(true);
        $fileSystem->shouldReceive('exists')->once()->with($classPath)->andReturn(true); // <-- already exists

        $command->execute(
            $this->createInputMock(CommandsCommand::ACTION_CREATE, $cmdClass),
            $this->createOutputMock(1)
        );

        // actual check would be performed by Mockery on test completion.
        $this->assertTrue(true);
    }

    /**
     * Test execution for Connect.
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public function testInvalidAction()
    {
        $cmdClass = null;

        $container = $this->createContainerMock([]);

        /** @var MockInterface $command */
        $command = Mockery::mock(CommandsCommand::class . '[createContainer,getCommandsCacheFilePath]');
        $command->shouldAllowMockingProtectedMethods();
        $command->shouldReceive('createContainer')->once()->withAnyArgs()->andReturn($container);
        /** @var CommandsCommand $command */
        $command->setComposer($this->createComposerMock());

        $command->execute(
            $this->createInputMock('XXX', $cmdClass), // <-- Invalid action
            $this->createOutputMock(1)
        );

        // actual check would be performed by Mockery on test completion.
        $this->assertTrue(true);
    }

    /**
     * @return Composer
     */
    private function createComposerMock(): Composer
    {
        /** @var CommandsCommand $command */

        /** @var MockInterface $composer */
        /** @var Composer $composer */

        return Mockery::mock(Composer::class);
    }

    /**
     * @param string $argAction
     * @param string|null $argClass
     * @return InputInterface
     */
    private function createInputMock(string $argAction, string $argClass = null): InputInterface
    {
        /** @var MockInterface $input */
        $input = Mockery::mock(InputInterface::class);

        $input->shouldReceive('getArgument')->once()->withAnyArgs()->andReturn($argAction);

        if ($argClass !== null) {
            $input->shouldReceive('hasArgument')->once()->with(CommandsCommand::ARG_CLASS)->andReturn(true);
            $input->shouldReceive('getArgument')->once()->with(CommandsCommand::ARG_CLASS)->andReturn($argClass);
        } else {
            $input->shouldReceive('hasArgument')->zeroOrMoreTimes()->with(CommandsCommand::ARG_CLASS)->andReturn(false);
        }

        /** @var InputInterface $input */

        return $input;
    }

    /**
     * @param int $writeTimes
     * @return OutputInterface
     */
    private function createOutputMock(int $writeTimes = 0): OutputInterface
    {
        /** @var MockInterface $output */
        $output = Mockery::mock(OutputInterface::class);

        if ($writeTimes > 0) {
            $output->shouldReceive('write')->times($writeTimes)->withAnyArgs()->andReturnUndefined();
        }

        /** @var OutputInterface $output */

        return $output;
    }

    /**
     * @return FileSystemInterface
     */
    private function createFileSystemMock(): FileSystemInterface
    {
        /** @var FileSystemInterface $fileSystem */
        return Mockery::mock(FileSystemInterface::class);
    }

    /**
     * @return CommandStorageInterface
     */
    private function createCommandStorageMock(): CommandStorageInterface
    {
        /** @var MockInterface $commandStorage */
        $commandStorage = Mockery::mock(CommandStorageInterface::class);

        $commandStorage->shouldReceive('getAll')->once()->withNoArgs()->andReturn([TestCommand::class]);

        /** @var CommandStorageInterface $commandStorage */

        return $commandStorage;
    }

    /**
     * @return CommandStorageInterface
     */
    private function createCommandStorageMockWithInvalidCommand(): CommandStorageInterface
    {
        /** @var MockInterface $commandStorage */
        $commandStorage = Mockery::mock(CommandStorageInterface::class);

        $commandStorage->shouldReceive('getAll')->once()->withNoArgs()->andReturn([self::class]); // <-- invalid class

        /** @var CommandStorageInterface $commandStorage */

        return $commandStorage;
    }

    /**
     * @param array $items
     * @return ContainerInterface
     */
    private function createContainerMock(array $items): ContainerInterface
    {
        /** @var MockInterface|ContainerInterface $container */
        $container = Mockery::mock(ContainerInterface::class);

        foreach ($items as $key => $item) {
            $container->shouldReceive('has')->zeroOrMoreTimes()->with($key)->andReturn(true);
            $container->shouldReceive('get')->zeroOrMoreTimes()->with($key)->andReturn($item);
        }

        /** @var ContainerInterface $container */

        return $container;
    }

    /**
     * @param string $commandFolder
     * @return CacheSettingsProviderInterface
     */
    private function createCacheSettingsProviderMock(string $commandFolder): CacheSettingsProviderInterface
    {
        /** @var MockInterface $provider */
        $provider = Mockery::mock(CacheSettingsProviderInterface::class);

        $provider->shouldReceive('getApplicationConfiguration')
            ->once()->withNoArgs()->andReturn([S::KEY_COMMANDS_FOLDER => $commandFolder]);

        /** @var CacheSettingsProviderInterface $provider */

        return $provider;
    }
}
