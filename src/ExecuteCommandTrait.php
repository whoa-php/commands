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

namespace Whoa\Commands;

use Closure;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Whoa\Common\Reflection\CheckCallableTrait;
use Whoa\Common\Reflection\ClassIsTrait;
use Whoa\Contracts\Application\ApplicationConfigurationInterface;
use Whoa\Contracts\Application\CacheSettingsProviderInterface;
use Whoa\Contracts\Commands\IoInterface;
use Whoa\Contracts\Commands\RoutesConfiguratorInterface;
use Whoa\Contracts\Commands\RoutesInterface;
use Whoa\Contracts\Container\ContainerInterface as WhoaContainerInterface;
use Whoa\Contracts\FileSystem\FileSystemInterface;
use Psr\Container\ContainerInterface as PsrContainerInterface;
use ReflectionException;

use function assert;
use function array_merge;
use function call_user_func;
use function count;

/**
 * Code for command execution is separated from the main code to get rid of a dependency from Composer.
 * This code could be executed independently in tests without composer dependency.
 * @package Whoa\Commands
 */
trait ExecuteCommandTrait
{
    use ClassIsTrait;

    /**
     * @param string $name
     * @param callable $handler
     * @param IoInterface $inOut
     * @param WhoaContainerInterface $container
     * @return void
     * @throws ReflectionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function executeCommand(
        string $name,
        callable $handler,
        IoInterface $inOut,
        WhoaContainerInterface $container
    ): void {
        // This method does bootstrap for every command (e.g. configure containers)
        // and then calls the actual command handler.

        // At this point we have probably only partly configured container, and we need to read from it
        // CLI route setting in order to fully configure it and then run the command with middleware.
        // However, when we read anything from it, it changes its state, so we are not allowed to add
        // anything to it (technically we can but in some cases it might cause an exception).
        // So, what's the solution? We clone the container, read from the clone everything we need,
        // and then continue with the original unchanged container.
        $routesPath = null;
        if (true) {
            $containerClone = clone $container;

            /** @var CacheSettingsProviderInterface $provider */
            $provider = $container->get(CacheSettingsProviderInterface::class);
            $appConfig = $provider->getApplicationConfiguration();

            $routesFolder = $appConfig[ApplicationConfigurationInterface::KEY_ROUTES_FOLDER] ?? '';
            $routesMask = $appConfig[ApplicationConfigurationInterface::KEY_ROUTES_FILE_MASK] ?? '';

            /** @var FileSystemInterface $files */
            assert(
                ($files = $containerClone->get(FileSystemInterface::class)) !== null &&
                empty($routesFolder) === false && empty($routesMask) === false &&
                $files->exists($routesFolder) === true,
                'Routes folder and mask must be defined in application settings.'
            );

            unset($containerClone);

            $routesPath = $routesFolder . DIRECTORY_SEPARATOR . $routesMask;
        }

        [$configurators, $middleware]
            = $this->readExtraContainerConfiguratorsAndMiddleware($routesPath, $name);

        $this->executeContainerConfigurators($configurators, $container);

        $handler = $this->buildExecutionChain($middleware, $handler, $container);

        // finally, go through all middleware and execute command handler
        // (container has to be the same (do not send as param), but middleware my wrap IO (send as param)).
        call_user_func($handler, $inOut);
    }

    /**
     * @param string $routesFolder
     * @param string $commandName
     * @return array
     * @throws ReflectionException
     */
    private function readExtraContainerConfiguratorsAndMiddleware(string $routesFolder, string $commandName): array
    {
        $routesFilter = new class ($commandName) implements RoutesInterface {
            use CheckCallableTrait;

            /** @var array */
            private array $middleware = [];

            /** @var array */
            private array $configurators = [];

            /** @var string */
            private string $commandName;

            /**
             * @param string $commandName
             */
            public function __construct(string $commandName)
            {
                $this->commandName = $commandName;
            }

            /**
             * @inheritdoc
             */
            public function addGlobalMiddleware(array $middleware): RoutesInterface
            {
                assert($this->checkMiddlewareCallables($middleware) === true);

                $this->middleware = array_merge($this->middleware, $middleware);

                return $this;
            }

            /**
             * @inheritdoc
             */
            public function addGlobalContainerConfigurators(array $configurators): RoutesInterface
            {
                assert($this->checkConfiguratorCallables($configurators) === true);

                $this->configurators = array_merge($this->configurators, $configurators);

                return $this;
            }

            /**
             * @inheritdoc
             */
            public function addCommandMiddleware(string $name, array $middleware): RoutesInterface
            {
                assert($this->checkMiddlewareCallables($middleware) === true);

                if ($this->commandName === $name) {
                    $this->middleware = array_merge($this->middleware, $middleware);
                }

                return $this;
            }

            /**
             * @inheritdoc
             */
            public function addCommandContainerConfigurators(string $name, array $configurators): RoutesInterface
            {
                assert($this->checkConfiguratorCallables($configurators) === true);

                if ($this->commandName === $name) {
                    $this->configurators = array_merge($this->configurators, $configurators);
                }

                return $this;
            }

            /**
             * @return array
             */
            public function getMiddleware(): array
            {
                return $this->middleware;
            }

            /**
             * @return array
             */
            public function getConfigurators(): array
            {
                return $this->configurators;
            }

            /**
             * @param array $mightBeConfigurators
             * @return bool
             */
            private function checkConfiguratorCallables(array $mightBeConfigurators): bool
            {
                $result = true;

                foreach ($mightBeConfigurators as $mightBeCallable) {
                    $result = $result === true &&
                        $this->checkPublicStaticCallable(
                            $mightBeCallable,
                            [WhoaContainerInterface::class],
                            'void'
                        );
                }

                return $result;
            }

            /**
             * @param array $mightBeMiddleware
             * @return bool
             */
            private function checkMiddlewareCallables(array $mightBeMiddleware): bool
            {
                $result = true;

                foreach ($mightBeMiddleware as $mightBeCallable) {
                    $result = $result === true &&
                        $this->checkPublicStaticCallable(
                            $mightBeCallable,
                            [IoInterface::class, Closure::class, PsrContainerInterface::class],
                            'void'
                        );
                }

                return $result;
            }
        };

        foreach (static::selectClasses($routesFolder, RoutesConfiguratorInterface::class) as $class) {
            /** @var RoutesConfiguratorInterface $class */
            $class::configureRoutes($routesFilter);
        }

        return [$routesFilter->getConfigurators(), $routesFilter->getMiddleware()];
    }

    /**
     * @param callable[] $configurators
     * @param WhoaContainerInterface $container
     * @return void
     */
    private function executeContainerConfigurators(array $configurators, WhoaContainerInterface $container): void
    {
        foreach ($configurators as $configurator) {
            call_user_func($configurator, $container);
        }
    }

    /**
     * @param array $middleware
     * @param callable $command
     * @param PsrContainerInterface $container
     * @return Closure
     */
    private function buildExecutionChain(
        array $middleware,
        callable $command,
        PsrContainerInterface $container
    ): Closure {
        $next = function (IoInterface $inOut) use ($command, $container): void {
            call_user_func($command, $container, $inOut);
        };

        for ($index = count($middleware) - 1; $index >= 0; $index--) {
            $currentMiddleware = $middleware[$index];
            $next = function (IoInterface $inOut) use ($currentMiddleware, $next, $container): void {
                call_user_func($currentMiddleware, $inOut, $next, $container);
            };
        }

        return $next;
    }
}
