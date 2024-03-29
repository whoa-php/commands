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

use Composer\Command\BaseCommand;
use Exception;
use PHPUnit\TextUI\ReflectionException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Whoa\Commands\Traits\CommandSerializationTrait;
use Whoa\Commands\Traits\CommandTrait;
use Whoa\Commands\Wrappers\DataArgumentWrapper;
use Whoa\Commands\Wrappers\DataOptionWrapper;
use Whoa\Contracts\Container\ContainerInterface as WhoaContainerInterface;
use Whoa\Contracts\Exceptions\ThrowableHandlerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function assert;

/**
 * @package Whoa\Commands
 */
class WhoaCommand extends BaseCommand
{
    use CommandSerializationTrait;
    use CommandTrait;
    use ExecuteCommandTrait;

    /**
     * @var string
     */
    private string $description;

    /**
     * @var string
     */
    private string $help;

    /**
     * @var array
     */
    private array $arguments;

    /**
     * @var array
     */
    private array $options;

    /**
     * @var callable|array
     */
    private $callable;

    /**
     * @param string $name
     * @param string $description
     * @param string $help
     * @param array $arguments
     * @param array $options
     * @param array $callable
     */
    public function __construct(
        string $name,
        string $description,
        string $help,
        array $arguments,
        array $options,
        array $callable
    ) {
        $this->description = $description;
        $this->help = $help;
        $this->arguments = $arguments;
        $this->options = $options;
        $this->callable = $callable;

        // it is important to call the parent constructor after
        // data init as it calls `configure` method.
        parent::__construct($name);
    }

    /**
     * @inheritdoc
     */
    public function configure()
    {
        parent::configure();

        $this
            ->setDescription($this->description)
            ->setHelp($this->help);

        foreach ($this->arguments as $data) {
            $arg = new DataArgumentWrapper($data);
            $this->addArgument($arg->getName(), $arg->getMode(), $arg->getDescription(), $arg->getDefault());
        }

        foreach ($this->options as $data) {
            $opt = new DataOptionWrapper($data);
            $this->addOption(
                $opt->getName(),
                $opt->getShortcut(),
                $opt->getMode(),
                $opt->getDescription(),
                $opt->getDefault()
            );
        }
    }

    /**
     * @inheritdoc
     * @throws Exception
     * @throws ReflectionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $container = null;
        try {
            // There is a tiny hack here. We need editable container, and we know that at this point
            // container still can be edited, so we cast it to `WhoaContainerInterface`.
            $container = $this->createContainer($this->getComposer());
            assert($container instanceof WhoaContainerInterface);

            $this->executeCommand($this->getName(), $this->callable, $this->wrapIo($input, $output), $container);
        } catch (Exception $exception) {
            if ($container !== null && $container->has(ThrowableHandlerInterface::class) === true) {
                /** @var ThrowableHandlerInterface $handler */
                $handler = $container->get(ThrowableHandlerInterface::class);
                $response = $handler->createResponse($exception, $container);

                $output->writeln((string)$response->getBody());
            } else {
                $message = $exception->getMessage();
                $file = $exception->getFile();
                $line = $exception->getLine();
                $trace = $exception->getTraceAsString();

                $output->writeln("$message at $file#$line" . PHP_EOL . $trace);
            }

            throw $exception;
        }
    }
}
