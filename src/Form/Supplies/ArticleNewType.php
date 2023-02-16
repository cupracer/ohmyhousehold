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

use App\Entity\Supplies\Article;
use App\Entity\Supplies\Product;
use App\Entity\Supplies\StorageLocation;
use App\Repository\Supplies\ProductRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

class ArticleNewType extends AbstractType
{
    public function __construct(
        private readonly RouterInterface $router,
        private readonly ProductRepository $productRepository,
    )
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('purchaseDate', DateType::class, [
                'widget' => 'single_text',
                'label_format' => 'form.article.%name%',
            ])
            ->add('storageLocation', EntityType::class, [
                'class' => StorageLocation::class,
                'choice_label' => 'name',
                'label_format' => 'form.article.%name%',
            ])
            ->add('bestBeforeDate', DateType::class, [
                'widget' => 'single_text',
                'label_format' => 'form.article.%name%',
                'required' => false,
            ])
            ->add('quantity', IntegerType::class, [
                'label_format' => 'form.article.%name%',
                'mapped' => false,
                'constraints' => [
                    new NotBlank(),
                    new Range([
                        'min' => 1,
                        'max' => 1000,
                    ]),
                ],
            ])

            // The Products field is used with Select2 to load options dynamically via Ajax.
            // As Symfony would load all Products a seconds time, it is generated via EventListeners.
            //
            // PRE_SET_DATA uses an empty array,
            // PRE_SUBMIT picks the selected ID and tries to load the Product from the database.
            //
            // Validation: If a result is returned, the object seems to be fine, if not, the selection is wrong.

            ->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
                $form = $event->getForm();

                // add the product field with an empty array and a json url for select2
                $form->add('product', EntityType::class, [
                    'placeholder' => '',
                    'class' => Product::class,
                    'choices' => [],
                    'label_format' => 'form.article.%name%',
                    'attr' => [
                        'class' => 'form-control select2field',
                        'data-json-url' => $this->router->generate('app_supplies_product_select2'),
                    ],
                ]);
            })
            ->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
                $data = $event->getData();
                $form = $event->getForm();

                // check if the product field is set in the submitted data and get the product id if it is an integer
                $productId = array_key_exists('product', $data) && is_numeric($data['product']) ? $data['product'] : null;

                // add the product field with the selected product and a json url for select2
                $form->add('product', EntityType::class, [
                    'placeholder' => '',
                    'class' => Product::class,
                    'choices' => $this->productRepository->findBy(['id' => intval($productId)]),
                    'label_format' => 'form.article.%name%',
                    'attr' => [
                        'class' => 'form-control select2field',
                        'data-json-url' => $this->router->generate('app_supplies_product_select2'),
                    ],
                ]);
            })
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Article::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id'   => 'article_new',
        ]);
    }
}
