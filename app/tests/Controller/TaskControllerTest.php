<?php
/**
 * Task Controller test.
 */

namespace App\Tests\Controller;

use App\Entity\Category;
use App\Entity\Task;
use App\Entity\Enum\UserRole;
use App\Entity\User;
use App\Repository\CategoryRepository;
use App\Repository\TaskRepository;
use App\Repository\UserRepository;
use App\Service\TaskServiceInterface;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class TaskControllerTest.
 */
class TaskControllerTest extends WebTestCase
{
    /**
     * Test route.
     *
     * @const string
     */
    public const TEST_ROUTE = '/task';

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
     * Test show single task.
     */
    public function testShowTaskForNonExistentTask(): void
    {
        // given
        $expectedStatusCode = 302;
        $testTaskId = 1230;
        $adminUser = $this->createUser([UserRole::ROLE_ADMIN->value, UserRole::ROLE_USER->value]);
        $this->httpClient->loginUser($adminUser);

        // when
        $this->httpClient->request('GET', self::TEST_ROUTE.'/'.$testTaskId);
        $actualStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $actualStatusCode);
    }

    /**
     * Test show single task.
     */
    public function testShowTaskWithMock(): void
    {
        // given
        $expectedStatusCode = 200;
        $testCategoryId = 123;
        $expectedCategory = new Category();
        $categoryIdProperty = new \ReflectionProperty(Category::class, 'id');
        $categoryIdProperty->setValue($expectedCategory, $testCategoryId);
        $expectedCategory->setTitle('Test category');
        $testTaskId = 122;
        $expectedTask = new Task();
        $taskIdProperty = new \ReflectionProperty(Task::class, 'id');
        $taskIdProperty->setValue($expectedTask, $testTaskId);
        $expectedTask->setTitle('Test task');
        $expectedTask->setCreatedAt(new \DateTimeImmutable());
        $expectedTask->setUpdatedAt(new \DateTimeImmutable());
        $expectedTask->setCategory($this->createCategory());
        $taskService = $this->createMock(TaskServiceInterface::class);
        $taskService->expects($this->once())
            ->method('findOneById')
            ->with($testTaskId)
            ->willReturn($expectedTask);
        static::getContainer()->set(TaskServiceInterface::class, $taskService);
        $adminUser = $this->createUser([UserRole::ROLE_ADMIN->value, UserRole::ROLE_USER->value]);
        $expectedTask->setAuthor($adminUser);
        $this->httpClient->loginUser($adminUser);
        // when
        $this->httpClient->request('GET', self::TEST_ROUTE.'/'.$expectedTask->getId());
        $actualStatusCode = $this->httpClient->getResponse()->getStatusCode();
        // then
        $this->assertEquals($expectedStatusCode, $actualStatusCode);
    }

    /**
     * Test create task.
     */
    public function testCreateTask(): void
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
     * Test create and save task.
     */
    public function testCreateSaveTask(): void
    {
        // given
        $expectedStatusCode = 302;
        $adminUser = $this->createUser([UserRole::ROLE_ADMIN->value, UserRole::ROLE_USER->value]);
        $this->httpClient->loginUser($adminUser);
        $createdTaskTitle = 'newCreatedTask';
        $createdCategory = $this->createCategory();

        // when
        $route = self::TEST_ROUTE.'/create';
        $this->httpClient->request('GET', $route);
        $this->httpClient->submitForm(
            'Zapisz',
            ['task' => ['title' => $createdTaskTitle, 'category' => $createdCategory->getId()]]
        );

        // then
        $actualStatusCode = $this->httpClient->getResponse()->getStatusCode();
        $this->assertEquals($expectedStatusCode, $actualStatusCode);
    }

    /**
     * Test edit task.
     */
    public function testEditTaskWithMock(): void
    {
        // given
        $expectedStatusCode = 200;
        $testCategoryId = 123;
        $expectedCategory = new Category();
        $categoryIdProperty = new \ReflectionProperty(Category::class, 'id');
        $categoryIdProperty->setValue($expectedCategory, $testCategoryId);
        $expectedCategory->setTitle('Test category');
        $testTaskId = 122;
        $expectedTask = new Task();
        $taskIdProperty = new \ReflectionProperty(Task::class, 'id');
        $taskIdProperty->setValue($expectedTask, $testTaskId);
        $expectedTask->setTitle('Test task');
        $expectedTask->setCreatedAt(new \DateTimeImmutable());
        $expectedTask->setUpdatedAt(new \DateTimeImmutable());
        $expectedTask->setCategory($this->createCategory());
        $taskService = $this->createMock(TaskServiceInterface::class);
        $taskService->expects($this->once())
            ->method('findOneById')
            ->with($testTaskId)
            ->willReturn($expectedTask);
        static::getContainer()->set(TaskServiceInterface::class, $taskService);
        $adminUser = $this->createUser([UserRole::ROLE_ADMIN->value, UserRole::ROLE_USER->value]);
        $expectedTask->setAuthor($adminUser);
        $this->httpClient->loginUser($adminUser);
        // when
        $route = $route = self::TEST_ROUTE.'/'.$expectedTask->getId().'/edit';
        $this->httpClient->request('GET', $route);
        $actualStatusCode = $this->httpClient->getResponse()->getStatusCode();
        // then
        $this->assertEquals($expectedStatusCode, $actualStatusCode);
    }

    /**
     * Test edit non-existent task.
     */
    public function testEditNonExistentTask(): void
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
     * Test edit and save task.
     */
    public function testEditTaskForm(): void
    {
        // given
        $expectedStatusCode = 302;
        $adminUser = $this->createUser([UserRole::ROLE_ADMIN->value, UserRole::ROLE_USER->value]);
        $this->httpClient->loginUser($adminUser);
        $createdTaskNewTitle = 'newCreatedTask';
        $createdCategory = $this->createCategory();
        $createdTask = $this->createTask($createdCategory, $adminUser);
        // when
        $route = $route = self::TEST_ROUTE.'/'.$createdTask->getId().'/edit';
        //        echo $route;
        $this->httpClient->request('GET', $route);
        $this->httpClient->submitForm(
            'Edytuj',
            ['task' => [
                'title' => $createdTaskNewTitle,
                'category' => $createdCategory->getId(),
            ],
            ]
        );

        // then
        $actualStatusCode = $this->httpClient->getResponse()->getStatusCode();
        $this->assertEquals($expectedStatusCode, $actualStatusCode);
    }

    /**
     * Test delete task.
     */
    public function testDeleteTaskWithMock(): void
    {
        // given
        $expectedStatusCode = 200;
        $testCategoryId = 123;
        $expectedCategory = new Category();
        $categoryIdProperty = new \ReflectionProperty(Category::class, 'id');
        $categoryIdProperty->setValue($expectedCategory, $testCategoryId);
        $expectedCategory->setTitle('Test category');
        $testTaskId = 122;
        $expectedTask = new Task();
        $taskIdProperty = new \ReflectionProperty(Task::class, 'id');
        $taskIdProperty->setValue($expectedTask, $testTaskId);
        $expectedTask->setTitle('Test task');
        $expectedTask->setCreatedAt(new \DateTimeImmutable());
        $expectedTask->setUpdatedAt(new \DateTimeImmutable());
        $expectedTask->setCategory($this->createCategory());
        $taskService = $this->createMock(TaskServiceInterface::class);
        $taskService->expects($this->once())
            ->method('findOneById')
            ->with($testTaskId)
            ->willReturn($expectedTask);
        static::getContainer()->set(TaskServiceInterface::class, $taskService);
        $adminUser = $this->createUser([UserRole::ROLE_ADMIN->value, UserRole::ROLE_USER->value]);
        $expectedTask->setAuthor($adminUser);
        $this->httpClient->loginUser($adminUser);
        // when
        $route = $route = self::TEST_ROUTE.'/'.$expectedTask->getId().'/delete';
        $this->httpClient->request('GET', $route);
        $actualStatusCode = $this->httpClient->getResponse()->getStatusCode();
        // then
        $this->assertEquals($expectedStatusCode, $actualStatusCode);
    }

    /**
     * Test delete non-existent task.
     */
    public function testDeleteNonExistentTask(): void
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
     * Test delete task.
     */
    public function testDeleteTaskForm(): void
    {
        // given
        $expectedStatusCode = 302;
        $adminUser = $this->createUser([UserRole::ROLE_ADMIN->value, UserRole::ROLE_USER->value]);
        $this->httpClient->loginUser($adminUser);
        $expectedCategory = $this->createCategory();
        $expectedTask = $this->createTask($expectedCategory, $adminUser);
        $route = self::TEST_ROUTE.'/'.$expectedTask->getId().'/delete';

        // when
        $this->httpClient->request('GET', $route);
        $this->httpClient->submitForm('Usuń');

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
        $category->setTitle('Test Category');

        $categoryRepository = static::getContainer()->get(CategoryRepository::class);
        $categoryRepository->save($category);

        return $category;
    }

    /**
     * Create task.
     *
     * @param Category $category Category entity
     * @param User     $user     User entity
     *
     * @return Task Task entity
     */
    protected function createTask(Category $category, User $user): Task
    {
        $task = new Task();
        $task->setTitle('Test Task');
        $task->setCreatedAt(new \DateTimeImmutable());
        $task->setUpdatedAt(new \DateTimeImmutable());
        $task->setCategory($category);
        $task->setAuthor($user);

        $taskRepository = static::getContainer()->get(TaskRepository::class);
        $taskRepository->save($task);

        return $task;
    }

    /**
     * Create user.
     *
     * @param array $roles User roles
     *
     * @return User User entity
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    protected function createUser(array $roles): User
    {
        $user = new User();
        $user->setEmail(uniqid().'user@example.com');
        $user->setRoles($roles);
        $user->setPassword(
            static::getContainer()->get('security.user_password_hasher')->hashPassword($user, 'password123')
        );

        $userRepository = static::getContainer()->get(UserRepository::class);
        $userRepository->save($user);

        return $user;
    }
}
