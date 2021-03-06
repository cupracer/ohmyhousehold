<?php

namespace App\Controller;

use App\Entity\BookingCategory;
use App\Entity\DTO\BookingCategoryDTO;
use App\Form\BookingCategoryType;
use App\Repository\HouseholdRepository;
use App\Service\BookingCategoryService;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use function Symfony\Component\Translation\t;

#[IsGranted("ROLE_HOUSEKEEPINGBOOK")]
#[Route('/{_locale<%app.supported_locales%>}/housekeepingbook/bookingcategory')]
class BookingCategoryController extends AbstractController
{
    private ManagerRegistry $managerRegistry;

    /**
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    #[Route('/', name: 'housekeepingbook_bookingcategory_index', methods: ['GET'])]
    public function index(HouseholdRepository $householdRepository, SessionInterface $session): Response
    {
        $currentHousehold = $householdRepository->find($session->get('current_household'));

        return $this->render('housekeepingbook/bookingcategory/index.html.twig', [
            'pageTitle' => t('Booking categories'),
            'household' => $currentHousehold,
        ]);
    }

    #[Route('/datatables', name: 'housekeepingbook_bookingcategory_datatables', methods: ['GET'])]
    public function getAsDatatables(Request $request, BookingCategoryService $bookingCategoryService, HouseholdRepository $householdRepository, SessionInterface $session): Response
    {
        $currentHousehold = $householdRepository->find($session->get('current_household'));

        return $this->json(
            $bookingCategoryService->getBookingCategoriesAsDatatablesArray($request, $currentHousehold)
        );
    }

    #[Route('/select2', name: 'housekeepingbook_bookingcategory_select2', methods: ['GET'])]
    public function getAsSelect2(Request $request, BookingCategoryService $bookingCategoryService, HouseholdRepository $householdRepository, SessionInterface $session): Response
    {
        $currentHousehold = $householdRepository->find($session->get('current_household'));

        return $this->json(
            $bookingCategoryService->getBookingCategoriesAsSelect2Array($request, $currentHousehold)
        );
    }

    #[Route('/new', name: 'housekeepingbook_bookingcategory_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        SessionInterface $session,
        HouseholdRepository $householdRepository
    ): Response
    {
        $household = null;

        if($session->has('current_household')) {
            $household = $householdRepository->find($session->get('current_household'));
        }

        $this->denyAccessUnlessGranted('createBookingCategory', $household);

        $bookingCategory = new BookingCategory();
        $createBookingCategory = new BookingCategoryDTO();

        $form = $this->createForm(BookingCategoryType::class, $createBookingCategory);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $bookingCategory->setName($createBookingCategory->getName());
                $bookingCategory->setHousehold($household);

                $entityManager = $this->managerRegistry->getManager();
                $entityManager->persist($bookingCategory);
                $entityManager->flush();

                $this->addFlash('success', t('Booking category was added.'));

                return $this->redirectToRoute('housekeepingbook_bookingcategory_index');
            }catch(Exception) {
                $this->addFlash('error', t('Booking category could not be created.'));
            }
        }

        return $this->render('housekeepingbook/bookingcategory/form.html.twig', [
            'pageTitle' => t('Add booking category'),
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'housekeepingbook_bookingcategory_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, BookingCategory $bookingCategory): Response
    {
        $this->denyAccessUnlessGranted('edit', $bookingCategory);

        $editBookingCategory = new BookingCategoryDTO();
        $editBookingCategory->setName($bookingCategory->getName());

        $form = $this->createForm(BookingCategoryType::class, $editBookingCategory, ['bookingCategory' => $bookingCategory]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $bookingCategory->setName($editBookingCategory->getName());

                $this->managerRegistry->getManager()->flush();

                $this->addFlash('success', t('Booking category was updated.'));

                return $this->redirectToRoute('housekeepingbook_bookingcategory_index');
            }catch(Exception) {
                $this->addFlash('error', t('Booking category could not be updated.'));
            }
        }

        return $this->render('housekeepingbook/bookingcategory/form.html.twig', [
            'pageTitle' => t('Edit booking category'),
            'form' => $form->createView(),
            'bookingCategory' => $bookingCategory,
            'button_label' => t('Update'),
        ]);
    }

    #[Route('/{id}', name: 'housekeepingbook_bookingcategory_delete', methods: ['POST'])]
    public function delete(Request $request, BookingCategory $bookingCategory): Response
    {
        try {
            if ($this->isCsrfTokenValid('delete_booking_category_' . $bookingCategory->getId(), $request->request->get('_token'))) {
                $this->denyAccessUnlessGranted('delete', $bookingCategory);
                $entityManager = $this->managerRegistry->getManager();
                $entityManager->remove($bookingCategory);
                $entityManager->flush();
                $this->addFlash('success', t('Booking category was deleted.'));
            } else {
                throw new Exception('invalid CSRF token');
            }
        }catch (Exception) {
            $this->addFlash('error', t('Booking category could not be deleted.'));
        }

        return $this->redirectToRoute('housekeepingbook_bookingcategory_index');
    }
}
