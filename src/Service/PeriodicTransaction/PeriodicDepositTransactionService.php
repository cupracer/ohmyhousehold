<?php

namespace App\Service\PeriodicTransaction;

use App\Entity\Household;
use App\Entity\PeriodicDepositTransaction;
use App\Entity\User;
use App\Repository\PeriodicTransaction\PeriodicDepositTransactionRepository;
use App\Repository\HouseholdUserRepository;
use App\Service\DatatablesService;
use IntlDateFormatter;
use NumberFormatter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Security;

class PeriodicDepositTransactionService extends DatatablesService
{
    private PeriodicDepositTransactionRepository $periodicDepositTransactionRepository;
    private HouseholdUserRepository $householdUserRepository;
    private UrlGeneratorInterface $urlGenerator;
    private Security $security;

    public function __construct(PeriodicDepositTransactionRepository $periodicDepositTransactionRepository,
                                HouseholdUserRepository $householdUserRepository,
                                UrlGeneratorInterface $urlGenerator,
                                Security $security)
    {
        $this->periodicDepositTransactionRepository = $periodicDepositTransactionRepository;
        $this->householdUserRepository = $householdUserRepository;
        $this->urlGenerator = $urlGenerator;
        $this->security = $security;
    }

    public function getPeriodicDepositTransactionsAsDatatablesArray(Request $request, Household $household): array
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
            ['startDate', 'endDate', 'bookingInterval', 'bookingDayOfMonth', 'user', 'bookingCategory', 'source', 'destination', 'description', 'amount',],
            (array) $request->query->all('columns'),
            (array) $request->query->all('order')
        );

        $result = $this->periodicDepositTransactionRepository->getFilteredDataByHousehold(
            $household, $start, $length, $orderingData, $search);

        $tableData = [];

        /** @var User $user */
        $user = $this->security->getUser();
        $householdUser = $this->householdUserRepository->findOneByUserAndHousehold($user, $household);

        $numberFormatter = numfmt_create($user->getUserProfile()->getLocale(), NumberFormatter::CURRENCY);
        $dateFormatter = new IntlDateFormatter($user->getUserProfile()->getLocale(), IntlDateFormatter::MEDIUM, IntlDateFormatter::NONE);

        /** @var PeriodicDepositTransaction $row */
        foreach($result['data'] as $row) {

            $rowData = [
                'startDate' => $dateFormatter->format($row->getStartDate()),
                'endDate' => $row->getEndDate() ? $dateFormatter->format($row->getEndDate()) : null,
                'bookingDayOfMonth' => $row->getBookingDayOfMonth(),
                'bookingInterval' => $row->getBookingInterval(),
                'user' => $row->getHouseholdUser()->getUser()->getUsername(),
                'bookingCategory' => $row->getPrivate() && $row->getHouseholdUser() !== $householdUser ? 'hidden' : $row->getBookingCategory()->getName(),
                'source' => $row->getPrivate() && $row->getHouseholdUser() !== $householdUser ? 'hidden' : $row->getSource()->getAccountHolder()->getName(),
                'destination' => $row->getPrivate() && $row->getHouseholdUser() !== $householdUser ? 'hidden' : $row->getDestination()->getName(),
                'description' => $row->getPrivate() && $row->getHouseholdUser() !== $householdUser ? 'hidden' : $row->getDescription(),
                'amount' => $row->getPrivate() && $row->getHouseholdUser() !== $householdUser ? 'hidden' : $numberFormatter->formatCurrency($row->getAmount(), 'EUR'),
                'private' => $row->getPrivate(),
                'hidden' => $row->getPrivate() && $row->getHouseholdUser() !== $householdUser,
                'editLink' => $this->security->isGranted('edit', $row) ? $this->urlGenerator->generate('housekeepingbook_periodic_deposit_transaction_edit', ['id' => $row->getId()]) : null,
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