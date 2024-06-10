<?php
/**
 * Category service.
 */

namespace App\Service;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use App\Repository\NoteRepository;
use App\Repository\TaskRepository;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;

/**
 * Class CategoryService.
 */
class CategoryService implements CategoryServiceInterface
{
    //    /**
    //     * Category repository.
    //     */
    //    private CategoryRepository $categoryRepository;
    //    /**
    //     * Task Repository.
    //     */
    //    private TaskRepository $taskRepository;

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
     * @param CategoryRepository $categoryRepository Category repository
     * @param TaskRepository     $taskRepository     Task repository
     * @param NoteRepository     $noteRepository     Note repository
     * @param PaginatorInterface $paginator          Paginator
     */
    public function __construct(private readonly CategoryRepository $categoryRepository, private readonly TaskRepository $taskRepository, private readonly NoteRepository $noteRepository, private readonly PaginatorInterface $paginator)
    {
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
            $this->categoryRepository->queryAll(),
            $page,
            self::PAGINATOR_ITEMS_PER_PAGE
        );
    }

    /**
     * Find one by id.
     *
     * @param int $id Id
     *
     * @return Category|null Category
     */
    public function findOneById(int $id): ?Category
    {
        $category = $this->categoryRepository->findOneById($id);

        return $category;
    }

    /**
     * Save entity.
     *
     * @param Category $category Category entity
     */
    public function save(Category $category): void
    {
        $this->categoryRepository->save($category);
    }

    /**
     * Delete entity.
     *
     * @param Category $category Category entity
     */
    public function delete(Category $category): void
    {
        $this->categoryRepository->delete($category);
    }

    /**
     * Can Category be deleted?
     *
     * @param int $id Id
     *
     * @return bool Result
     */
    public function canBeDeleted($id): bool
    {
        $category = $this->findOneById($id);
        $taskCount = $this->taskRepository->countByCategory($category);
        $noteCount = $this->noteRepository->countByCategory($category);

        if (0 === $taskCount && 0 === $noteCount) {
            return true;
        }

        return false;
    }

    /**
     * Does category with this id exist?
     *
     * @param int $id Id
     *
     * @return bool Result
     */
    public function categoryExists($id): bool
    {
        $categoryCount = $this->findOneById($id);

        return !is_null($categoryCount);
    }
}
