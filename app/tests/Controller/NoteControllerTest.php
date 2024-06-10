<?php
/**
 * Note Controller test.
 */

namespace App\Tests\Controller;

use App\Entity\Category;
use App\Entity\Note;
use App\Entity\Enum\UserRole;
use App\Entity\User;
use App\Repository\CategoryRepository;
use App\Repository\UserRepository;
use App\Service\NoteService;
use App\Service\NoteServiceInterface;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class NoteControllerTest.
 */
class NoteControllerTest extends WebTestCase
{
    /**
     * Test route.
     *
     * @const string
     */
    public const TEST_ROUTE = '/note';

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
     * Test show single non-existent note.
     */
    public function testShowNoteForNonExistentNote(): void
    {
        // given
        $expectedStatusCode = 302;
        $testNoteId = 1230;
        $adminUser = $this->createUser([UserRole::ROLE_ADMIN->value, UserRole::ROLE_USER->value]);
        $this->httpClient->loginUser($adminUser);

        // when
        $this->httpClient->request('GET', self::TEST_ROUTE.'/'.$testNoteId);
        $actualStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $actualStatusCode);
    }

    /**
     * Test show single note.
     */
    public function testShowNoteWithMock(): void
    {
        // given
        $expectedStatusCode = 200;
        $testCategoryId = 123;
        $expectedCategory = new Category();
        $categoryIdProperty = new \ReflectionProperty(Category::class, 'id');
        $categoryIdProperty->setValue($expectedCategory, $testCategoryId);
        $expectedCategory->setTitle('Test category');

        $testNoteId = 122;
        $expectedNote = new Note();
        $noteIdProperty = new \ReflectionProperty(Note::class, 'id');
        $noteIdProperty->setValue($expectedNote, $testNoteId);
        $expectedNote->setTitle('Test note');
        $expectedNote->setContent('Test note content');
        $expectedNote->setCreatedAt(new \DateTimeImmutable());
        $expectedNote->setUpdatedAt(new \DateTimeImmutable());
        $expectedNote->setCategory($this->createCategory());

        $noteService = $this->createMock(NoteServiceInterface::class);
        $noteService->expects($this->once())
            ->method('findOneById')
            ->with($testNoteId)
            ->willReturn($expectedNote);
        static::getContainer()->set(NoteServiceInterface::class, $noteService);

        $adminUser = $this->createUser([UserRole::ROLE_ADMIN->value, UserRole::ROLE_USER->value]);
        $expectedNote->setAuthor($adminUser);
        $this->httpClient->loginUser($adminUser);

        // when
        $this->httpClient->request('GET', self::TEST_ROUTE.'/'.$expectedNote->getId());
        $actualStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $actualStatusCode);
    }

    /**
     * Test create note.
     */
    public function testCreateNote(): void
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
     * Test create and save note.
     */
    public function testCreateSaveNote(): void
    {
        // given
        $expectedStatusCode = 302;
        $adminUser = $this->createUser([UserRole::ROLE_ADMIN->value, UserRole::ROLE_USER->value]);
        $this->httpClient->loginUser($adminUser);
        $createdNoteTitle = 'newCreatedNote';
        $createdNoteContent = 'newCreatedNoteContent';
        $createdCategory = $this->createCategory();

        // when
        $route = self::TEST_ROUTE.'/create';
        $this->httpClient->request('GET', $route);
        $this->httpClient->submitForm(
            'Zapisz',
            ['note' => ['title' => $createdNoteTitle, 'content' => $createdNoteContent, 'category' => $createdCategory->getId()]]
        );

        // then
        $actualStatusCode = $this->httpClient->getResponse()->getStatusCode();
        $this->assertEquals($expectedStatusCode, $actualStatusCode);
    }

    /**
     * Test edit note.
     */
    public function testEditNoteWithMock(): void
    {
        // given
        $expectedStatusCode = 200;
        $testCategoryId = 123;
        $expectedCategory = new Category();
        $categoryIdProperty = new \ReflectionProperty(Category::class, 'id');
        $categoryIdProperty->setValue($expectedCategory, $testCategoryId);
        $expectedCategory->setTitle('Test category');

        $testNoteId = 122;
        $expectedNote = new Note();
        $noteIdProperty = new \ReflectionProperty(Note::class, 'id');
        $noteIdProperty->setValue($expectedNote, $testNoteId);
        $expectedNote->setTitle('Test note');
        $expectedNote->setContent('Test note content');
        $expectedNote->setCreatedAt(new \DateTimeImmutable());
        $expectedNote->setUpdatedAt(new \DateTimeImmutable());
        $expectedNote->setCategory($this->createCategory());

        $noteService = $this->createMock(NoteServiceInterface::class);
        $noteService->expects($this->once())
            ->method('findOneById')
            ->with($testNoteId)
            ->willReturn($expectedNote);
        static::getContainer()->set(NoteServiceInterface::class, $noteService);

        $adminUser = $this->createUser([UserRole::ROLE_ADMIN->value, UserRole::ROLE_USER->value]);
        $expectedNote->setAuthor($adminUser);
        $this->httpClient->loginUser($adminUser);

        // when
        $route = self::TEST_ROUTE.'/'.$expectedNote->getId().'/edit';
        $this->httpClient->request('GET', $route);
        $actualStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $actualStatusCode);
        $this->assertSelectorTextContains('html h1', '#'.$expectedNote->getId());
    }

    /**
     * Test edit non-existent note.
     */
    public function testEditNonExistentNote(): void
    {
        // given
        $expectedStatusCode = 302;
        $nonExistentId = 123456789;
        $adminUser = $this->createUser([UserRole::ROLE_ADMIN->value, UserRole::ROLE_USER->value]);
        $this->httpClient->loginUser($adminUser);

        // when
        $route = self::TEST_ROUTE.'/'.$nonExistentId.'/edit';
        $this->httpClient->request('GET', $route);
        $actualStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $actualStatusCode);
    }

    /**
     * Test edit and save note.
     */
    public function testEditNoteForm(): void
    {
        // given
        $expectedStatusCode = 302;
        $adminUser = $this->createUser([UserRole::ROLE_ADMIN->value, UserRole::ROLE_USER->value]);
        $this->httpClient->loginUser($adminUser);
        $createdNoteNewTitle = 'newCreatedNote';
        $createdNoteNewContent = 'newCreatedNoteContent';
        $createdCategory = $this->createCategory();
        $createdNote = $this->createNote($createdCategory, $adminUser);
        // when
        $route = $route = self::TEST_ROUTE.'/'.$createdNote->getId().'/edit';
        //        echo $route;
        $this->httpClient->request('GET', $route);
        $this->httpClient->submitForm(
            'Edytuj',
            ['note' => [
                'title' => $createdNoteNewTitle,
                'content' => $createdNoteNewContent,
                'category' => $createdCategory->getId(),
            ],
            ]
        );

        // then
        $actualStatusCode = $this->httpClient->getResponse()->getStatusCode();
        $this->assertEquals($expectedStatusCode, $actualStatusCode);
    }

    /**
     * Test delete note.
     */
    public function testDeleteNoteWithMock(): void
    {
        // given
        $expectedStatusCode = 200;
        $testCategoryId = 123;
        $expectedCategory = new Category();
        $categoryIdProperty = new \ReflectionProperty(Category::class, 'id');
        $categoryIdProperty->setValue($expectedCategory, $testCategoryId);
        $expectedCategory->setTitle('Test category');
        $testNoteId = 122;
        $expectedNote = new Note();
        $noteIdProperty = new \ReflectionProperty(Note::class, 'id');
        $noteIdProperty->setValue($expectedNote, $testNoteId);
        $expectedNote->setTitle('Test note');
        $expectedNote->setContent('Test note content');
        $expectedNote->setCreatedAt(new \DateTimeImmutable());
        $expectedNote->setUpdatedAt(new \DateTimeImmutable());
        $expectedNote->setCategory($this->createCategory());
        $noteService = $this->createMock(NoteServiceInterface::class);
        $noteService->expects($this->once())
            ->method('findOneById')
            ->with($testNoteId)
            ->willReturn($expectedNote);
        static::getContainer()->set(NoteServiceInterface::class, $noteService);
        $adminUser = $this->createUser([UserRole::ROLE_ADMIN->value, UserRole::ROLE_USER->value]);
        $expectedNote->setAuthor($adminUser);
        $this->httpClient->loginUser($adminUser);
        // when
        $route = self::TEST_ROUTE.'/'.$expectedNote->getId().'/delete';
        $this->httpClient->request('GET', $route);
        $actualStatusCode = $this->httpClient->getResponse()->getStatusCode();
        // then
        $this->assertEquals($expectedStatusCode, $actualStatusCode);
    }

    /**
     * Test delete non-existent note.
     */
    public function testDeleteNonExistentNote(): void
    {
        // given
        $expectedStatusCode = 302;
        $nonExistentId = 123456789;
        $adminUser = $this->createUser([UserRole::ROLE_ADMIN->value, UserRole::ROLE_USER->value]);
        $this->httpClient->loginUser($adminUser);
        // when
        $route = $route = self::TEST_ROUTE.'/'.$nonExistentId.'/delete';
        $this->httpClient->request('GET', $route);
        $actualStatusCode = $this->httpClient->getResponse()->getStatusCode();
        //        echo $this->httpClient->getResponse()->getContent();
        // then
        $this->assertEquals($expectedStatusCode, $actualStatusCode);
    }

    /**
     * Test delete note.
     */
    public function testDeleteNoteForm(): void
    {
        // given
        $expectedStatusCode = 302;
        $adminUser = $this->createUser([UserRole::ROLE_ADMIN->value, UserRole::ROLE_USER->value]);
        $this->httpClient->loginUser($adminUser);
        $expectedCategory = $this->createCategory();
        $expectedTask = $this->createNote($expectedCategory, $adminUser);
        $route = self::TEST_ROUTE.'/'.$expectedTask->getId().'/delete';
        //        echo $route;
        $this->httpClient->request('GET', $route);
        $this->httpClient->submitForm(
            'Usuń'
        );

        // then
        $actualStatusCode = $this->httpClient->getResponse()->getStatusCode();
        $this->assertEquals($expectedStatusCode, $actualStatusCode);
    }

    /**
     * Create user.
     *
     * @param array $roles User roles
     *
     * @return User User entity
     *
     * @throws ORMException|OptimisticLockException|ContainerExceptionInterface|NotFoundExceptionInterface
     */
    private function createUser(array $roles): User
    {
        $userRepository = static::getContainer()->get(UserRepository::class);
        $userEmail = 'user'.random_int(1, 100).'@example.com';

        $user = new User();
        $user->setEmail($userEmail);
        $user->setRoles($roles);
        $user->setPassword('p@$$w0rd');
        $userRepository->save($user);

        return $user;
    }

    /**
     * Create category.
     *
     * @return Category Category entity
     *
     * @throws ORMException|OptimisticLockException|ContainerExceptionInterface|NotFoundExceptionInterface
     */
    private function createCategory(): Category
    {
        $categoryRepository = static::getContainer()->get(CategoryRepository::class);
        $categoryTitle = 'Category #'.random_int(1, 100);

        $category = new Category();
        $category->setTitle($categoryTitle);
        $categoryRepository->save($category);

        return $category;
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
