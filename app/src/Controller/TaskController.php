<?php
/**
 * Task controller.
 */

namespace App\Controller;

use App\Entity\Task;
use App\Entity\User;
use App\Form\Type\TaskType;
use App\Service\TaskServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class TaskController.
 */
#[Route('/task')]
class TaskController extends AbstractController
{
    private $taskRepository;

    /**
     * Constructor.
     *
     * @param TaskServiceInterface $taskService Task service
     * @param TranslatorInterface  $translator  Translator
     */
    public function __construct(private readonly TaskServiceInterface $taskService, private readonly TranslatorInterface $translator)
    {
    }

    /**
     * Index action.
     *
     * @param int $page Page number
     *
     * @return Response HTTP response
     */
    #[Route(name: 'task_index', methods: 'GET')]
    public function index(#[MapQueryParameter] int $page = 1): Response
    {
        $pagination = $this->taskService->getPaginatedList(
            $page,
            $this->getUser()
        );

        return $this->render('task/index.html.twig', ['pagination' => $pagination]);
    }

    //    /**
    //     * Find task by id
    //     * @param int $id
    //     * @return Task|null
    //     */
    //    public function findOneById(int $id): ?Task
    //    {
    //        return $this -> taskRepository->findOneById($id);
    //    }

    /**
     * Show action.
     *
     * @param int $id Id
     *
     * @return Response HTTP response
     */
    #[Route(
        '/{id}',
        name: 'task_show',
        requirements: ['id' => '[1-9]\d*'],
        methods: 'GET'
    )]
    public function show($id): Response
    {
        $task = $this->taskService->findOneById($id);
        if ($task) {
            $user = $this->getUser();
            if ($this->canView($task, $user)) {
                return $this->render(
                    'task/show.html.twig',
                    ['task' => $task]
                );
            }
        }
        $this->addFlash(
            'warning',
            $this->translator->trans('message.task_not_found')
        );

        return $this->redirectToRoute('task_index');
    }

    /**
     * Create action.
     *
     * @param Request $request HTTP request
     *
     * @return Response HTTP response
     */
    #[Route('/create', name: 'task_create', methods: 'GET|POST')]
    public function create(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $task = new Task();
        $task->setAuthor($user);
        $form = $this->createForm(
            TaskType::class,
            $task,
            ['action' => $this->generateUrl('task_create')]
        );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->taskService->save($task);

            $this->addFlash(
                'success',
                $this->translator->trans('message.created_successfully')
            );

            return $this->redirectToRoute('task_index');
        }

        return $this->render(
            'task/create.html.twig',
            ['form' => $form->createView()]
        );
    }

    /**
     * Edit action.
     *
     * @param Request $request HTTP request
     * @param int     $id      Id
     *
     * @return Response HTTP response
     */
    #[Route('/{id}/edit', name: 'task_edit', requirements: ['id' => '[1-9]\d*'], methods: 'GET|PUT')]
    public function edit(Request $request, $id): Response
    {
        $task = $this->taskService->findOneById($id);
        if ($task) {
            $user = $this->getUser();
            if ($this->canEdit($task, $user)) {
                $form = $this->createForm(
                    TaskType::class,
                    $task,
                    [
                        'method' => 'PUT',
                        'action' => $this->generateUrl('task_edit', ['id' => $task->getId()]),
                    ]
                );
                $form->handleRequest($request);

                if ($form->isSubmitted() && $form->isValid()) {
                    $this->taskService->save($task);

                    $this->addFlash(
                        'success',
                        $this->translator->trans('message.edited_successfully')
                    );

                    return $this->redirectToRoute('task_index');
                }

                return $this->render(
                    'task/edit.html.twig',
                    [
                        'form' => $form->createView(),
                        'task' => $task,
                    ]
                );
            }
        }

        return $this->redirectToRoute('task_index');
    }

    /**
     * Delete action.
     *
     * @param Request $request HTTP request
     * @param int     $id      Id
     *
     * @return Response HTTP response
     */
    #[Route('/{id}/delete', name: 'task_delete', requirements: ['id' => '[1-9]\d*'], methods: 'GET|DELETE')]
    public function delete(Request $request, $id): Response
    {
        $task = $this->taskService->findOneById($id);
        if ($task) {
            $user = $this->getUser();
            if ($this->canDelete($task, $user)) {
                $form = $this->createForm(
                    FormType::class,
                    $task,
                    [
                        'method' => 'DELETE',
                        'action' => $this->generateUrl('task_delete', ['id' => $task->getId()]),
                    ]
                );
                $form->handleRequest($request);

                if ($form->isSubmitted() && $form->isValid()) {
                    $this->taskService->delete($task);

                    $this->addFlash(
                        'success',
                        $this->translator->trans('message.deleted_successfully')
                    );

                    return $this->redirectToRoute('task_index');
                }

                return $this->render(
                    'task/delete.html.twig',
                    [
                        'form' => $form->createView(),
                        'task' => $task,
                    ]
                );
            }
        }

        return $this->redirectToRoute('task_index');
    }

    /**
     * Checks if user can view task.
     *
     * @param Task          $task Task entity
     * @param UserInterface $user User
     *
     * @return bool Result
     */
    private function canView(Task $task, UserInterface $user): bool
    {
        return $task->getAuthor() === $user;
    }

    /**
     * Checks if user can edit task.
     *
     * @param Task          $task Task entity
     * @param UserInterface $user User
     *
     * @return bool Result
     */
    private function canEdit(Task $task, UserInterface $user): bool
    {
        return $task->getAuthor() === $user;
    }

    /**
     * Checks if user can delete task.
     *
     * @param Task          $task Task entity
     * @param UserInterface $user User
     *
     * @return bool Result
     */
    private function canDelete(Task $task, UserInterface $user): bool
    {
        return $task->getAuthor() === $user;
    }
}
