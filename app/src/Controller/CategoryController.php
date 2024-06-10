<?php
/**
 * Category controller.
 */

namespace App\Controller;

use App\Entity\Category;
use App\Form\Type\CategoryType;
use App\Service\CategoryServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class CategoryController.
 */
#[Route('/category')]
class CategoryController extends AbstractController
{
    private $categoryRepository;

    /**
     * Constructor.
     *
     * @param CategoryServiceInterface $taskService Task service
     * @param TranslatorInterface      $translator  Translator
     */
    public function __construct(private readonly CategoryServiceInterface $taskService, private readonly TranslatorInterface $translator)
    {
        $this->categoryService = $taskService;
    }

    /**
     * Index action.
     *
     * @param int $page Page number
     *
     * @return Response HTTP response
     */
    #[Route(name: 'category_index', methods: 'GET')]
    public function index(#[MapQueryParameter] int $page = 1): Response
    {
        $pagination = $this->categoryService->getPaginatedList($page);

        return $this->render('category/index.html.twig', ['pagination' => $pagination]);
    }

    //    /**
    //     * Find category by id.
    //     *
    //     * @param int $id
    //     * @return Category
    //     */
    //    public function findOneById(int $id): Category | Null
    //    {
    //        if($category = $this->categoryRepository->findOneById($id))
    //        {
    //            return $category;
    //        }
    //        else
    //        {
    //            return Null;
    //        }
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
        name: 'category_show',
        requirements: ['id' => '[1-9]\d*'],
        methods: 'GET'
    )]
    public function show(int $id): Response
    {
        $category = $this->categoryService->findOneById($id);
        if (!$category) {
            throw $this->createNotFoundException();
        }

        return $this->render('category/show.html.twig', ['category' => $category]);
    }

    /**
     * Create action.
     *
     * @param Request $request HTTP request
     *
     * @return Response HTTP response
     */
    #[Route(
        '/create',
        name: 'category_create',
        methods: 'GET|POST',
    )]
    public function create(Request $request): Response
    {
        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->categoryService->save($category);

            $this->addFlash(
                'success',
                $this->translator->trans('message.created_successfully')
            );

            return $this->redirectToRoute('category_index');
        }

        return $this->render(
            'category/create.html.twig',
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
    #[Route('/{id}/edit', name: 'category_edit', requirements: ['id' => '[1-9]\d*'], methods: 'GET|PUT')]
    public function edit(Request $request, $id): Response
    {
        if (!$this->categoryService->categoryExists($id)) {
            $this->addFlash(
                'warning',
                $this->translator->trans('message.category_not_found')
            );

            return $this->redirectToRoute('category_index');
        }
        $category = $this->categoryService->findOneById($id);
        $form = $this->createForm(
            CategoryType::class,
            $category,
            [
                'method' => 'PUT',
                'action' => $this->generateUrl('category_edit', ['id' => $category->getId()]),
            ]
        );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->categoryService->save($category);

            $this->addFlash(
                'success',
                $this->translator->trans('message.created_successfully')
            );

            return $this->redirectToRoute('category_index');
        }

        return $this->render(
            'category/edit.html.twig',
            [
                'form' => $form->createView(),
                'category' => $category,
            ]
        );
    }

    /**
     * Delete action.
     *
     * @param Request $request HTTP request
     * @param int     $id      Id
     *
     * @return Response HTTP response
     */
    #[Route('/{id}/delete', name: 'category_delete', requirements: ['id' => '[1-9]\d*'], methods: 'GET|DELETE')]
    public function delete(Request $request, $id): Response
    {
        if (!$this->categoryService->categoryExists($id)) {
            $this->addFlash(
                'warning',
                $this->translator->trans('message.category_not_found')
            );

            return $this->redirectToRoute('category_index');
        }
        $category = $this->categoryService->findOneById($id);
        if (!$this->categoryService->canBeDeleted($id)) {
            $this->addFlash(
                'warning',
                $this->translator->trans('message.category_contains_tasks_or_notes')
            );

            return $this->redirectToRoute('category_index');
        }

        $form = $this->createForm(
            FormType::class,
            $category,
            [
                'method' => 'DELETE',
                'action' => $this->generateUrl('category_delete', ['id' => $category->getId()]),
            ]
        );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->categoryService->delete($category);

            $this->addFlash(
                'success',
                $this->translator->trans('message.deleted_successfully')
            );

            return $this->redirectToRoute('category_index');
        }

        return $this->render(
            'category/delete.html.twig',
            [
                'form' => $form->createView(),
                'category' => $category,
            ]
        );
    }
}
