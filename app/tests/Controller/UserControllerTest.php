<?php

/**
 * User Controller test.
 */

namespace App\Tests\Controller;

use App\Entity\Category;
use App\Entity\Note;
use App\Entity\Task;
use App\Entity\User;
use App\Entity\Enum\UserRole;
use App\Repository\CategoryRepository;
use App\Repository\UserRepository;
use App\Service\NoteService;
use App\Service\TaskService;
use App\Service\UserService;
use App\Service\UserServiceInterface;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class UserControllerTest.
 */
class UserControllerTest extends WebTestCase
{
    /**
     * Test route.
     *
     * @const string
     */
    public const TEST_ROUTE = '/user';

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
     * Test index route for non-admin user.
     *
     * @throws ContainerExceptionInterface|NotFoundExceptionInterface|ORMException|OptimisticLockException
     */
    public function testIndexRouteNonAdminUser(): void
    {
        // given
        $expectedStatusCode = 302;
        $normalUser = $this->createUser([UserRole::ROLE_USER->value]);
        $this->httpClient->loginUser($normalUser);

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
     * Test show single non-existent user.
     */
    public function testShowUserForNonExistentUser(): void
    {
        // given
        $expectedStatusCode = 302;
        $testUserId = 1230;
        $adminUser = $this->createUser([UserRole::ROLE_ADMIN->value, UserRole::ROLE_USER->value]);
        $this->httpClient->loginUser($adminUser);

        // when
        $this->httpClient->request('GET', self::TEST_ROUTE.'/'.$testUserId);
        $actualStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $actualStatusCode);
    }

    /**
     * Test show single user for non-admin.
     */
    public function testShowUserWithMockNonAdmin(): void
    {
        // given
        $expectedStatusCode = 302;
        $testUserId = 122;
        $expectedUser = new User();
        $userIdProperty = new \ReflectionProperty(User::class, 'id');
        $userIdProperty->setValue($expectedUser, $testUserId);
        $expectedUser->setEmail('u@e.pl');
        $expectedUser->setPassword('u123');
        $expectedUser->setRoles([UserRole::ROLE_USER->value]);
        $normalUser = $this->createUser([UserRole::ROLE_USER->value]);
        $this->httpClient->loginUser($normalUser);

        // when
        $this->httpClient->request('GET', self::TEST_ROUTE.'/'.$expectedUser->getId());
        $actualStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $actualStatusCode);
    }

    /**
     * Test show single user.
     */
    public function testShowUserWithMock(): void
    {
        // given
        $expectedStatusCode = 200;
        $testUserId = 122;
        $expectedUser = new User();
        $userIdProperty = new \ReflectionProperty(User::class, 'id');
        $userIdProperty->setValue($expectedUser, $testUserId);
        $expectedUser->setEmail('u@e.pl');
        $expectedUser->setPassword('u123');
        $expectedUser->setRoles([UserRole::ROLE_USER->value]);
        $userService = $this->createMock(UserServiceInterface::class);
        $userService->expects($this->once())
            ->method('findOneById')
            ->with($testUserId)
            ->willReturn($expectedUser);
        static::getContainer()->set(UserServiceInterface::class, $userService);
        $adminUser = $this->createUser([UserRole::ROLE_ADMIN->value, UserRole::ROLE_USER->value]);
        $this->httpClient->loginUser($adminUser);

        // when
        $this->httpClient->request('GET', self::TEST_ROUTE.'/'.$expectedUser->getId());
        $actualStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $actualStatusCode);
        $adminUser->eraseCredentials();
    }

    /**
     * Test create user.
     */
    public function testCreateUser(): void
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
     * Test create user by non-admin.
     */
    public function testCreateUserByNonAdmin(): void
    {
        // given
        $expectedStatusCode = 302;
        $normalUser = $this->createUser([UserRole::ROLE_USER->value]);
        $this->httpClient->loginUser($normalUser);

        // when
        $route = self::TEST_ROUTE.'/create';
        $this->httpClient->request('GET', $route);
        $actualStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $actualStatusCode);
    }

    /**
     * Test add user.
     */
    public function testAddUser(): void
    {
        // given
        $expectedStatusCode = 302;
        $adminUser = $this->createUser([UserRole::ROLE_ADMIN->value, UserRole::ROLE_USER->value]);
        $this->httpClient->loginUser($adminUser);
        $createdUserEmail = 'newuser@example.com';
        $createdUserPassword = 'user1234';
        $userRepository = static::getContainer()->get(UserRepository::class);

        // when
        $route = self::TEST_ROUTE.'/create';
        $this->httpClient->request('GET', $route);
        $this->httpClient->submitForm(
            'Zapisz',
            ['user' => ['email' => $createdUserEmail, 'password' => $createdUserPassword]]
        );

        // then
        $actualStatusCode = $this->httpClient->getResponse()->getStatusCode();
        $this->assertEquals($expectedStatusCode, $actualStatusCode);
    }

    /**
     * Test delete user route.
     */
    public function testDeleteUser(): void
    {
        // given
        $expectedStatusCode = 200;
        $adminUser = $this->createUser([UserRole::ROLE_ADMIN->value, UserRole::ROLE_USER->value]);
        $this->httpClient->loginUser($adminUser);
        $expectedUser = $this->createUser2([UserRole::ROLE_USER->value]);
        $route = self::TEST_ROUTE.'/'.$expectedUser->getId().'/delete';

        // then
        $this->httpClient->request('GET', $route);
        $actualStatusCode = $this->httpClient->getResponse()->getStatusCode();
        $this->assertEquals($expectedStatusCode, $actualStatusCode);
    }

    /**
     * Test delete user by non admin.
     */
    public function testDeleteUserByNonAdmin(): void
    {
        // given
        $expectedStatusCode = 302;
        $normalUser = $this->createUser([UserRole::ROLE_USER->value]);
        $this->httpClient->loginUser($normalUser);
        $expectedUser = $this->createUser2([UserRole::ROLE_USER->value]);
        $route = self::TEST_ROUTE.'/'.$expectedUser->getId().'/delete';

        // then
        $this->httpClient->request('GET', $route);
        $actualStatusCode = $this->httpClient->getResponse()->getStatusCode();
        $this->assertEquals($expectedStatusCode, $actualStatusCode);
    }

    /**
     * Test delete user form.
     */
    public function testDeleteUserForm(): void
    {
        // given
        $expectedStatusCode = 302;
        $adminUser = $this->createUser([UserRole::ROLE_ADMIN->value, UserRole::ROLE_USER->value]);
        $this->httpClient->loginUser($adminUser);
        $expectedUser = $this->createUser2([UserRole::ROLE_USER->value]);
        $route = self::TEST_ROUTE.'/'.$expectedUser->getId().'/delete';
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
     * Test delete user with notes.
     */
    public function testDeleteUserWithNotes(): void
    {
        // given
        $expectedStatusCode = 302;
        $adminUser = $this->createUser([UserRole::ROLE_ADMIN->value, UserRole::ROLE_USER->value]);
        $this->httpClient->loginUser($adminUser);
        $expectedUser = $this->createUser2([UserRole::ROLE_USER->value]);
        $expectedCategory = $this->createCategory();
        $expectedNote = $this->createNote($expectedCategory, $expectedUser);
        $expectedTask = $this->createTask($expectedCategory, $expectedUser);
        $route = self::TEST_ROUTE.'/'.$expectedUser->getId().'/delete';
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
     * Create second user.
     *
     * @param array $roles User roles
     *
     * @return User User entity
     *
     * @throws ContainerExceptionInterface|NotFoundExceptionInterface|ORMException|OptimisticLockException
     */
    private function createUser2(array $roles): User
    {
        $passwordHasher = static::getContainer()->get('security.password_hasher');
        $user = new User();
        $user->setEmail('user2@example.com');
        $user->setRoles($roles);
        $user->setPassword(
            $passwordHasher->hashPassword(
                $user,
                'p@55w0rd'
            )
        );
        $userService = static::getContainer()->get(UserService::class);
        $userService->save($user);

        return $user;
    }

    /**
     * Create category.
     *
     * @return Category category
     */
    private function createCategory(): Category
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

    /**
     * Create task.
     *
     * @param Category $category Category
     * @param User     $user     User
     *
     * @return Task task
     */
    private function createTask($category, $user): Task
    {
        $task = new Task();
        $task->setTitle('Title');
        $task->setUpdatedAt(new \DateTimeImmutable());
        $task->setCreatedAt(new \DateTimeImmutable());
        $task->setCategory($category);
        $task->setAuthor($user);
        $taskService = self::getContainer()->get(TaskService::class);
        $taskService->save($task);

        return $task;
    }
}
