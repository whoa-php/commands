<?php namespace Limoncello\Flute\Contracts\Api;

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

use Closure;
use Doctrine\DBAL\Query\QueryBuilder;
use Limoncello\Flute\Contracts\Models\PaginatedDataInterface;

/**
 * @package Limoncello\Flute
 */
interface CrudInterface
{
    /**
     * @return self
     */
    public function combineWithAnd(): self;

    /**
     * @return self
     */
    public function combineWithOr(): self;

    /**
     * @param iterable $filterParameters
     *
     * @return self
     */
    public function withFilters(iterable $filterParameters): self;

    /**
     * @param string|int $index
     *
     * @return self
     */
    public function withIndexFilter($index): self;

    /**
     * @param iterable $sortingParameters
     *
     * @return self
     */
    public function withSorts(iterable $sortingParameters): self;

    /**
     * @param iterable $includePaths
     *
     * @return self
     */
    public function withIncludes(iterable $includePaths): self;

    /**
     * @param int $offset
     * @param int $limit
     *
     * @return self
     */
    public function withPaging(int $offset, int $limit): self;

    /**
     * @param string   $name
     * @param iterable $filters
     *
     * @return CrudInterface
     */
    public function withRelationshipFilters(string $name, iterable $filters): self;

    /**
     * @param string   $name
     * @param iterable $sorts
     *
     * @return CrudInterface
     */
    public function withRelationshipSorts(string $name, iterable $sorts): self;

    /**
     * @param iterable|null $columns
     *
     * @return QueryBuilder
     */
    public function createIndexBuilder(iterable $columns = null): QueryBuilder;

    /**
     * @return QueryBuilder
     */
    public function createDeleteBuilder(): QueryBuilder;

    /**
     * @param QueryBuilder $builder
     * @param string       $modelClass
     *
     * @return PaginatedDataInterface
     */
    public function fetchResources(QueryBuilder $builder, string $modelClass): PaginatedDataInterface;

    /**
     * @param QueryBuilder|null $builder
     * @param string|null       $modelClass
     *
     * @return PaginatedDataInterface
     */
    public function fetchResource(QueryBuilder $builder, string $modelClass): PaginatedDataInterface;

    /**
     * @param QueryBuilder $builder
     * @param string       $modelClass
     *
     * @return array|null
     */
    public function fetchRow(QueryBuilder $builder, string $modelClass): ?array;

    /**
     * @param QueryBuilder $builder
     * @param string       $modelClass
     * @param string       $columnName
     *
     * @return iterable
     */
    public function fetchColumn(QueryBuilder $builder, string $modelClass, string $columnName): iterable;

    /**
     * @return PaginatedDataInterface
     */
    public function index(): PaginatedDataInterface;

    /**
     * @return array
     */
    public function indexIdentities(): array;

    /**
     * @param null|string $index
     *
     * @return PaginatedDataInterface
     */
    public function read($index): PaginatedDataInterface;

    /**
     * @return int|null
     */
    public function count(): ?int;

    /**
     * @return int
     */
    public function delete(): int;

    /**
     * @param null|string $index
     *
     * @return bool
     */
    public function remove($index): bool;

    /**
     * @param null|string $index
     * @param iterable    $attributes
     * @param iterable    $toMany
     *
     * @return string
     */
    public function create($index, iterable $attributes, iterable $toMany): string;

    /**
     * @param int|string $index
     * @param iterable   $attributes
     * @param iterable   $toMany
     *
     * @return int
     */
    public function update($index, iterable $attributes, iterable $toMany): int;

    /**
     * @param string        $name
     * @param iterable|null $relationshipFilters
     * @param iterable|null $relationshipSorts
     *
     * @return PaginatedDataInterface
     */
    public function indexRelationship(
        string $name,
        iterable $relationshipFilters = null,
        iterable $relationshipSorts = null
    ): PaginatedDataInterface;

    /**
     * @param string        $name
     * @param iterable|null $relationshipFilters
     * @param iterable|null $relationshipSorts
     *
     * @return array
     */
    public function indexRelationshipIdentities(
        string $name,
        iterable $relationshipFilters = null,
        iterable $relationshipSorts = null
    ): array;

    /**
     * @param int|string    $index
     * @param string        $name
     * @param iterable|null $relationshipFilters
     * @param iterable|null $relationshipSorts
     *
     * @return PaginatedDataInterface
     */
    public function readRelationship(
        $index,
        string $name,
        iterable $relationshipFilters = null,
        iterable $relationshipSorts = null
    ): PaginatedDataInterface;

    /**
     * @param int|string $parentId
     * @param string     $name
     * @param int|string $childId
     *
     * @return bool
     */
    public function hasInRelationship($parentId, string $name, $childId): bool;

    /**
     * @param Closure $closure
     */
    public function inTransaction(Closure $closure): void;
}
