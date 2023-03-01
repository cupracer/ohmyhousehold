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

namespace App\Controller\Supplies;

use App\Entity\Supplies\Product;
use App\Form\Supplies\ProductType;
use App\Service\Supplies\ProductService;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Omines\DataTablesBundle\Adapter\Doctrine\ORMAdapter;
use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\Column\TwigStringColumn;
use Omines\DataTablesBundle\DataTableFactory;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

#[IsGranted('ROLE_USER')]
#[Route('/{_locale<%app.supported_locales%>}/supplies/product')]
class ProductController extends AbstractController
{
    #[Route('/', name: 'app_supplies_product_index', methods: ['GET', 'POST'])]
    public function index(Request $request, DataTableFactory $dataTableFactory, TranslatorInterface $translator): Response
    {
        $table = $dataTableFactory->create()
            ->add('commodity', TextColumn::class, [
                'label' => 'form.product.commodity',
                'field' => 'commodity.name',
            ])
            ->add('brand', TextColumn::class, [
                'label' => 'form.product.brand',
                'field' => 'brand.name',
                'className' => 'min',
            ])
            ->add('name', TextColumn::class, [
                'label' => 'form.product.name',
                'render' => function($value, Product $product) {
                    return sprintf(
                        '<a href="%s">%s</a>',
                        $this->generateUrl('app_supplies_product_show', ['id' => $product->getId()]),
                        $value ?: 'dto.');
                },
            ])
            ->add('quantity', TextColumn::class, [
                'label' => 'form.product.quantity',
                'className' => 'min text-right',
                'render' => function($value) {
                    if (is_numeric($value) && floor($value) == $value) {
                        return (int)$value;
                    }else {
                        return $value;
                    }
                },
            ])
            ->add('measure', TextColumn::class, [
                'label' => 'form.product.measure',
                'field' => 'measure.unit',
                'className' => 'min',
                'render' => function($value) use ($translator) {
                    return $translator->trans($value);
                },
            ])
            ->add('packaging', TextColumn::class, [
                'label' => 'form.product.packaging',
                'field' => 'packaging.name',
                'className' => 'min',
                'render' => function($value) use ($translator) {
                    return $translator->trans($value);
                },
            ])
            ->add('createdAt', TwigStringColumn::class, [
                'label' => 'label.createdAt',
                'template' => '{% if value is not empty %}{{ value|format_datetime }}{% endif %}',
                'className' => 'min',
            ])
            ->add('updatedAt', TwigStringColumn::class, [
                'label' => 'label.updatedAt',
                'template' => '{% if value is not empty %}{{ value|format_datetime }}{% endif %}',
                'className' => 'min',
            ])
            ->addOrderBy('name')
            ->createAdapter(ORMAdapter::class, [
                'entity' => Product::class,
            ])
            ->handleRequest($request);

        if ($table->isCallback()) {
            return $table->getResponse();
        }
        return $this->render('supplies/product/index.html.twig', [
            'pageTitle' => 'app.supplies.products.title',
            'datatable' => $table,
        ]);
    }

    #[Route('/new', name: 'app_supplies_product_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, LoggerInterface $logger): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($product);
            $entityManager->flush();

            $logger->info("New product '{name}' ({id}) was created.", ['name' => $product->getShortName(), 'id' => $product->getId()]);
            $this->addFlash('success', new TranslatableMessage(
                "app.supplies.product.form.success.created", ['%name%' => $product->getShortName()]));

            return $this->redirectToRoute('app_supplies_product_new');
        }

        return $this->render('supplies/product/form.html.twig', [
            'form' => $form,
            'pageTitle' => 'app.supplies.product.form.create.title',
        ]);
    }

    #[Route('/{id}/show', name: 'app_supplies_product_show', methods: ['GET'])]
    public function show(Product $product): Response
    {
        return $this->render('supplies/product/show.html.twig', [
            'pageTitle' => new TranslatableMessage(
                "app.supplies.product.title", ['%name%' => $product->getShortName()]),
            'product' => $product,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_supplies_product_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Product $product, EntityManagerInterface $entityManager, LoggerInterface $logger): Response
    {
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $logger->info("Product '{name}' ({id}) was updated.", ['name' => $product->getShortName(), 'id' => $product->getId()]);
            $this->addFlash('success', new TranslatableMessage(
                "app.supplies.product.form.success.updated", [
                '%name%' => $product->getShortName(),
                '%id%' => $product->getId()
            ]));

            return $this->redirectToRoute('app_supplies_product_show', ['id' => $product->getId()]);
        }

        return $this->render('supplies/product/form.html.twig', [
            'pageTitle' => new TranslatableMessage(
                "app.supplies.product.form.edit.title", ['%name%' => $product->getShortName()]),
            'form' => $form->createView(),
            'product' => $product,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_supplies_product_delete', methods: ['POST'])]
    public function delete(Request $request, Product $product, EntityManagerInterface $entityManager, LoggerInterface $logger): Response
    {
        $id = $product->getId();

        try {
            if ($this->isCsrfTokenValid('delete_product_' . $product->getId(), $request->request->get('_token'))) {
                $entityManager->remove($product);
                $entityManager->flush();

                $logger->info("Product '{name}' ({id}) was deleted.", ['name' => $product->getShortName(), 'id' => $id]);
                $this->addFlash('success', new TranslatableMessage(
                    "app.supplies.product.form.success.deleted", ['%name%' => $product->getShortName(), '%id%' => $id]));

                return $this->redirectToRoute('app_supplies_product_index');
            }else {
                $logger->error("Invalid CSRF token used while deleting product '{name}' ({id}).", ['name' => $product->getShortName(), 'id' => $id]);
                throw new Exception('invalid CSRF token');
            }
        } catch (ForeignKeyConstraintViolationException) {
            $this->addFlash('error', new TranslatableMessage(
                "app.supplies.product.form.delete.error.inuse", ['%name%' => $product->getShortName(), '%id%' => $id]));
        }catch (Exception $e) {
            $logger->error('Error occurred during commodity deletion: {error}', ['error' => $e->getMessage()]);
            $this->addFlash('error', new TranslatableMessage(
                "app.supplies.product.form.delete.error", ['%name%' => $product->getShortName(), '%id%' => $id]));
        }

        return $this->redirectToRoute('app_supplies_product_show', ['id' => $product->getId()]);
    }

    // AJAX endpoint for select2
    #[Route('/select2', name: 'app_supplies_product_select2', methods: ['GET'])]
    public function getAsSelect2(Request $request, ProductService $productService): Response
    {
        // create a variable with the boolean value of the query parameter inUseOnly and set false as default
        $inUseOnly = filter_var($request->query->get('inUseOnly', false), FILTER_VALIDATE_BOOLEAN);

        return $this->json(
            $productService->getProductsAsSelect2Array($request, $inUseOnly)
        );
    }
}
