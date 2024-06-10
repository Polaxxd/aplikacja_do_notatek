<?php
/**
 * Note controller.
 */

namespace App\Controller;

use App\Entity\Note;
use App\Entity\User;
use App\Form\Type\NoteType;
use App\Service\NoteServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class NoteController.
 */
#[Route('/note')]
class NoteController extends AbstractController
{
    /**
     * Note service.
     */
    private NoteServiceInterface $noteService;

    /**
     * Translator.
     */
    private TranslatorInterface $translator;

    /**
     * Constructor.
     *
     * @param NoteServiceInterface $noteService Note service
     * @param TranslatorInterface  $translator  Translator
     */
    public function __construct(NoteServiceInterface $noteService, TranslatorInterface $translator)
    {
        $this->noteService = $noteService;
        $this->translator = $translator;
    }

    /**
     * Index action.
     *
     * @param int $page Page number
     *
     * @return Response HTTP response
     */
    #[Route(name: 'note_index', methods: 'GET')]
    public function index(#[MapQueryParameter] int $page = 1): Response
    {
        $pagination = $this->noteService->getPaginatedList(
            $page,
            $this->getUser()
        );

        return $this->render('note/index.html.twig', ['pagination' => $pagination]);
    }

    /**
     * Show action.
     *
     * @param int $id Id
     *
     * @return Response HTTP response
     */
    #[Route('/{id}', name: 'note_show', requirements: ['id' => '[1-9]\d*'], methods: 'GET')]
    public function show($id): Response
    {
        $note = $this->noteService->findOneById($id);
        if ($note) {
            $user = $this->getUser();
            if ($this->canView($note, $user)) {
                return $this->render('note/show.html.twig', ['note' => $note]);
            }
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
    #[Route('/create', name: 'note_create', methods: 'GET|POST')]
    public function create(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $note = new Note();
        $note->setAuthor($user);
        $form = $this->createForm(
            NoteType::class,
            $note,
            ['action' => $this->generateUrl('note_create')]
        );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->noteService->save($note);

            $this->addFlash(
                'success',
                $this->translator->trans('message.created_successfully')
            );

            return $this->redirectToRoute('note_index');
        }

        return $this->render('note/create.html.twig', ['form' => $form->createView()]);
    }

    /**
     * Edit action.
     *
     * @param Request $request HTTP request
     * @param int     $id      Id
     *
     * @return Response HTTP response
     */
    #[Route('/{id}/edit', name: 'note_edit', requirements: ['id' => '[1-9]\d*'], methods: 'GET|PUT')]
    public function edit(Request $request, $id): Response
    {
        $note = $this->noteService->findOneById($id);
        if ($note) {
            $user = $this->getUser();
            if ($this->canEdit($note, $user)) {
                $form = $this->createForm(
                    NoteType::class,
                    $note,
                    [
                        'method' => 'PUT',
                        'action' => $this->generateUrl('note_edit', ['id' => $note->getId()]),
                    ]
                );
                $form->handleRequest($request);

                if ($form->isSubmitted() && $form->isValid()) {
                    $this->noteService->save($note);

                    $this->addFlash(
                        'success',
                        $this->translator->trans('message.edited_successfully')
                    );

                    return $this->redirectToRoute('note_index');
                }

                return $this->render(
                    'note/edit.html.twig',
                    [
                        'form' => $form->createView(),
                        'note' => $note,
                    ]
                );
            }
        }

        return $this->redirectToRoute('note_index');
    }

    /**
     * Delete action.
     *
     * @param Request $request HTTP request
     * @param int     $id      Id
     *
     * @return Response HTTP response
     */
    #[Route('/{id}/delete', name: 'note_delete', requirements: ['id' => '[1-9]\d*'], methods: 'GET|DELETE')]
    public function delete(Request $request, $id): Response
    {
        $note = $this->noteService->findOneById($id);
        if ($note) {
            $user = $this->getUser();
            if ($this->canDelete($note, $user)) {
                $form = $this->createForm(
                    FormType::class,
                    $note,
                    [
                        'method' => 'DELETE',
                        'action' => $this->generateUrl('note_delete', ['id' => $note->getId()]),
                    ]
                );
                $form->handleRequest($request);

                if ($form->isSubmitted() && $form->isValid()) {
                    $this->noteService->delete($note);

                    $this->addFlash(
                        'success',
                        $this->translator->trans('message.deleted_successfully')
                    );

                    return $this->redirectToRoute('note_index');
                }

                return $this->render(
                    'note/delete.html.twig',
                    [
                        'form' => $form->createView(),
                        'note' => $note,
                    ]
                );
            }
        }

        return $this->redirectToRoute('note_index');
    }

    /**
     * Checks if user can edit note.
     *
     * @param Note          $note Note entity
     * @param UserInterface $user User
     *
     * @return bool Result
     */
    private function canEdit(Note $note, UserInterface $user): bool
    {
        return $note->getAuthor() === $user;
    }

    /**
     * Checks if user can view note.
     *
     * @param Note          $note Note entity
     * @param UserInterface $user User
     *
     * @return bool Result
     */
    private function canView(Note $note, UserInterface $user): bool
    {
        return $note->getAuthor() === $user;
    }

    /**
     * Checks if user can delete note.
     *
     * @param Note          $note Note entity
     * @param UserInterface $user User
     *
     * @return bool Result
     */
    private function canDelete(Note $note, UserInterface $user): bool
    {
        return $note->getAuthor() === $user;
    }
    //    /**
    //     * Get filters from request.
    //     *
    //     * @param Request $request HTTP request
    //     *
    //     * @return array<string, int> Array of filters
    //     *
    //     * @psalm-return array{category_id: int, tag_id: int, status_id: int}
    //     */
    //    private function getFilters(Request $request): array
    //    {
    //        $filters = [];
    //        $filters['category_id'] = $request->query->getInt('filters_category_id');
    //
    //        return $filters;
    //    }
}
