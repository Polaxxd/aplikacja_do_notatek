<?php
/**
 * User service.
 */

namespace App\Service;

use App\Entity\User;
use App\Repository\NoteRepository;
use App\Repository\TaskRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Class UserService.
 */
class UserService implements UserServiceInterface
{
    /**
     * Items per page.
     *
     * Use constants to define configuration options that rarely change instead
     * of specifying them in app/config/config.yml.
     * See https://symfony.com/doc/current/best_practices.html#configuration
     *
     * @constant int
     */
    private const PAGINATOR_ITEMS_PER_PAGE = 10;

    /**
     * Constructor.
     *
     * @param UserRepository              $userRepository User repository
     * @param PaginatorInterface          $paginator      Paginator
     * @param TaskRepository              $taskRepository Task repository
     * @param NoteRepository              $noteRepository Note repository
     * @param EntityManagerInterface      $entityManager  Entity manager
     * @param UserPasswordHasherInterface $passwordHasher Password hasher
     */
    public function __construct(private readonly UserRepository $userRepository, private readonly PaginatorInterface $paginator, private readonly TaskRepository $taskRepository, private readonly NoteRepository $noteRepository, private readonly EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    /**
     * Get paginated list.
     *
     * @param int $page Page number
     *
     * @return PaginationInterface<string, mixed> Paginated list
     */
    public function getPaginatedList(int $page): PaginationInterface
    {
        return $this->paginator->paginate(
            $this->userRepository->queryAll(),
            $page,
            self::PAGINATOR_ITEMS_PER_PAGE
        );
    }

    /**
     * Save entity.
     *
     * @param User $user User entity
     */
    public function save(User $user): void
    {
        $this->userRepository->save($user);
    }

    /**
     * Registr new entity.
     *
     * @param User   $user          User entity
     * @param string $plainPassword Plain password
     */
    public function registerUser(User $user, string $plainPassword): void
    {
        $password = $this->passwordHasher->hashPassword($user, $plainPassword);
        $this->userRepository->registerUser($user, $password);
    }

    /**
     * Deleting user's notes.
     *
     * @param User $user User entity
     */
    public function deleteUsersTaskAndNotes(User $user): void
    {
        $tasks = $this->taskRepository->queryByAuthor($user)->getQuery()->getResult();

        foreach ($tasks as $userTask) {
            $this->entityManager->remove($userTask);
        }
        $this->entityManager->flush();

        $notes = $this->noteRepository->queryByAuthor($user)->getQuery()->getResult();

        foreach ($notes as $userNote) {
            $this->entityManager->remove($userNote);
        }
        $this->entityManager->flush();
    }

    /**
     * Delete entity.
     *
     * @param User $user User entity
     */
    public function delete(User $user): void
    {
        $this->deleteUsersTaskAndNotes($user);
        $this->userRepository->delete($user);
    }

    /**
     * Find one by id.
     *
     * @param int $id Id
     *
     * @return User|null User
     */
    public function findOneById(int $id): ?User
    {
        return $this->userRepository->findOneById($id);
    }
}
