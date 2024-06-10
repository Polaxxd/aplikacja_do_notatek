<?php
/**
 * Category service interface.
 */

namespace App\Service;

use App\Entity\Category;
use Knp\Component\Pager\Pagination\PaginationInterface;

/**
 * Interface CategoryServiceInterface.
 */
interface CategoryServiceInterface
{
    /**
     * Get paginated list.
     *
     * @param int $page Page number
     *
     * @return PaginationInterface<string, mixed> Paginated list
     */
    public function getPaginatedList(int $page): PaginationInterface;

    /**
     * Save entity.
     *
     * @param Category $category Category entity
     */
    public function save(Category $category): void;

    /**
     * Delete entity.
     *
     * @param Category $category Category entity
     */
    public function delete(Category $category): void;

    /**
     * Can Category be deleted?
     *
     * @param int $id Id
     *
     * @return bool Result
     */
    public function canBeDeleted($id): bool;

    /**
     * Find one by id.
     *
     * @param int $id Id
     */
    public function findOneById(int $id): ?Category;

    /**
     * Does category with this id exist?
     *
     * @param int $id Id
     *
     * @return bool Result
     */
    public function categoryExists($id): bool;
}
