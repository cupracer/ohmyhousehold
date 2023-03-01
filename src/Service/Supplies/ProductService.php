<?php

/*
 * Copyright (c) 2023. Thomas Schulte <thomas@cupracer.de>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software
 * and associated documentation files (the "Software"), to deal in the Software without restriction,
 * including without limitation the rights to use, copy, modify, merge, publish, distribute,
 * sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all copies or
 * substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO
 * THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
 * IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
 * WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE
 * OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

namespace App\Service\Supplies;

use App\Entity\Supplies\Product;
use App\Repository\Supplies\ProductRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProductService
{
    public function __construct(
        private readonly ProductRepository   $productRepository,
        private readonly TranslatorInterface $translator,
    )
    {
    }

    public function getProductsAsSelect2Array(Request $request, bool $inUseOnly = false): array
    {
        $page = $request->query->getInt('page', 1);
        $length = $request->query->getInt('length', 10);

        // set start variable to the first record of the page
        $start = ($page - 1) * $length;

        $search = $request->query->get('term', '');

        $result = $this->productRepository->getFilteredData(
            $start, $length, $inUseOnly, $search);

        $tableData = [];

        foreach($result['data'] as $row) {
            /** @var Product $product */
            $product = $row[0];
            $rowData = [
                'id' => $product->getId(),
                'text' => $product->getExtendedName() .
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