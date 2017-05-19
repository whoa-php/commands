<?php namespace Limoncello\Application\Packages\Application;

/**
 * Copyright 2015-2017 info@neomerx.com
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

use Limoncello\Application\Commands\CommandStorage;
use Limoncello\Application\Traits\SelectClassesTrait;
use Limoncello\Application\Traits\SelectClassImplementsTrait;
use Limoncello\Contracts\Application\ContainerConfiguratorInterface;
use Limoncello\Contracts\Commands\CommandInterface;
use Limoncello\Contracts\Commands\CommandStorageInterface;
use Limoncello\Contracts\Container\ContainerInterface as LimoncelloContainerInterface;
use Limoncello\Contracts\Provider\ProvidesCommandsInterface;
use Limoncello\Contracts\Settings\SettingsProviderInterface;
use Psr\Container\ContainerInterface as PsrContainerInterface;

/**
 * @package Limoncello\Application
 */
class ApplicationContainerConfigurator implements ContainerConfiguratorInterface
{
    /** @var callable */
    const HANDLER = [self::class, self::METHOD_NAME];

    /**
     * @inheritdoc
     */
    public static function configure(LimoncelloContainerInterface $container)
    {
        $container[CommandStorageInterface::class] = function (PsrContainerInterface $container) {
            $creator = new class
            {
                use SelectClassesTrait, SelectClassImplementsTrait;

                /**
                 * @param string $commandsPath
                 * @param array  $providerClasses
                 *
                 * @return CommandStorageInterface
                 */
                public function createCommandStorage(
                    string $commandsPath,
                    array $providerClasses
                ): CommandStorageInterface {
                    $storage = new CommandStorage();

                    $interfaceName = CommandInterface::class;
                    foreach ($this->selectClasses($commandsPath, $interfaceName) as $commandClass) {
                        $storage->add($commandClass);
                    }

                    $interfaceName = ProvidesCommandsInterface::class;
                    foreach ($this->selectClassImplements($providerClasses, $interfaceName) as $providerClass) {
                        /** @var ProvidesCommandsInterface $providerClass */
                        foreach ($providerClass::getCommands() as $commandClass) {
                            $storage->add($commandClass);
                        }
                    }

                    return $storage;
                }
            };

            /** @var SettingsProviderInterface $provider */
            $provider = $container->get(SettingsProviderInterface::class);
            $settings = $provider->get(ApplicationSettings::class);

            $providerClasses  = $settings[ApplicationSettings::KEY_PROVIDER_CLASSES];
            $commandsFileMask = '*.php';
            $commandsFolder   = $settings[ApplicationSettings::KEY_COMMANDS_FOLDER];
            $commandsPath     = $commandsFolder . DIRECTORY_SEPARATOR . $commandsFileMask;

            $storage = $creator->createCommandStorage($commandsPath, $providerClasses);

            return $storage;
        };
    }
}