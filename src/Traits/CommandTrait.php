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

namespace Whoa\Commands\Traits;

use Composer\Composer;
use Whoa\Commands\CommandConstants;
use Whoa\Commands\Exceptions\ConfigurationException;
use Whoa\Commands\Wrappers\ConsoleIoWrapper;
use Whoa\Contracts\Commands\IoInterface;
use Whoa\Contracts\Core\ApplicationInterface;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function file_exists;

/**
 * @package Whoa\Commands
 */
trait CommandTrait
{
    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return IoInterface
     */
    protected function wrapIo(InputInterface $input, OutputInterface $output): IoInterface
    {
        return new ConsoleIoWrapper($input, $output);
    }

    /**
     * @param Composer $composer
     * @return ContainerInterface
     * @throws ReflectionException
     */
    protected function createContainer(Composer $composer): ContainerInterface
    {
        // use application autoloader otherwise no app classes will be visible for us
        $autoLoaderPath = $this->getAutoloadPath($composer);
        if (file_exists($autoLoaderPath) === true) {
            require_once $autoLoaderPath;
        }

        $extra = $this->getExtra($composer);
        $appKey = CommandConstants::COMPOSER_JSON__EXTRA__APPLICATION;
        $classKey = CommandConstants::COMPOSER_JSON__EXTRA__APPLICATION__CLASS;
        $appClass = $extra[$appKey][$classKey] ?? CommandConstants::DEFAULT_APPLICATION_CLASS_NAME;
        if ($this->isValidApplicationClass($appClass) === false) {
            $settingsPath = "extra->$appKey->$classKey";
            throw new ConfigurationException(
                "Invalid application class specified '$appClass'. Check your settings at composer.json $settingsPath."
            );
        }

        return $this->createApplication($appClass)->createContainer();
    }

    /**
     * @param Composer $composer
     * @return string
     */
    protected function getAutoloadPath(Composer $composer): string
    {
        return $composer->getConfig()->get('vendor-dir') . DIRECTORY_SEPARATOR . 'autoload.php';
    }

    /**
     * @param Composer $composer
     * @return array
     */
    protected function getExtra(Composer $composer): array
    {
        return $composer->getPackage()->getExtra();
    }

    /**
     * @param string $className
     * @return bool
     * @throws ReflectionException
     */
    protected function isValidApplicationClass(string $className): bool
    {
        $reflectionClass = new ReflectionClass($className);

        return
            $reflectionClass->isInstantiable() === true &&
            $reflectionClass->implementsInterface(ApplicationInterface::class) === true;
    }

    /**
     * @param string $className
     * @return ApplicationInterface
     */
    protected function createApplication(string $className): ApplicationInterface
    {
        return new $className();
    }
}
