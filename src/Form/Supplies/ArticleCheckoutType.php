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

use App\Entity\Supplies\Product;
use App\Repository\Supplies\ProductRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;

class ArticleCheckoutType extends AbstractType
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
                        'data-json-url' => $this->router->generate('app_supplies_product_select2', ['inUseOnly' => true]),
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
                        'data-json-url' => $this->router->generate('app_supplies_product_select2', ['inUseOnly' => true]),
                    ],
                ]);
            })

            ->add('showItems', SubmitType::class, [
                'label' => 'app.supplies.article.checkout.button.show_items',
            ])
            ->add('smartCheckout', SubmitType::class, [
                'label' => 'app.supplies.article.checkout.button.smart_checkout',
                'attr' => [
                    'class' => 'btn-success',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id'   => 'article_checkout',
        ]);
    }
}