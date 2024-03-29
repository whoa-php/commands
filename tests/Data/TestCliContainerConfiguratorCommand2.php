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

use Whoa\Contracts\Application\ContainerConfiguratorInterface;
use Whoa\Contracts\Container\ContainerInterface;

/**
 * @package Whoa\Tests\Commands
 */
class TestCliContainerConfiguratorCommand2 implements ContainerConfiguratorInterface
{
    /** @var bool Flag if container was called */
    private static bool $isExecuted = false;

    /** @var array Container callable */
    public const CALLABLE_METHOD = [self::class, self::CONTAINER_METHOD_NAME];

    /**
     * @inheritdoc
     */
    public static function configureContainer(ContainerInterface $container): void
    {
        static::$isExecuted = true;
    }

    /**
     * @return bool
     */
    public static function isExecuted(): bool
    {
        return static::$isExecuted;
    }

    /**
     * Clear `is executed` flag.
     */
    public static function clear(): void
    {
        static::$isExecuted = false;
    }
}
