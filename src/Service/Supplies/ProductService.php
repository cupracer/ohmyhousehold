<?php

namespace App\Service\Supplies;

use App\Entity\Household;
use App\Entity\Supplies\Product;
use App\Repository\Supplies\ProductRepository;
use App\Service\DatatablesService;
use IntlDateFormatter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProductService extends DatatablesService
{
    private ProductRepository $productRepository;
    private UrlGeneratorInterface $urlGenerator;
    private TranslatorInterface $translator;

    public function __construct(
        ProductRepository $productRepository,
        UrlGeneratorInterface $urlGenerator,
        TranslatorInterface $translator)
    {
        $this->productRepository = $productRepository;
        $this->urlGenerator = $urlGenerator;
        $this->translator = $translator;
    }

    public function getProductsAsDatatablesArray(Request $request, Household $household, bool $inUseOnly): array
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
            ['name', 'brand', 'ean', 'category', 'packaging', 'usageCount', ],
            (array) $request->query->all('columns'),
            (array) $request->query->all('order')
        );

        $result = $this->productRepository->getFilteredDataByHousehold(
            $household, $start, $length, $orderingData, $inUseOnly, $search);

        $tableData = [];

        foreach($result['data'] as $row) {
            /** @var Product $product */
            $product = $row[0];
            $rowData = [
                'id' => $product->getId(),
                'name' => $product->getSupply()->getName() . ($product->getName() ? ' - ' . $product->getName() : ''),
                'brand' => $product->getBrand()->getName(),
                'ean' => $product->getEan(),
                'category' => $product->getSupply()->getCategory()?->getName(),
                'packaging' => $product->getPackaging() ? $this->translator->trans($product->getPackaging()->getName()) : null,
                'amount' => 1*$product->getQuantity() . ' ' . $this->translator->trans($product->getMeasure()->getUnit()),
                'minimumNumber' => $product->getMinimumNumber(),
                'usageCount' => $row['numUsage'],
                'orderValue' => $row['orderValue'],
                'createdAt' => IntlDateFormatter::formatObject($product->getCreatedAt())
            ];

            $rowData['editLink'] = $this->urlGenerator->generate(
                'supplies_product_edit', ['id' => $product->getId()]);

            $tableData[] = $rowData;
        }

        return [
            'draw' => $draw,
            'data' => $tableData,
            'recordsTotal' => $result['recordsTotal'],
            'recordsFiltered' => $result['recordsFiltered'],
        ];
    }

    public function getProductsAsSelect2Array(Request $request, Household $household, bool $inUseOnly): array
    {
        $page = $request->query->getInt('page', 1);
        $length = $request->query->getInt('length', 10);
        $start = $page > 1 ? $page * $length : 0;
        $search = $request->query->get('term', '');

        $orderingData = [
            [
                'name' => 'name',
                'dir' => 'asc',
            ]
        ];

        $result = $this->productRepository->getFilteredDataByHousehold(
            $household, $start, $length, $orderingData, $inUseOnly, $search);

        $tableData = [];

        foreach($result['data'] as $row) {
            /** @var Product $product */
            $product = $row[0];
            $rowData = [
                'id' => $product->getId(),
                'text' => $product->getSupply()->getName() .
                    ($product->getName() ? ' - ' . $product->getName() : '') .
                    ' - ' . $product->getBrand()->getName() .
                    ' - ' . 1*$product->getQuantity() . ' ' .$this->translator->trans($product->getMeasure()->getUnit()),
            ];

            $tableData[] = $rowData;
        }

        return [
            'results' => $tableData,
            'pagination' => [
                'more' => $start + $length < $result['recordsFiltered'],
            ]
        ];
    }
}