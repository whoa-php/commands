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

namespace Whoa\Commands\Wrappers;

use Whoa\Contracts\Commands\CommandInterface;
use Symfony\Component\Console\Input\InputArgument;

use function assert;
use function is_string;

/**
 * @package Whoa\Commands
 */
class DataArgumentWrapper
{
    /**
     * @var array
     */
    private array $data;

    /**
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;

        assert(!($this->isRequired() && $this->isOptional()));
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->getData()[CommandInterface::ARGUMENT_NAME];
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->getData()[CommandInterface::ARGUMENT_DESCRIPTION] ?? '';
    }

    /**
     * @return string|null
     */
    public function getDefault(): ?string
    {
        $value = $this->getData()[CommandInterface::ARGUMENT_DEFAULT] ?? null;

        assert(is_string($value) === true || $value === null);

        return $value;
    }

    /**
     * @return bool
     */
    public function isRequired(): bool
    {
        return ($this->getModeValue() & CommandInterface::ARGUMENT_MODE__REQUIRED) > 0;
    }

    /**
     * @return bool
     */
    public function isOptional(): bool
    {
        return ($this->getModeValue() & CommandInterface::ARGUMENT_MODE__OPTIONAL) > 0;
    }

    /**
     * @return bool
     */
    public function isArray(): bool
    {
        return ($this->getModeValue() & CommandInterface::ARGUMENT_MODE__IS_ARRAY) > 0;
    }

    /**
     * @return int|null
     */
    public function getMode(): ?int
    {
        $mode = null;

        $this->isRequired() === false ?: $mode = (int)$mode | InputArgument::REQUIRED;
        $this->isOptional() === false ?: $mode = (int)$mode | InputArgument::OPTIONAL;
        $this->isArray() === false ?: $mode = (int)$mode | InputArgument::IS_ARRAY;

        return $mode;
    }

    /**
     * @return int
     */
    protected function getModeValue(): int
    {
        return $this->getData()[CommandInterface::ARGUMENT_MODE] ?? 0;
    }

    /**
     * @return array
     */
    protected function getData(): array
    {
        return $this->data;
    }
}
