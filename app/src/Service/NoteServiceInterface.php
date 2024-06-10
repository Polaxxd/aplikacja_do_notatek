<?php
/**
 * Note service interface.
 */

namespace App\Service;

use App\Entity\Note;
use App\Entity\User;
use Knp\Component\Pager\Pagination\PaginationInterface;

/**
 * Interface NoteServiceInterface.
 */
interface NoteServiceInterface
{
    /**
     * Get paginated list.
     *
     * @param int  $page   Page number
     * @param User $author Author
     *
     * @return PaginationInterface<string, mixed> Paginated list
     */
    public function getPaginatedList(int $page, User $author): PaginationInterface;

    /**
     * Save entity.
     *
     * @param Note $note Note entity
     */
    public function save(Note $note): void;

    /**
     * Delete entity.
     *
     * @param Note $note Note entity
     */
    public function delete(Note $note): void;

    /**
     * Find one by id.
     *
     * @param int $id Id
     */
    public function findOneById(int $id): ?Note;
}
