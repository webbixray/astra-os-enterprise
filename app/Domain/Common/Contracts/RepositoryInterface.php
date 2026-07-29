<?php

declare(strict_types=1);

namespace App\Domain\Common\Contracts;

/**
 * Interface RepositoryInterface
 *
 * Generic repository interface providing standard data access operations
 * for domain entities. Repositories act as in-memory collections of aggregate
 * roots and provide a clean abstraction layer between the domain model and
 * the persistence mechanism.
 *
 * Implementations of this interface should be stateless and focus solely on
 * storage and retrieval of aggregate roots.
 *
 * @template TEntity of object
 * @template TId
 *
 * @package App\Domain\Common\Contracts
 */
interface RepositoryInterface
{
    /**
     * Find an entity by its unique identifier.
     *
     * Returns null when no entity with the given identifier exists.
     *
     * @param TId $id The unique identifier of the entity.
     * @return TEntity|null The found entity, or null if not found.
     */
    public function find($id): ?object;

    /**
     * Retrieve all entities managed by this repository.
     *
     * @return array<int, TEntity> An array of all entities.
     */
    public function findAll(): array;

    /**
     * Persist a new or modified entity to the data store.
     *
     * @param TEntity $entity The entity to persist.
     * @return TEntity The persisted entity.
     */
    public function save(object $entity): object;

    /**
     * Remove an entity from the data store.
     *
     * @param TEntity $entity The entity to delete.
     * @return void
     */
    public function delete(object $entity): void;
}
