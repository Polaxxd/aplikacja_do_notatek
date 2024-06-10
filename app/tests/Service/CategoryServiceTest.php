<?php
/**
 * Category service tests.
 */

namespace App\Tests\Service;

use App\Entity\Category;
use App\Entity\Enum\UserRole;
use App\Entity\Note;
use App\Entity\Task;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\CategoryService;
use App\Service\CategoryServiceInterface;
use App\Service\NoteService;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class CategoryServiceTest.
 */
class CategoryServiceTest extends KernelTestCase
{
    /**
     * Category repository.
     */
    private ?EntityManagerInterface $entityManager;

    /**
     * Category service.
     */
    private ?CategoryServiceInterface $categoryService;

    /**
     * Set up test.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function setUp(): void
    {
        $container = static::getContainer();
        $this->entityManager = $container->get('doctrine.orm.entity_manager');
        $this->categoryService = $container->get(CategoryService::class);
    }

    /**
     * Test save.
     *
     * @throws ORMException
     */
    public function testSave(): void
    {
        // given
        $expectedCategory = new Category();
        $expectedCategory->setTitle('Test Category');

        // when
        $this->categoryService->save($expectedCategory);

        // then
        $expectedCategoryId = $expectedCategory->getId();
        $resultCategory = $this->entityManager->createQueryBuilder()
            ->select('category')
            ->from(Category::class, 'category')
            ->where('category.id = :id')
            ->setParameter(':id', $expectedCategoryId, Types::INTEGER)
            ->getQuery()
            ->getSingleResult();

        $this->assertEquals($expectedCategory, $resultCategory);
    }

    /**
     * Test delete.
     *
     * @throws OptimisticLockException|ORMException
     */
    public function testDelete(): void
    {
        // given
        $categoryToDelete = new Category();
        $categoryToDelete->setTitle('Test Category');
        $this->entityManager->persist($categoryToDelete);
        $this->entityManager->flush();
        $deletedCategoryId = $categoryToDelete->getId();

        // when
        $this->categoryService->delete($categoryToDelete);

        // then
        $resultCategory = $this->entityManager->createQueryBuilder()
            ->select('category')
            ->from(Category::class, 'category')
            ->where('category.id = :id')
            ->setParameter(':id', $deletedCategoryId, Types::INTEGER)
            ->getQuery()
            ->getOneOrNullResult();

        $this->assertNull($resultCategory);
    }

    /**
     * Test can be deleted.
     *
     * @throws OptimisticLockException|ORMException
     */
    public function testCanBeDeleted(): void
    {
        // given
        $categoryToDelete = new Category();
        $categoryToDelete->setTitle('Test Category');
        $this->entityManager->persist($categoryToDelete);
        $this->entityManager->flush();
        $deletedCategoryId = $categoryToDelete->getId();

        // when
        $verdict = $this->categoryService->canBeDeleted($deletedCategoryId);

        // then
        $resultTask = $this->entityManager->createQueryBuilder()
            ->select('task')
            ->from(Task::class, 'task')
            ->where('task.category = :category')
            ->setParameter(':category', $categoryToDelete)
            ->getQuery()
            ->getOneOrNullResult();
        $resultNote = $this->entityManager->createQueryBuilder()
            ->select('note')
            ->from(Note::class, 'note')
            ->where('note.category = :category')
            ->setParameter(':category', $categoryToDelete)
            ->getQuery()
            ->getOneOrNullResult();
        $resultDelete = is_null($resultTask) && is_null($resultNote);

        $this->assertEquals($resultDelete, $verdict);
    }

    /**
     * Test can be deleted when it can't.
     *
     * @throws OptimisticLockException|ORMException
     */
    public function testCanBeDeletedWhenItCant(): void
    {
        // given
        $categoryToDelete = new Category();
        $categoryToDelete->setTitle('Test Category');
        $this->entityManager->persist($categoryToDelete);
        $this->entityManager->flush();
        $deletedCategoryId = $categoryToDelete->getId();
        $createdUser = $this->createUser([UserRole::ROLE_USER->value, UserRole::ROLE_ADMIN->value]);
        $createdNote = $this->createNote($categoryToDelete, $createdUser);

        // when
        $verdict = $this->categoryService->canBeDeleted($deletedCategoryId);

        // then
        $resultNote = $this->entityManager->createQueryBuilder()
            ->select('note')
            ->from(Note::class, 'note')
            ->where('note.category = :category')
            ->setParameter(':category', $categoryToDelete)
            ->getQuery()
            ->getOneOrNullResult();

        $this->assertEquals(is_null($resultNote), $verdict);
    }

    /**
     * Test find by id.
     *
     * @throws ORMException
     */
    public function testFindById(): void
    {
        // given
        $expectedCategory = new Category();
        $expectedCategory->setTitle('Test Category');
        $this->entityManager->persist($expectedCategory);
        $this->entityManager->flush();
        $expectedCategoryId = $expectedCategory->getId();

        // when
        $resultCategory = $this->categoryService->findOneById($expectedCategoryId);

        // then
        $this->assertEquals($expectedCategory, $resultCategory);
    }

    /**
     * Test get paginated list.
     */
    public function testGetPaginatedList(): void
    {
        // given
        $page = 1;
        $dataSetSize = 3;
        $expectedResultSize = 3;

        $counter = 0;
        while ($counter < $dataSetSize) {
            $category = new Category();
            $category->setTitle('Test Category #'.$counter);
            $this->categoryService->save($category);

            ++$counter;
        }

        // when
        $result = $this->categoryService->getPaginatedList($page);

        // then
        $this->assertEquals($expectedResultSize, $result->count());
    }

    /**
     * Test category exists.
     *
     * @throws OptimisticLockException|ORMException
     */
    public function testCategoryExists(): void
    {
        // given
        $categoryToCheck = new Category();
        $categoryToCheck->setTitle('Test Category');
        $this->entityManager->persist($categoryToCheck);
        $this->entityManager->flush();
        $checkCategoryId = $categoryToCheck->getId();

        // when
        $verdict = $this->categoryService->categoryExists($checkCategoryId);

        // then
        $resultCategory = $this->entityManager->createQueryBuilder()
            ->select('category')
            ->from(Category::class, 'category')
            ->where('category.id = :id')
            ->setParameter(':id', $checkCategoryId, Types::INTEGER)
            ->getQuery()
            ->getOneOrNullResult();

        if ($verdict) {
            $this->assertNotNull($resultCategory);
        } else {
            $this->assertNull($resultCategory);
        }
    }

    /**
     * Create user.
     *
     * @param array $roles User roles
     *
     * @return User User entity
     *
     * @throws ContainerExceptionInterface|NotFoundExceptionInterface|ORMException|OptimisticLockException
     */
    private function createUser(array $roles): User
    {
        $passwordHasher = static::getContainer()->get('security.password_hasher');
        $user = new User();
        $user->setEmail('user@example.com');
        $user->setRoles($roles);
        $user->setPassword(
            $passwordHasher->hashPassword(
                $user,
                'p@55w0rd'
            )
        );
        $userRepository = static::getContainer()->get(UserRepository::class);
        $userRepository->save($user);

        return $user;
    }

    /**
     * Create note.
     *
     * @param Category $category Category
     * @param User     $user     User
     *
     * @return Note note
     */
    private function createNote(Category $category, User $user): Note
    {
        $note = new Note();
        $note->setTitle('Title');
        $note->setContent('NoteContent');
        $note->setUpdatedAt(new \DateTimeImmutable());
        $note->setCreatedAt(new \DateTimeImmutable());
        $note->setCategory($category);
        $note->setAuthor($user);
        $noteService = self::getContainer()->get(NoteService::class);
        $noteService->save($note);

        return $note;
    }
}
