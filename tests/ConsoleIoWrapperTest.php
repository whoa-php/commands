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

use Mockery\MockInterface;
use Whoa\Commands\Wrappers\ConsoleIoWrapper;
use Mockery;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @package Whoa\Tests\Commands
 */
class ConsoleIoWrapperTest extends TestCase
{
    /**
     * Test plugin.
     */
    public function testActivate(): void
    {
        /** @var MockInterface $input */
        /** @var MockInterface $output */
        $input = Mockery::mock(InputInterface::class);
        $output = Mockery::mock(OutputInterface::class);

        $input->shouldReceive('getArguments')->zeroOrMoreTimes()->withNoArgs()->andReturn([]);
        $input->shouldReceive('getOptions')->zeroOrMoreTimes()->withNoArgs()->andReturn([]);
        $input->shouldReceive('hasOption')->zeroOrMoreTimes()->withAnyArgs()->andReturn(true);
        $input->shouldReceive('getOption')->zeroOrMoreTimes()->withAnyArgs()->andReturn('whatever');

        $output->shouldReceive('write')->zeroOrMoreTimes()->withAnyArgs()->andReturnUndefined();

        /** @var InputInterface $input */
        /** @var OutputInterface $output */

        $wrapper = new ConsoleIoWrapper($input, $output);

        $this->assertNotNull($wrapper->getArguments());
        $this->assertNotNull($wrapper->getOptions());
        $this->assertTrue($wrapper->hasOption('some_opt_name'));
        $this->assertNotEmpty($wrapper->getOption('some_opt_name'));

        $wrapper->writeInfo('some warning');
    }

    /**
     * Test verbosity convert.
     * @return void
     */
    public function testVerbosityConvert(): void
    {
        $testWrapper = new class extends ConsoleIoWrapper {
            /**
             * Constructor.
             */
            public function __construct()
            {
                $input = Mockery::mock(InputInterface::class);
                $output = Mockery::mock(OutputInterface::class);

                /** @var InputInterface $input */
                /** @var OutputInterface $output */

                parent::__construct($input, $output);
            }

            /**
             * @param int $verbosity
             *
             * @return int
             */
            public function getConvertedVerbosity(int $verbosity): int
            {
                return $this->convertVerbosityLevel($verbosity);
            }
        };

        $this->assertEquals(
            OutputInterface::VERBOSITY_QUIET,
            $testWrapper->getConvertedVerbosity(ConsoleIoWrapper::VERBOSITY_QUIET)
        );
        $this->assertEquals(
            OutputInterface::VERBOSITY_NORMAL,
            $testWrapper->getConvertedVerbosity(ConsoleIoWrapper::VERBOSITY_NORMAL)
        );
        $this->assertEquals(
            OutputInterface::VERBOSITY_VERBOSE,
            $testWrapper->getConvertedVerbosity(ConsoleIoWrapper::VERBOSITY_VERBOSE)
        );
        $this->assertEquals(
            OutputInterface::VERBOSITY_VERY_VERBOSE,
            $testWrapper->getConvertedVerbosity(ConsoleIoWrapper::VERBOSITY_VERY_VERBOSE)
        );
        $this->assertEquals(
            OutputInterface::VERBOSITY_NORMAL,
            $testWrapper->getConvertedVerbosity(-1)
        );
    }
}
