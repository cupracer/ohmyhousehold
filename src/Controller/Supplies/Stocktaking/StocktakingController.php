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

namespace App\Controller\Supplies\Stocktaking;

use App\Entity\Supplies\Article;
use App\Entity\Supplies\Stocktaking\InventoryItem;
use App\Entity\Supplies\Stocktaking\Stocktaking;
use App\Form\Supplies\Stocktaking\InventoryItemType;
use App\Form\Supplies\Stocktaking\StocktakingType;
use App\Service\Supplies\Stocktaking\StocktakingService;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Exception;
use Omines\DataTablesBundle\Adapter\Doctrine\ORMAdapter;
use Omines\DataTablesBundle\Column\DateTimeColumn;
use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\Column\TwigColumn;
use Omines\DataTablesBundle\Column\TwigStringColumn;
use Omines\DataTablesBundle\DataTableFactory;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

#[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
#[Route('/{_locale<%app.supported_locales%>}/supplies/stocktaking')]
class StocktakingController extends AbstractController
{
    #[Route('/', name: 'app_supplies_stocktaking_index')]
    public function index(Request $request, DataTableFactory $dataTableFactory): Response
    {
        $table = $dataTableFactory->create()
            ->add('name', TextColumn::class, [
                'label' => 'form.stocktaking.name',
                'render' => function($value, Stocktaking $stocktaking) {
                    return sprintf(
                        '<a href="%s">%s</a>',
                        $this->generateUrl('app_supplies_stocktaking_show', ['id' => $stocktaking->getId()]),
                        $value);
                },
            ])
            ->add('storageLocation', TextColumn::class, [
                'label' => 'form.article.storageLocation',
                'field' => 'stocktaking.storageLocation.name',
                'className' => 'min',
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
                'entity' => Stocktaking::class,
            ])
            ->handleRequest($request);

        if ($table->isCallback()) {
            return $table->getResponse();
        }

        return $this->render('supplies/stocktaking/index.html.twig', [
            'pageTitle' => 'app.supplies.stocktakings.title',
            'datatable' => $table,
        ]);
    }

    #[Route('/new', name: 'app_supplies_stocktaking_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, LoggerInterface $logger, StocktakingService $stocktakingService): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $stocktaking = new Stocktaking();
        $form = $this->createForm(StocktakingType::class, $stocktaking);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $stocktakingService->createInventoryItemsForAllCurrentArticles($stocktaking);
            $entityManager->persist($stocktaking);
            $entityManager->flush();

            $logger->info(new TranslatableMessage('app.supplies.stocktaking.created', ['%name%' => $stocktaking->getName()]));
            $this->addFlash('success', new TranslatableMessage('app.supplies.stocktaking.created', ['%name%' => $stocktaking->getName()]));

            return $this->redirectToRoute('app_supplies_stocktaking_show', ['id' => $stocktaking->getId()]);
        }

        return $this->render('supplies/stocktaking/form.html.twig', [
            'pageTitle' => 'app.supplies.stocktaking.new.title',
            'form' => $form->createView(),
        ]);
    }

    #[Route('/show/{id}', name: 'app_supplies_stocktaking_show', methods: ['GET', 'POST'])]
    public function show(Request $request, Stocktaking $stocktaking, DataTableFactory $dataTableFactory, Environment $twig): Response
    {
        $twig->addGlobal('updateUrl', 'app_supplies_stocktaking_item_update');
        $twig->addGlobal('deleteUrl', 'app_supplies_article_delete_ajax');

        $table = $dataTableFactory->create()
            ->add('article', TextColumn::class, [
                'label' => 'app.supplies.stocktaking.form.article',
                'className' => 'min text-center',
                'render' => function($value, InventoryItem $inventoryItem) {
                    if($inventoryItem->getArticle() !== null) {
                        return sprintf(
                            '<a href="%s">%s</a>',
                            $this->generateUrl('app_supplies_article_show', ['id' => $inventoryItem->getArticle()->getId()]),
                            $inventoryItem->getArticle()->getId());
                    }else {
                        return '';
                    }
                },
            ])
            ->add('commodityName', TextColumn::class, [
                'label' => 'form.product.commodity',
            ])
            ->add('brandName', TextColumn::class, [
                'label' => 'form.product.brand',
            ])
            ->add('productName', TextColumn::class, [
                'label' => 'form.article.product',
            ])
            ->add('bestBeforeDate', TwigStringColumn::class, [
                'label' => 'form.article.bestBeforeDate',
                'template' => '{% if value is not empty %}{{ value|format_date }}{% endif %}',
            ])
            ->add('identifierCodes', TextColumn::class, [
                'label' => 'form.product.identifierCodes',
                'data' => function(InventoryItem $item) {
                    return implode(',', $item->getIdentifierCodes());
                },
                'raw' => true,
                'visible' => false,
            ])
            ->add('status', TwigColumn::class, [
                'label' => 'form.inventory-item.status',
                'className' => 'min',
                'template' => 'supplies/stocktaking/_stocktaking_buttons.html.twig',
            ])
            ->addOrderBy('commodityName')
            ->addOrderBy('brandName')
            ->createAdapter(ORMAdapter::class, [
                'entity' => InventoryItem::class,
                'query' => function(QueryBuilder $builder) use ($stocktaking) {
                    $builder
                        ->select('i')
                        ->from(InventoryItem::class, 'i')
                        ->leftJoin('i.stocktaking', 's')
                        ->andWhere('s.id = :stocktaking')
                        ->setParameter('stocktaking', $stocktaking->getId())
                    ;
                },
            ])
            ->handleRequest($request);

        if ($table->isCallback()) {
            return $table->getResponse();
        }

        return $this->render('supplies/stocktaking/show.html.twig', [
            'pageTitle' => new TranslatableMessage(
                "app.supplies.stocktaking.title", ['%name%' => $stocktaking->getName()]),
            'stocktaking' => $stocktaking,
            'datatable' => $table,
        ]);
    }

    #[Route('/item/{id}/update', name: 'app_supplies_stocktaking_item_update', methods: ['POST'])]
    public function edit(Request $request, InventoryItem $inventoryItem, EntityManagerInterface $entityManager, LoggerInterface $logger, TranslatorInterface $translator): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $form = $this->createForm(InventoryItemType::class, $inventoryItem);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $logger->info("app.supplies.stocktaking.item.updated", [
                '%name%' => $inventoryItem->getStocktaking()->getName(),
                '%id%' => $inventoryItem->getId()
            ]);

            return new JsonResponse([
                'status' => 'success',
                'message' => $translator->trans(
                    "app.supplies.stocktaking.item.updated", [
                    '%name%' => $inventoryItem->getExtendedProductName(),
                ]),
            ]);
        }

        return new JsonResponse([
            'status' => 'error',
            'message' => $translator->trans(
                "app.supplies.stocktaking.item.update.failed", [
                '%name%' => $inventoryItem->getExtendedProductName(),
            ]),
        ]);
    }

    #[Route('/{id}', name: 'app_supplies_stocktaking_delete', methods: ['POST'])]
    public function delete(Request $request, Stocktaking $stocktaking, EntityManagerInterface $entityManager, LoggerInterface $logger): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $id = $stocktaking->getId();

        try {
            if ($this->isCsrfTokenValid('delete_stocktaking_' . $stocktaking->getId(), $request->request->get('_token'))) {
                $entityManager->remove($stocktaking);
                $entityManager->flush();

                $logger->info("Stocktaking '{name}' ({id}) was deleted.", ['name' => $stocktaking->getName(), 'id' => $id]);
                $this->addFlash('success', new TranslatableMessage(
                    "app.supplies.stocktaking.form.success.deleted", ['%name%' => $stocktaking->getName(), '%id%' => $id]));
            }else {
                $logger->error("Invalid CSRF token used while deleting stocktaking '{name}' ({id}).", ['name' => $stocktaking->getName(), 'id' => $id]);
                throw new Exception('invalid CSRF token');
            }
        } catch (ForeignKeyConstraintViolationException) {
            $this->addFlash('error', new TranslatableMessage(
                "app.supplies.stocktaking.form.delete.error.inuse", ['%name%' => $stocktaking->getName(), '%id%' => $id]));
        }catch (Exception $e) {
            $logger->error('Error occuring during stocktaking deletion: {error}', ['error' => $e->getMessage()]);
            $this->addFlash('error', new TranslatableMessage(
                "app.supplies.stocktaking.form.delete.error", ['%name%' => $stocktaking->getName(), '%id%' => $id]));
        }

        return $this->redirectToRoute('app_supplies_stocktaking_index');
    }
}
