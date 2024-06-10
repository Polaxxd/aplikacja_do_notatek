<?php
/**
 * User controller.
 */

namespace App\Controller;

use App\Entity\User;
use App\Form\Type\UserType;
use App\Service\UserServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class UserController.
 */
#[Route('/user')]
class UserController extends AbstractController
{
    /**
     * Constructor.
     *
     * @param UserServiceInterface $userService User service
     * @param TranslatorInterface  $translator  Translator
     */
    public function __construct(private readonly UserServiceInterface $userService, private readonly TranslatorInterface $translator)
    {
    }

    /**
     * Index action.
     *
     * @param int $page Page number
     *
     * @return Response HTTP response
     */
    #[Route(name: 'user_index', methods: 'GET')]
    public function index(#[MapQueryParameter] int $page = 1): Response
    {
        $loggedUser = $this->getUser();
        if ($this->canList($loggedUser)) {
            $pagination = $this->userService->getPaginatedList(
                $page,
                $this->getUser()
            );

            return $this->render('user/index.html.twig', ['pagination' => $pagination]);
        }

        return $this->redirectToRoute('note_index');
    }

    /**
     * Show action.
     *
     * @param int $id Id
     *
     * @return Response HTTP response
     */
    #[Route(
        '/{id}',
        name: 'user_show',
        requirements: ['id' => '[1-9]\d*'],
        methods: 'GET'
    )]
    public function show($id): Response
    {
        $loggedUser = $this->getUser();
        if ($this->canList($loggedUser)) {
            $user = $this->userService->findOneById($id);
            if ($user) {
                return $this->render(
                    'user/show.html.twig',
                    ['user' => $user]
                );
            }

            return $this->redirectToRoute('user_index');
        }

        return $this->redirectToRoute('note_index');
    }

    /**
     * Create action.
     *
     * @param Request $request HTTP request
     *
     * @return Response HTTP response
     */
    #[Route('/create', name: 'user_create', methods: 'GET|POST')]
    public function create(Request $request): Response
    {
        $loggedUser = $this->getUser();
        if ($this->canList($loggedUser)) {
            $user = new User();
            $form = $this->createForm(
                UserType::class,
                $user,
                ['action' => $this->generateUrl('user_create')]
            );
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $plainPassword = $form->get('password')->getData();

                $this->userService->registerUser($user, $plainPassword);

                $this->addFlash(
                    'success',
                    $this->translator->trans('message.created_successfully')
                );

                return $this->redirectToRoute('user_index');
            }

            return $this->render(
                'user/create.html.twig',
                ['form' => $form->createView()]
            );
        }

        return $this->redirectToRoute('note_index');
    }

    /**
     * Delete action.
     *
     * @param Request $request HTTP request
     * @param User    $user    User entity
     *
     * @return Response HTTP response
     */
    #[Route('/{id}/delete', name: 'user_delete', requirements: ['id' => '[1-9]\d*'], methods: 'GET|DELETE')]
    public function delete(Request $request, User $user): Response
    {
        $loggedUser = $this->getUser();
        if ($this->canList($loggedUser)) {
            $form = $this->createForm(
                FormType::class,
                $user,
                [
                    'method' => 'DELETE',
                    'action' => $this->generateUrl('user_delete', ['id' => $user->getId()]),
                ]
            );
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $this->userService->delete($user);

                $this->addFlash(
                    'success',
                    $this->translator->trans('message.deleted_successfully')
                );

                return $this->redirectToRoute('user_index');
            }

            return $this->render(
                'user/delete.html.twig',
                [
                    'form' => $form->createView(),
                    'user' => $user,
                ]
            );
        }

        return $this->redirectToRoute('note_index');
    }

    /**
     * Checks if viever can list users.
     *
     * @param UserInterface $user User
     *
     * @return bool Result
     */
    private function canList(UserInterface $user): bool
    {
        return in_array('ROLE_ADMIN', $user->getRoles());
    }
}
