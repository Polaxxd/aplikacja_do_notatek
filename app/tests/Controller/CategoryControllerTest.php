<?php
/**
 * Category Controller test.
 */

namespace App\Tests\Controller;

use App\Entity\Category;
use App\Entity\Enum\UserRole;
use App\Entity\Note;
use App\Entity\User;
use App\Repository\CategoryRepository;
use App\Repository\UserRepository;
use App\Service\CategoryServiceInterface;
use App\Service\NoteService;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class CategoryControllerTest.
 */
class CategoryControllerTest extends WebTestCase
{
    /**
     * Test route.
     *
     * @const string
     */
    public const TEST_ROUTE = '/category';

    /**
     * Test client.
     */
    private KernelBrowser $httpClient;

    /**
     * Set up tests.
     */
    public function setUp(): void
    {
        $this->httpClient = static::createClient();
    }

    /**
     * Test index route for non-authorized user.
     *
     * @throws ContainerExceptionInterface|NotFoundExceptionInterface|ORMException|OptimisticLockException
     */
    public function testIndexRouteNonAuthorizedUser(): void
    {
        // given
        $expectedStatusCode = 302;

        // when
        $this->httpClient->request('GET', self::TEST_ROUTE);
        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);
    }

    /**
     * Test show single category for non-authorised user.
     */
    public function testShowCategoryNonAuthorizedUser(): void
    {
        // given
        $expectedStatusCode = 302;
        $testCategoryId = 123;
        $expectedCategory = new Category();
        $categoryIdProperty = new \ReflectionProperty(Category::class, 'id');
        $categoryIdProperty->setValue($expectedCategory, $testCategoryId);
        $expectedCategory->setTitle('Test category');
        $expectedCategory->setCreatedAt(new \DateTimeImmutable());
        $expectedCategory->setUpdatedAt(new \DateTimeImmutable());
        $expectedCategory->setSlug('test-category');
        $categoryService = $this->createMock(CategoryServiceInterface::class);
        static::getContainer()->set(CategoryServiceInterface::class, $categoryService);

        // when
        $this->httpClient->request('GET', self::TEST_ROUTE.'/'.$expectedCategory->getId());
        $actualStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $actualStatusCode);
        $this->assertTrue($this->httpClient->getResponse()->isRedirect());
        $this->assertEquals('/login', $this->httpClient->getResponse()->headers->get('Location'));
    }

    /**
     * Test index route for anonymous user.
     */
    public function testIndexRouteAnonymousUser(): void
    {
        // given
        $expectedStatusCode = 302;

        // when
        $this->httpClient->request('GET', self::TEST_ROUTE);
        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);
    }

    /**
     * Test index route for admin user.
     *
     * @throws ContainerExceptionInterface|NotFoundExceptionInterface|ORMException|OptimisticLockException
     */
    public function testIndexRouteAdminUser(): void
    {
        // given
        $expectedStatusCode = 200;
        $adminUser = $this->createUser([UserRole::ROLE_USER->value, UserRole::ROLE_ADMIN->value]);
        $this->httpClient->loginUser($adminUser);

        // when
        $this->httpClient->request('GET', self::TEST_ROUTE);
        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);
    }

    /**
     * Test show single category.
     */
    public function testShowCategoryForNonExistentCategory(): void
    {
        // given
        $expectedStatusCode = 404;
        $testCategoryId = 1230;
        $adminUser = $this->createUser([UserRole::ROLE_ADMIN->value, UserRole::ROLE_USER->value]);
        $this->httpClient->loginUser($adminUser);

        // when
        $this->httpClient->request('GET', self::TEST_ROUTE.'/'.$testCategoryId);
        $actualStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $actualStatusCode);
    }

    /**
     * Test edit non-existent category.
     */
    public function testEditCategoryForNonExistentCategory(): void
    {
        // given
        $expectedStatusCode = 302;
        $testCategoryId = 1234;
        $adminUser = $this->createUser([UserRole::ROLE_ADMIN->value, UserRole::ROLE_USER->value]);
        $this->httpClient->loginUser($adminUser);

        // when
        $route = self::TEST_ROUTE.'/'.$testCategoryId.'/edit';
        $this->httpClient->request('GET', $route);
        $actualStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $actualStatusCode);
    }

    /**
     * Test delete non-existant category.
     */
    public function testDeleteCategoryForNonExistentCategory(): void
    {
        // given
        $expectedStatusCode = 302;
        $testCategoryId = 1234;
        $adminUser = $this->createUser([UserRole::ROLE_ADMIN->value, UserRole::ROLE_USER->value]);
        $this->httpClient->loginUser($adminUser);

        // when
        $route = self::TEST_ROUTE.'/'.$testCategoryId.'/delete';
        $this->httpClient->request('GET', $route);
        $actualStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $actualStatusCode);
    }

    /**
     * Test show single category.
     */
    public function testShowCategoryWithMock(): void
    {
        // given
        $expectedStatusCode = 200;
        $testCategoryId = 123;
        $expectedCategory = new Category();
        $categoryIdProperty = new \ReflectionProperty(Category::class, 'id');
        $categoryIdProperty->setValue($expectedCategory, $testCategoryId);
        $expectedCategory->setTitle('Test category');
        $expectedCategory->setCreatedAt(new \DateTimeImmutable());
        $expectedCategory->setUpdatedAt(new \DateTimeImmutable());
        $expectedCategory->setSlug('test-category');
        $categoryService = $this->createMock(CategoryServiceInterface::class);
        $categoryService->expects($this->once())
            ->method('findOneById')
            ->with($testCategoryId)
            ->willReturn($expectedCategory);
        static::getContainer()->set(CategoryServiceInterface::class, $categoryService);
        $adminUser = $this->createUser([UserRole::ROLE_ADMIN->value, UserRole::ROLE_USER->value]);
        $this->httpClient->loginUser($adminUser);

        // when
        $this->httpClient->request('GET', self::TEST_ROUTE.'/'.$expectedCategory->getId());
        $actualStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $actualStatusCode);
        $this->assertSelectorTextContains('html h1', '#'.$expectedCategory->getId());
    }

    /**
     * Test create category.
     */
    public function testCreateCategory(): void
    {
        // given
        $expectedStatusCode = 200;
        $adminUser = $this->createUser([UserRole::ROLE_ADMIN->value, UserRole::ROLE_USER->value]);
        $this->httpClient->loginUser($adminUser);

        // when
        $route = self::TEST_ROUTE.'/create';
        $this->httpClient->request('GET', $route);
        $actualStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $actualStatusCode);
    }

    /**
     * Test create and save category.
     */
    public function testCreateSaveCategory(): void
    {
        // given
        $expectedStatusCode = 302;
        $adminUser = $this->createUser([UserRole::ROLE_ADMIN->value, UserRole::ROLE_USER->value]);
        $this->httpClient->loginUser($adminUser);
        $createdCategoryTitle = 'createdCat';
        $categoryRepository = static::getContainer()->get(CategoryRepository::class);

        // when
        $route = self::TEST_ROUTE.'/create';
        $this->httpClient->request('GET', $route);
        $this->httpClient->submitForm(
            'Zapisz',
            [
                'category' => [
                    'title' => $createdCategoryTitle,
                ],
            ]
        );

        // then
        $savedCategory = $categoryRepository->findOneByTitle($createdCategoryTitle);
        $this->assertEquals($createdCategoryTitle, $savedCategory->getTitle());
        $this->assertEquals($expectedStatusCode, $this->httpClient->getResponse()->getStatusCode());
        $this->assertTrue($this->httpClient->getResponse()->isRedirect());
    }

    /**
     * Test edit category.
     */
    public function testEditCategory(): void
    {
        // given
        $expectedStatusCode = 200;
        $adminUser = $this->createUser([UserRole::ROLE_ADMIN->value, UserRole::ROLE_USER->value]);
        $this->httpClient->loginUser($adminUser);
        $categoryToEdit = new Category();
        $categoryToEdit->setTitle('title');
        $categoryToEdit->setCreatedAt(new \DateTimeImmutable());
        $categoryToEdit->setUpdatedAt(new \DateTimeImmutable());
        $categoryToEdit->setSlug('slug');
        $categoryRepository = static::getContainer()->get(CategoryRepository::class);
        $categoryRepository->save($categoryToEdit);

        // when
        $route = self::TEST_ROUTE.'/'.$categoryToEdit->getId().'/edit';
        $this->httpClient->request('GET', $route);
        $actualStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $actualStatusCode);
    }

    /**
     * Test edit and save category.
     */
    public function testEditSaveCategoryWithMock(): void
    {
        // given
        $expectedStatusCode = 302;
        $testCategoryId = 120;
        $expectedCategory = new Category();
        $categoryIdProperty = new \ReflectionProperty(Category::class, 'id');
        $categoryIdProperty->setValue($expectedCategory, $testCategoryId);
        $expectedCategory->setTitle('Test category');
        $expectedCategory->setCreatedAt(new \DateTimeImmutable());
        $expectedCategory->setUpdatedAt(new \DateTimeImmutable());
        $expectedCategory->setSlug('test-category');
        $categoryService = $this->createMock(CategoryServiceInterface::class);
        $categoryService->expects($this->once())
            ->method('findOneById')
            ->with($testCategoryId)
            ->willReturn($expectedCategory);
        $categoryService->expects($this->once())
            ->method('categoryExists')
            ->with($testCategoryId)
            ->willReturn(true);
        static::getContainer()->set(CategoryServiceInterface::class, $categoryService);
        $adminUser = $this->createUser([UserRole::ROLE_ADMIN->value, UserRole::ROLE_USER->value]);
        $this->httpClient->loginUser($adminUser);
        // when
        $route = self::TEST_ROUTE.'/'.$expectedCategory->getId().'/edit';
        $this->httpClient->request('GET', $route);
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form[name="category"]');

        $editedCategoryTitle = 'newEditedCategory';
        $this->httpClient->submitForm(
            'Edytuj',
            [
                'category[title]' => $editedCategoryTitle,
            ],
        );

        $actualStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $actualStatusCode);
    }

    /**
     * Test delete category.
     */
    public function testDeleteCategoryWithMock(): void
    {
        // given
        $expectedStatusCode = 200;
        $testCategoryId = 123;
        $expectedCategory = new Category();
        $categoryIdProperty = new \ReflectionProperty(Category::class, 'id');
        $categoryIdProperty->setValue($expectedCategory, $testCategoryId);
        $expectedCategory->setTitle('Test category');
        $expectedCategory->setCreatedAt(new \DateTimeImmutable());
        $expectedCategory->setUpdatedAt(new \DateTimeImmutable());
        $expectedCategory->setSlug('test-category');
        $categorySlug = $expectedCategory->getSlug();
        $categoryService = $this->createMock(CategoryServiceInterface::class);
        $categoryService->expects($this->once())
            ->method('findOneById')
            ->with($testCategoryId)
            ->willReturn($expectedCategory);
        $categoryService->expects($this->once())
            ->method('categoryExists')
            ->with($testCategoryId)
            ->willReturn(true);
        $categoryService->expects($this->once())
            ->method('canBeDeleted')
            ->with($testCategoryId)
            ->willReturn(true);
        static::getContainer()->set(CategoryServiceInterface::class, $categoryService);
        $adminUser = $this->createUser([UserRole::ROLE_ADMIN->value, UserRole::ROLE_USER->value]);
        $this->httpClient->loginUser($adminUser);
        // when
        $this->httpClient->request('GET', self::TEST_ROUTE.'/'.$expectedCategory->getId().'/delete');
        $actualStatusCode = $this->httpClient->getResponse()->getStatusCode();
        // then
        $this->assertEquals($expectedStatusCode, $actualStatusCode);
        $this->assertSelectorTextContains('html h1', '#'.$expectedCategory->getId());
    }

    /**
     * Test delete category when can't be deleted.
     */
    public function testDeleteCategoryCantBeDeleted(): void
    {
        // given
        $expectedStatusCode = 302;
        $testCategoryId = 123;
        $expectedCategory = $this->createCategory();
        $adminUser = $this->createUser([UserRole::ROLE_ADMIN->value, UserRole::ROLE_USER->value]);
        $this->httpClient->loginUser($adminUser);
        $createdNote = $this->createNote($expectedCategory, $adminUser);

        // when
        $this->httpClient->request('GET', self::TEST_ROUTE.'/'.$expectedCategory->getId().'/delete');
        $actualStatusCode = $this->httpClient->getResponse()->getStatusCode();
        // then
        $this->assertEquals($expectedStatusCode, $actualStatusCode);
    }

    /**
     * Test delete category.
     */
    public function testDeleteCategoryForm(): void
    {
        // given
        $expectedStatusCode = 302;
        $expectedCategory = $this->createCategory();
        $adminUser = $this->createUser([UserRole::ROLE_ADMIN->value, UserRole::ROLE_USER->value]);
        $this->httpClient->loginUser($adminUser);
        $route = self::TEST_ROUTE.'/'.$expectedCategory->getId().'/delete';
        $this->httpClient->request('GET', $route);
        $this->httpClient->submitForm(
            'Usuń'
        );
        // then
        $actualStatusCode = $this->httpClient->getResponse()->getStatusCode();
        $this->assertEquals($expectedStatusCode, $actualStatusCode);
    }

    /**
     * Create category.
     *
     * @return Category category
     */
    protected function createCategory(): Category
    {
        $category = new Category();
        $category->setTitle('Title');
        $category->setUpdatedAt(new \DateTimeImmutable());
        $category->setCreatedAt(new \DateTimeImmutable());
        $categoryRepository = self::getContainer()->get(CategoryRepository::class);
        $categoryRepository->save($category);

        return $category;
    }

    /**
     * Create user.
     *
     * @param array $roles User roles
     *
     * @return User User entity
     */
    private function createUser(array $roles): User
    {
        $userRepository = static::getContainer()->get(UserRepository::class);
        $passwordHasher = static::getContainer()->get('security.user_password_hasher');
        $user = new User();
        $user->setEmail('user'.microtime().'@example.com');
        $user->setRoles($roles);
        $user->setPassword(
            $passwordHasher->hashPassword(
                $user,
                'p@55w0rd'
            )
        );

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
    private function createNote($category, $user): Note
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
