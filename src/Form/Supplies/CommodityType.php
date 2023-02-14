<?php

/*
 * Copyright (c) 2023. Thomas Schulte <thomas@cupracer.de>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software
 * and associated documentation files (the “Software”), to deal in the Software without restriction,
 * including without limitation the rights to use, copy, modify, merge, publish, distribute,
 * sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all copies or
 * substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED “AS IS”, WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO
 * THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
 * IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
 * WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE
 * OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

namespace App\Form\Supplies;

use App\Entity\Supplies\Category;
use App\Entity\Supplies\Commodity;
use App\Repository\Supplies\CategoryRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class CommodityType extends AbstractType
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly CategoryRepository $categoryRepository,
    )
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label_format' => 'form.commodity.%name%',
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'label_format' => 'form.commodity.%name%',
                'choices' => $this->getTranslatedAndSortedCategoryChoices(),
                'choice_label' => function($choice) {
                    return $this->translator->trans($choice->getName());
                },
            ])
            ->add('minimumStocks', CollectionType::class, [
                'entry_type' => MinimumCommodityStockType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Commodity::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id'   => 'commodity',
        ]);
    }

    /**
     * Get the translated and sorted category choices
     * @return array
     */
    private function getTranslatedAndSortedCategoryChoices(): array
    {
        $choices = $this->categoryRepository->findAll();

        // Translate and sort the choices
        usort($choices, function ($a, $b) {
            /** @var Category $a */
            /** @var Category $b */
            return strcmp($this->translator->trans($a->getName()), $this->translator->trans($b->getName()));
        });

        return $choices;
    }
}
