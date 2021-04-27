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

namespace Whoa\Tests\Commands\Data;

use Whoa\Contracts\Commands\RoutesConfiguratorInterface;
use Whoa\Contracts\Commands\RoutesInterface;

/**
 * @package Whoa\Tests\Commands
 */
class TestCliRoutesConfigurator implements RoutesConfiguratorInterface
{
    /** @var string Command name for test. */
    const COMMAND_NAME_1 = 'name-1';

    /** @var string Command name for test. */
    const COMMAND_NAME_2 = 'name-2';

    /**
     * @inheritdoc
     */
    public static function configureRoutes(RoutesInterface $routes): void
    {
        $routes->addGlobalContainerConfigurators([
            TestCliContainerConfiguratorGlobal::CALLABLE_METHOD,
        ]);
        $routes->addCommandContainerConfigurators(static::COMMAND_NAME_1, [
            TestCliContainerConfiguratorCommand1::CALLABLE_METHOD,
        ]);
        $routes->addCommandContainerConfigurators(static::COMMAND_NAME_2, [
            TestCliContainerConfiguratorCommand2::CALLABLE_METHOD,
        ]);

        $routes->addGlobalMiddleware([
            TestCliMiddlewareGlobal::CALLABLE_METHOD,
        ]);
        $routes->addCommandMiddleware(static::COMMAND_NAME_1, [
            TestCliMiddlewareCommand1::CALLABLE_METHOD,
        ]);
        $routes->addCommandMiddleware(static::COMMAND_NAME_2, [
            TestCliMiddlewareCommand2::CALLABLE_METHOD,
        ]);
    }

    /**
     * Clear test flags.
     */
    public static function clearTestFlags(): void
    {
        TestCliContainerConfiguratorGlobal::clear();
        TestCliContainerConfiguratorCommand1::clear();
        TestCliContainerConfiguratorCommand2::clear();
        TestCliMiddlewareGlobal::clear();
        TestCliMiddlewareCommand1::clear();
        TestCliMiddlewareCommand2::clear();
    }

    /**
     * @return bool
     */
    public static function areHandlersExecuted1(): bool
    {
        return TestCliContainerConfiguratorGlobal::isExecuted() && TestCliContainerConfiguratorCommand1::isExecuted() &&
            TestCliMiddlewareGlobal::isExecuted() && TestCliMiddlewareCommand1::isExecuted();
    }

    /**
     * @return bool
     */
    public static function areHandlersExecuted2(): bool
    {
        return TestCliContainerConfiguratorGlobal::isExecuted() && TestCliContainerConfiguratorCommand2::isExecuted() &&
            TestCliMiddlewareGlobal::isExecuted() && TestCliMiddlewareCommand2::isExecuted();
    }
}
