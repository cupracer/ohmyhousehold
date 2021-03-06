<?php

namespace App\Controller\Supplies;

use App\Entity\Supplies\Brand;
use App\Entity\Supplies\DTO\BrandDTO;
use App\Form\Supplies\BrandType;
use App\Repository\HouseholdRepository;
use App\Service\Supplies\BrandService;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use function Symfony\Component\Translation\t;

#[IsGranted("ROLE_SUPPLIES")]
#[Route('/{_locale<%app.supported_locales%>}/supplies/components/brand')]
class BrandController extends AbstractController
{
    private ManagerRegistry $managerRegistry;
    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack, ManagerRegistry $managerRegistry)
    {
        $this->requestStack = $requestStack;
        $this->managerRegistry = $managerRegistry;
    }

    #[Route('/', name: 'supplies_brand_index', methods: ['GET'])]
    public function index(HouseholdRepository $householdRepository): Response
    {
        $currentHousehold = $householdRepository->find($this->requestStack->getSession()->get('current_household'));

        return $this->render('supplies/brand/index.html.twig', [
            'pageTitle' => t('Brands'),
            'household' => $currentHousehold,
        ]);
    }

    #[Route('/datatables', name: 'supplies_brand_datatables', methods: ['GET'])]
    public function getAsDatatables(Request $request, BrandService $brandService, HouseholdRepository $householdRepository): Response
    {
        $currentHousehold = $householdRepository->find($this->requestStack->getSession()->get('current_household'));

        return $this->json(
            $brandService->getBrandsAsDatatablesArray($request, $currentHousehold)
        );
    }

    #[Route('/select2', name: 'supplies_brand_select2', methods: ['GET'])]
    public function getAsSelect2(Request $request, BrandService $brandService, HouseholdRepository $householdRepository): Response
    {
        $currentHousehold = $householdRepository->find($this->requestStack->getSession()->get('current_household'));

        return $this->json(
            $brandService->getBrandsAsSelect2Array($request, $currentHousehold)
        );
    }

    #[Route('/new', name: 'supplies_brand_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        HouseholdRepository $householdRepository
    ): Response
    {
        $household = null;

        if($this->requestStack->getSession()->has('current_household')) {
            $household = $householdRepository->find($this->requestStack->getSession()->get('current_household'));
        }

        $this->denyAccessUnlessGranted('createSuppliesBrand', $household);

        $brand = new Brand();
        $createBrand = new BrandDTO();

        $form = $this->createForm(BrandType::class, $createBrand);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $brand->setName($createBrand->getName());
                $brand->setHousehold($household);

                $entityManager = $this->managerRegistry->getManager();
                $entityManager->persist($brand);
                $entityManager->flush();

                $this->addFlash('success', t('Brand was added.'));

                return $this->redirectToRoute('supplies_brand_new');
            }catch (Exception) {
                $this->addFlash('error', t('Brand could not be created.'));
            }
        }

        return $this->render('supplies/brand/form.html.twig', [
            'pageTitle' => t('Add brand'),
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'supplies_brand_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Brand $brand): Response
    {
        $this->denyAccessUnlessGranted('edit', $brand);

        $editBrand = new BrandDTO();
        $editBrand->setName($brand->getName());

        $form = $this->createForm(BrandType::class, $editBrand, ['brand' => $brand]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $brand->setName($editBrand->getName());

                $this->managerRegistry->getManager()->flush();

                $this->addFlash('success', t('Brand was updated.'));

                return $this->redirectToRoute('supplies_brand_index');
            }catch(Exception) {
                $this->addFlash('error', t('Brand could not be updated.'));
            }
        }

        return $this->render('supplies/brand/form.html.twig', [
            'pageTitle' => t('Edit brand'),
            'form' => $form->createView(),
            'brand' => $brand,
            'button_label' => t('Update'),
        ]);
    }

    #[Route('/{id}', name: 'supplies_brand_delete', methods: ['POST'])]
    public function delete(Request $request, Brand $brand): Response
    {
        try {
            if ($this->isCsrfTokenValid('delete_brand_' . $brand->getId(), $request->request->get('_token'))) {
                $this->denyAccessUnlessGranted('delete' , $brand);
                $entityManager = $this->managerRegistry->getManager();
                $entityManager->remove($brand);
                $entityManager->flush();
                $this->addFlash('success', t('Brand was deleted.'));
            }else {
                throw new Exception('invalid CSRF token');
            }
        }catch (Exception) {
            $this->addFlash('error', t('Brand could not be deleted.'));
        }

        return $this->redirectToRoute('supplies_brand_index');
    }
}
