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
use Composer\IO\NullIO;
use Composer\Package\RootPackageInterface;
use Mockery\MockInterface;
use Whoa\Commands\CommandConstants;
use Whoa\Commands\ComposerCommandProvider;
use Whoa\Commands\ComposerPlugin;
use Mockery;
use Whoa\Contracts\Container\ContainerInterface;

/**
 * @package Whoa\Tests\Commands
 */
class ComposerPluginTest extends TestCase
{

    /**
     * Test plugin.
     */
    public function testActivate()
    {
        /** @var MockInterface $composer */
        $composer = Mockery::mock(Composer::class);

        $fileName = implode(DIRECTORY_SEPARATOR, ['tests', 'Data', 'TestCacheData.php']);
        /** @var MockInterface $rootPackage */
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

        $ioInterface = new NullIO();
        $plugin = new ComposerPlugin();

        $this->assertNotEmpty($plugin->getCapabilities());

        ComposerCommandProvider::setCommands([]);
        $plugin->activate($composer, $ioInterface);
        $this->assertCount(2, (new ComposerCommandProvider())->getCommands());
    }
}
