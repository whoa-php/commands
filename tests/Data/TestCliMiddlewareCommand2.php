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

use Closure;
use Whoa\Contracts\Commands\IoInterface;
use Whoa\Contracts\Commands\MiddlewareInterface;
use Psr\Container\ContainerInterface;

/**
 * @package Whoa\Tests\Commands
 */
class TestCliMiddlewareCommand2 implements MiddlewareInterface
{
    /** @var bool Flag if middleware was called */
    private static bool $isExecuted = false;

    /** @var array Middleware callable */
    public const CALLABLE_METHOD = [self::class, self::MIDDLEWARE_METHOD_NAME];

    /**
     * @inheritdoc
     */
    public static function handle(IoInterface $inOut, Closure $next, ContainerInterface $container): void
    {
        static::$isExecuted = true;

        $next($inOut);
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
