<?php

namespace App\Service\Transaction;

use App\Entity\User;
use App\Entity\WithdrawalTransaction;
use App\Entity\Household;
use App\Repository\HouseholdUserRepository;
use App\Repository\Transaction\WithdrawalTransactionRepository;
use App\Service\DatatablesService;
use IntlDateFormatter;
use NumberFormatter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Security;

class WithdrawalTransactionService extends DatatablesService
{
    private WithdrawalTransactionRepository $withdrawalTransactionRepository;
    private HouseholdUserRepository $householdUserRepository;
    private UrlGeneratorInterface $urlGenerator;
    private Security $security;

    public function __construct(WithdrawalTransactionRepository $withdrawalTransactionRepository,
                                HouseholdUserRepository $householdUserRepository,
                                UrlGeneratorInterface $urlGenerator,
                                Security $security)
    {
        $this->withdrawalTransactionRepository = $withdrawalTransactionRepository;
        $this->householdUserRepository = $householdUserRepository;
        $this->urlGenerator = $urlGenerator;
        $this->security = $security;
    }

    public function getWithdrawalTransactionsAsDatatablesArray(Request $request, Household $household): array
    {
        $draw = $request->query->getInt('draw', 1);
        $start = $request->query->getInt('start');
        $length = $request->query->getInt('length', 10);
        $searchParam = (array) $request->query->all('search');

        if(array_key_exists('value', $searchParam)) {
            $search = $searchParam['value'];
        }else {
            $search = '';
        }

        $orderingData = $this->getOrderingData(
            ['bookingDate', 'user', 'bookingCategory', 'source', 'destination', 'description', 'amount',],
            (array) $request->query->all('columns'),
            (array) $request->query->all('order')
        );

        $result = $this->withdrawalTransactionRepository->getFilteredDataByHousehold(
            $household, $start, $length, $orderingData, $search);

        $tableData = [];

        /** @var User $user */
        $user = $this->security->getUser();
        $householdUser = $this->householdUserRepository->findOneByUserAndHousehold($user, $household);

        $numberFormatter = numfmt_create($user->getUserProfile()->getLocale(), NumberFormatter::CURRENCY);
        $dateFormatter = new IntlDateFormatter($user->getUserProfile()->getLocale(), IntlDateFormatter::MEDIUM, IntlDateFormatter::NONE);

        /** @var WithdrawalTransaction $row */
        foreach($result['data'] as $row) {
            $rowData = [
                'id' => $row->getId(),
                'completed' => $row->isCompleted(),
                'bookingDate' => $dateFormatter->format($row->getBookingDate()),
                'user' => $row->getHouseholdUser()->getUser()->getUsername(),
                'bookingCategory' => $row->getPrivate() && $row->getHouseholdUser() !== $householdUser ? 'hidden' : $row->getBookingCategory()->getName(),
                'source' => $row->getPrivate() && $row->getHouseholdUser() !== $householdUser ? 'hidden' : $row->getSource()->getName(),
                'destination' => $row->getPrivate() && $row->getHouseholdUser() !== $householdUser ? 'hidden' : $row->getDestination()->getAccountHolder()->getName(),
                'description' => $row->getPrivate() && $row->getHouseholdUser() !== $householdUser ? 'hidden' : $row->getDescription(),
                'amount' => $row->getPrivate() && $row->getHouseholdUser() !== $householdUser ? 'hidden' : $numberFormatter->formatCurrency($row->getAmount(), 'EUR'),
                'private' => $row->getPrivate(),
                'hidden' => $row->getPrivate() && $row->getHouseholdUser() !== $householdUser,
                'editLink' => $this->security->isGranted('edit', $row) ? $this->urlGenerator->generate('housekeepingbook_withdrawal_transaction_edit', ['id' => $row->getId()]) : null,
                'editStateLink' => $this->security->isGranted('edit', $row) ? $this->urlGenerator->generate('housekeepingbook_withdrawal_transaction_edit_state', ['id' => $row->getId()]) : null,
            ];

            $tableData[] = $rowData;
        }

        return [
            'draw' => $draw,
            'data' => $tableData,
            'recordsTotal' => $result['recordsTotal'],
            'recordsFiltered' => $result['recordsFiltered'],
        ];
    }

//    public function getAssetAccountsAsSelect2Array(Request $request, Household $household): array
//    {
//        $page = $request->query->getInt('page', 1);
//        $length = $request->query->getInt('length', 10);
//        $start = $page > 1 ? $page * $length : 0;
//        $search = $request->query->get('term', '');
//
//        $orderingData = [
//            [
//                'name' => 'name',
//                'dir' => 'asc',
//            ]
//        ];
//
//        $result = $this->assetAccountRepository->getFilteredDataByHousehold(
//            $household, $start, $length, $orderingData, $search);
//
//        $tableData = [];
//
//        /** @var AssetAccount $row */
//        foreach($result['data'] as $row) {
//            $rowData = [
//                'id' => $row->getId(),
//                'text' => $row->getName(),
//            ];
//
//            $tableData[] = $rowData;
//        }
//
//        return [
//            'results' => $tableData,
//            'pagination' => [
//                'more' => $start + $length < $result['recordsFiltered'],
//            ]
//        ];
//    }
}