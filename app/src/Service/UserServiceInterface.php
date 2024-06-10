<?php
/**
 * User service interface.
 */

namespace App\Service;

use App\Entity\User;
use Knp\Component\Pager\Pagination\PaginationInterface;

/**
 * Interface UserServiceInterface.
 */
interface UserServiceInterface
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
     * @param User $user User entity
     */
    public function save(User $user): void;

    /**
     * Register new entity.
     *
     * @param User   $user          User entity
     * @param string $plainPassword Plain password
     */
    public function registerUser(User $user, string $plainPassword): void;

    /**
     * Deleting user's notes.
     *
     * @param User $user User entity
     */
    public function deleteUsersTaskAndNotes(User $user): void;

    /**
     * Delete entity.
     *
     * @param User $user User entity
     */
    public function delete(User $user): void;

    /**
     * Find one by id.
     *
     * @param int $id Id
     */
    public function findOneById(int $id): ?User;

    //    /**
    //     * Find one by email.
    //     *
    //     */
    //    public function findOneByEmail(int $email): ?User;

    //    /**
    //     * Upgrade user password.
    //     *
    //     * @param User $user User entity
    //     * @param string $plainPassword
    //     */
    //    public function ugradeUserPassword(User $user, string $plainPassword): void;
}
