<?php

namespace App\Controller\Transaction;

use App\Entity\DTO\WithdrawalTransactionDTO;
use App\Entity\ExpenseAccount;
use App\Entity\WithdrawalTransaction;
use App\Form\Transaction\WithdrawalTransactionType;
use App\Repository\Account\ExpenseAccountRepository;
use App\Repository\HouseholdRepository;
use App\Repository\HouseholdUserRepository;
use App\Service\Transaction\WithdrawalTransactionService;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use function Symfony\Component\Translation\t;

#[IsGranted("ROLE_HOUSEKEEPINGBOOK")]
#[Route('/{_locale<%app.supported_locales%>}/housekeepingbook/transaction/withdrawal')]
class WithdrawalTransactionController extends AbstractController
{
    private ManagerRegistry $managerRegistry;
    private RequestStack $requestStack;
    private HouseholdRepository $householdRepository;
    private WithdrawalTransactionService $withdrawalTransactionService;

    public function __construct(HouseholdRepository          $householdRepository, RequestStack $requestStack,
                                WithdrawalTransactionService $withdrawalTransactionService, ManagerRegistry $managerRegistry)
    {
        $this->requestStack = $requestStack;
        $this->householdRepository = $householdRepository;
        $this->withdrawalTransactionService = $withdrawalTransactionService;
        $this->managerRegistry = $managerRegistry;
    }

    #[Route('/', name: 'housekeepingbook_withdrawal_transaction_index', methods: ['GET'])]
    public function index(): Response
    {
        $currentHousehold = $this->householdRepository->find($this->requestStack->getSession()->get('current_household'));

        return $this->render('housekeepingbook/transaction/withdrawal/index.html.twig', [
            'pageTitle' => t('withdrawals'),
            'household' => $currentHousehold,
        ]);
    }

    #[Route('/datatables', name: 'housekeepingbook_withdrawal_transaction_datatables', methods: ['GET'])]
    public function getWithdrawalTransactionsAsDatatables(Request $request): Response
    {
        $currentHousehold = $this->householdRepository->find($this->requestStack->getSession()->get('current_household'));

        return $this->json(
            $this->withdrawalTransactionService->getWithdrawalTransactionsAsDatatablesArray($request, $currentHousehold)
        );
    }

    #[Route('/new', name: 'housekeepingbook_withdrawal_transaction_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        HouseholdRepository $householdRepository,
        HouseholdUserRepository $householdUserRepository,
        ExpenseAccountRepository $expenseAccountRepository
    ): Response
    {
        $household = null;
        $householdUser = null;

        if($this->requestStack->getSession()->has('current_household')) {
            $household = $householdRepository->find($this->requestStack->getSession()->get('current_household'));
            $householdUser = $householdUserRepository->findOneByUserAndHousehold($this->getUser(), $household);
        }

        $this->denyAccessUnlessGranted('createWithdrawalTransaction', $household);

        $withdrawalTransaction = new WithdrawalTransaction();
        $createWithdrawalTransaction = new WithdrawalTransactionDTO();

        // set initial values
        $createWithdrawalTransaction->setBookingDate((new DateTime())->modify('midnight'));

        $form = $this->createForm(WithdrawalTransactionType::class, $createWithdrawalTransaction);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager = $this->managerRegistry->getManager();

                // Find or create the required expense account
                $expenseAccount = $expenseAccountRepository->findOneByHouseholdAndAccountHolder($household, $createWithdrawalTransaction->getDestination());

                if(!$expenseAccount) {
                    $expenseAccount = new ExpenseAccount();
                    $expenseAccount->setHousehold($household);
                    $expenseAccount->setInitialBalance(0);
                    $expenseAccount->setInitialBalanceDate((new DateTime())->modify('midnight'));
                    $expenseAccount->setAccountHolder($createWithdrawalTransaction->getDestination());

                    $entityManager->persist($expenseAccount);
                }

                // explicitly setting to "midnight" might not be necessary for a db date field
                $withdrawalTransaction->setBookingDate($createWithdrawalTransaction->getBookingDate()->modify('midnight'));
                $withdrawalTransaction->setBookingCategory($createWithdrawalTransaction->getBookingCategory());
                $withdrawalTransaction->setSource($createWithdrawalTransaction->getSource());
                $withdrawalTransaction->setDestination($expenseAccount);
                $withdrawalTransaction->setAmount($createWithdrawalTransaction->getAmount());
                $withdrawalTransaction->setDescription($createWithdrawalTransaction->getDescription());
                $withdrawalTransaction->setPrivate($createWithdrawalTransaction->getPrivate());
                $withdrawalTransaction->setBookingPeriodOffset($createWithdrawalTransaction->getBookingPeriodOffset());
                $withdrawalTransaction->setCompleted($createWithdrawalTransaction->isCompleted());

                // TODO: Do we need to explicitly check that these values are set and not null?
                $withdrawalTransaction->setHousehold($household);
                $withdrawalTransaction->setHouseholdUser($householdUser);

                $entityManager->persist($withdrawalTransaction);
                $entityManager->flush();

                $this->addFlash('success', t('Withdrawal transaction was added.'));

                return $this->redirectToRoute('housekeepingbook_withdrawal_transaction_new');
            } catch (Exception $exception) {
                $this->addFlash('error', t('Withdrawal transaction could not be created: ' . $exception->getMessage()));
            }
        }

        return $this->render('housekeepingbook/transaction/withdrawal/form.html.twig', [
            'pageTitle' => t('add_withdrawal_transaction'),
            'form' => $form->createView(),
        ]);
    }


    #[Route('/{id}/edit', name: 'housekeepingbook_withdrawal_transaction_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, WithdrawalTransaction $withdrawalTransaction, ExpenseAccountRepository $expenseAccountRepository): Response
    {
        $this->denyAccessUnlessGranted('edit', $withdrawalTransaction);

        $editWithdrawalTransaction = new WithdrawalTransactionDTO();
        $editWithdrawalTransaction->setBookingDate($withdrawalTransaction->getBookingDate());
        $editWithdrawalTransaction->setBookingCategory($withdrawalTransaction->getBookingCategory());
        $editWithdrawalTransaction->setSource($withdrawalTransaction->getSource());
        $editWithdrawalTransaction->setDestination($withdrawalTransaction->getDestination()->getAccountHolder());
        $editWithdrawalTransaction->setAmount($withdrawalTransaction->getAmount());
        $editWithdrawalTransaction->setDescription($withdrawalTransaction->getDescription());
        $editWithdrawalTransaction->setPrivate($withdrawalTransaction->getPrivate());
        $editWithdrawalTransaction->setBookingPeriodOffset($withdrawalTransaction->getBookingPeriodOffset());
        $editWithdrawalTransaction->setCompleted($withdrawalTransaction->isCompleted());

        $form = $this->createForm(WithdrawalTransactionType::class, $editWithdrawalTransaction);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager = $this->managerRegistry->getManager();

                // Find or create the required expense account
                $expenseAccount = $expenseAccountRepository->findOneByHouseholdAndAccountHolder($withdrawalTransaction->getHousehold(), $editWithdrawalTransaction->getDestination());

                if(!$expenseAccount) {
                    $expenseAccount = new ExpenseAccount();
                    $expenseAccount->setHousehold($withdrawalTransaction->getHousehold());
                    $expenseAccount->setInitialBalance(0);
                    $expenseAccount->setInitialBalanceDate((new DateTime())->modify('midnight'));
                    $expenseAccount->setAccountHolder($editWithdrawalTransaction->getDestination());

                    $entityManager->persist($expenseAccount);
                }

                $withdrawalTransaction->setBookingDate($editWithdrawalTransaction->getBookingDate());
                $withdrawalTransaction->setBookingCategory($editWithdrawalTransaction->getBookingCategory());
                $withdrawalTransaction->setSource($editWithdrawalTransaction->getSource());
                $withdrawalTransaction->setDestination($expenseAccount);
                $withdrawalTransaction->setAmount($editWithdrawalTransaction->getAmount());
                $withdrawalTransaction->setDescription($editWithdrawalTransaction->getDescription());
                $withdrawalTransaction->setPrivate($editWithdrawalTransaction->getPrivate());
                $withdrawalTransaction->setBookingPeriodOffset($editWithdrawalTransaction->getBookingPeriodOffset());
                $withdrawalTransaction->setCompleted($editWithdrawalTransaction->isCompleted());

                $entityManager->flush();
                $this->addFlash('success', t('Withdrawal transaction was updated.'));

                return $this->redirectToRoute('housekeepingbook_withdrawal_transaction_index');
            } catch (Exception) {
                $this->addFlash('error', t('Withdrawal transaction could not be updated.'));
            }
        }

        return $this->render('housekeepingbook/transaction/withdrawal/form.html.twig', [
            'pageTitle' => t('edit_withdrawal_transaction'),
            'form' => $form->createView(),
            'withdrawalTransaction' => $withdrawalTransaction,
            'button_label' => t('update.button'),
        ]);
    }


    #[Route('/{id}', name: 'housekeepingbook_withdrawal_transaction_delete', methods: ['POST'])]
    public function delete(Request $request, WithdrawalTransaction $withdrawalTransaction): Response
    {
        try {
            if ($this->isCsrfTokenValid('delete_withdrawal_transaction_' . $withdrawalTransaction->getId(), $request->request->get('_token'))) {
                $this->denyAccessUnlessGranted('delete', $withdrawalTransaction);
                $entityManager = $this->managerRegistry->getManager();
                $entityManager->remove($withdrawalTransaction);
                $entityManager->flush();
                $this->addFlash('success', t('Withdrawal transaction was deleted.'));
            }else {
                throw new Exception('invalid CSRF token');
            }
        }catch (Exception) {
            $this->addFlash('error', t('Withdrawal transaction could not be deleted.'));
        }

        return $this->redirectToRoute('housekeepingbook_withdrawal_transaction_index');
    }

    #[Route('/{id}/edit/state', name: 'housekeepingbook_withdrawal_transaction_edit_state', methods: ['POST'])]
    public function editState(Request $request, WithdrawalTransaction $withdrawalTransaction): Response
    {
        $this->denyAccessUnlessGranted('edit', $withdrawalTransaction);

        $state = $request->request->get('state') === 'true';

        try {
            $withdrawalTransaction->setCompleted($state);

            $entityManager = $this->managerRegistry->getManager();
            $entityManager->persist($withdrawalTransaction);
            $entityManager->flush();

            $transactionStateStr = $state ? 'completed' : "unconfirmed";

            $this->addFlash('success', t("Transaction state has been marked as " . $transactionStateStr . "."));
            return $this->json([
                'success' => true,
            ]);
        }catch (Exception) {
            $this->addFlash('error', t("Failed to mark transaction state as " . $transactionStateStr . "."));
            return $this->json([
                'success' => false,
            ]);
        }
    }
}
