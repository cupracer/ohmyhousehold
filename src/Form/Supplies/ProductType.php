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

namespace App\Form\Supplies;

use App\Entity\Supplies\Brand;
use App\Entity\Supplies\Commodity;
use App\Entity\Supplies\Measure;
use App\Entity\Supplies\Packaging;
use App\Entity\Supplies\Product;
use App\Repository\Supplies\BrandRepository;
use App\Repository\Supplies\CommodityRepository;
use App\Repository\Supplies\MeasureRepository;
use App\Repository\Supplies\PackagingRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProductType extends AbstractType
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly MeasureRepository $measureRepository,
        private readonly PackagingRepository $packagingRepository,
        private readonly CommodityRepository $commodityRepository,
        private readonly BrandRepository $brandRepository,
    )
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('commodity', EntityType::class, [
                'class' => Commodity::class,
                'choice_label' => 'name',
                'label_format' => 'form.product.%name%',
                'choices' => $this->getSortedCommodityChoices(),
            ])
            ->add('name', TextType::class, [
                'label_format' => 'form.product.%name%',
                'required' => false,
            ])
            ->add('organicCertification', CheckboxType::class, [
                'label_format' => 'form.product.%name%',
                'required' => false,
            ])
            ->add('brand', EntityType::class, [
                'class' => Brand::class,
                'choice_label' => 'name',
                'label_format' => 'form.product.%name%',
                'choices' => $this->getSortedBrandChoices(),
            ])
            ->add('identifierCodes', CollectionType::class, [
                'entry_type' => IdentifierCodeType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'label' => false,
            ])
            ->add('quantity', TextType::class, [
                'label_format' => 'form.product.%name%',
            ])
            ->add('measure', EntityType::class, [
                'class' => Measure::class,
                'label_format' => 'form.product.%name%',
                'choices' => $this->getTranslatedAndSortedMeasureChoices(),
                'choice_label' => function($choice) {
                    return $this->translator->trans($choice->getName());
                },
            ])
            ->add('packaging', EntityType::class, [
                'class' => Packaging::class,
                'label_format' => 'form.product.%name%',
                'choices' => $this->getTranslatedAndSortedPackagingChoices(),
                'choice_label' => function($choice) {
                    return $this->translator->trans($choice->getName());
                },
            ])
            ->add('minimumGlobalStock', IntegerType::class, [
                'label_format' => 'form.product.minimumglobalstock',
                'required' => false,
            ])
            ->add('minimumProductStocks', CollectionType::class, [
                'entry_type' => MinimumProductStockType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'label' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id'   => 'product',
        ]);
    }

    /**
     * Get the translated and sorted measure choices
     * @return array
     */
    private function getTranslatedAndSortedMeasureChoices(): array
    {
        $choices = $this->measureRepository->findAll();

        // Translate and sort the choices
        usort($choices, function ($a, $b) {
            /** @var Measure $a */
            /** @var Measure $b */
            return strcmp($this->translator->trans($a->getName()), $this->translator->trans($b->getName()));
        });

        return $choices;
    }

    /**
     * Get the translated and sorted packaging choices
     * @return array
     */
    private function getTranslatedAndSortedPackagingChoices(): array
    {
        $choices = $this->packagingRepository->findAll();

        // Translate and sort the choices
        usort($choices, function ($a, $b) {
            /** @var Packaging $a */
            /** @var Packaging $b */
            return strcmp($this->translator->trans($a->getName()), $this->translator->trans($b->getName()));
        });

        return $choices;
    }

    /**
     * Get the sorted commodity choices
     * @return array
     */
    private function getSortedCommodityChoices(): array
    {
        $choices = $this->commodityRepository->findAll();

        // Sort the choices
        usort($choices, function ($a, $b) {
            /** @var Commodity $a */
            /** @var Commodity $b */
            return strcmp($a->getName(), $b->getName());
        });

        return $choices;
    }

    /**
     * Get the sorted brand choices
     * @return array
     */
    private function getSortedBrandChoices(): array
    {
        $choices = $this->brandRepository->findAll();

        // Sort the choices
        usort($choices, function ($a, $b) {
            /** @var Brand $a */
            /** @var Brand $b */
            return strcmp($a->getName(), $b->getName());
        });

        return $choices;
    }
}
