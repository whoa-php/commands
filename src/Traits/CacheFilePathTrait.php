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

use function realpath;

/**
 * @package Whoa\Commands
 */
trait CacheFilePathTrait
{
    /**
     * @param Composer $composer
     * @return null|string
     */
    protected function getCommandsCacheFilePath(Composer $composer): ?string
    {
        $extra = $composer->getPackage()->getExtra();
        $keyApp = CommandConstants::COMPOSER_JSON__EXTRA__APPLICATION;
        $keyCacheFile = CommandConstants::COMPOSER_JSON__EXTRA__APPLICATION__COMMANDS_CACHE;
        $commandsCacheFile = $extra[$keyApp][$keyCacheFile] ?? null;
        if ($commandsCacheFile !== null) {
            $appRootPath = $composer->getConfig()->get('vendor-dir') . DIRECTORY_SEPARATOR . '..';
            $commandsCacheFile = realpath($appRootPath) . DIRECTORY_SEPARATOR . $commandsCacheFile;
        }

        return $commandsCacheFile;
    }
}
