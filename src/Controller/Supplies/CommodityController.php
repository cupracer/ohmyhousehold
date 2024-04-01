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

use App\Entity\Supplies\Commodity;
use App\Form\Supplies\CommodityType;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Exception;
use Omines\DataTablesBundle\Adapter\Doctrine\FetchJoinORMAdapter;
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

#[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
#[Route('/{_locale<%app.supported_locales%>}/supplies/commodity')]
class CommodityController extends AbstractController
{
    protected function getNumArticlesByCommodity(Commodity $commodity): int
    {
        $total = 0;

        foreach($commodity->getProducts() as $product) {
            $total+= count($product->getArticles());
        }

        return $total;
    }

    protected function getMinimumProductsStock(Commodity $commodity): int
    {
        $total = 0;

        foreach($commodity->getProducts() as $product) {
            $total+= $product->getMinimumGlobalStock();
        }

        return $total;
    }

    #[Route('/', name: 'app_supplies_commodity_index', methods: ['GET', 'POST'])]
    public function index(Request $request, DataTableFactory $dataTableFactory, TranslatorInterface $translator): Response
    {
        $table = $dataTableFactory->create()
            ->add('name', TextColumn::class, [
                'label' => 'form.commodity.name',
                'render' => function($value, Commodity $commodity) {
                    return sprintf(
                        '<a href="%s">%s</a>',
                        $this->generateUrl('app_supplies_commodity_show', ['id' => $commodity->getId()]),
                        $value);
                },
            ])
            // TODO: find a way to fix sorting and filtering translated strings instead of message keys
            ->add('category', TextColumn::class, [
                'label' => 'form.commodity.category',
                'field' => 'category.name',
                'render' => function($value) use ($translator) {
                    return $translator->trans($value);
                },
            ])
            ->add('numStock', TextColumn::class, [
                'label' => 'form.commodity.in-stock',
                'render' => function($value, Commodity $commodity) {
                    $numArticles = $this->getNumArticlesByCommodity($commodity);
                    $minGlobalStock = $commodity->getMinimumGlobalStock();
                    $minProductStock = $this->getMinimumProductsStock($commodity);

                    $minStock = $minGlobalStock >= $minProductStock ? $minGlobalStock : $minProductStock;

                    $buttonColor = 'primary';
                    $buttonText = $numArticles;
                    $titleText = "current: " . $numArticles;

                    if($minStock) {
                        $buttonText = $numArticles . ' / ' . $minStock;
                        $titleText = "current: " . $numArticles . ' / min: ' . $minStock;
                    }

                    if($minStock && $numArticles >= $minStock) {
                        $buttonColor = 'success';
                    }

                    if($minStock && $numArticles < $minStock && $numArticles > 0) {
                        $buttonColor = 'warning';
                    }

                    if($minStock && $numArticles < $minStock && $numArticles <= 0) {
                        $buttonColor = 'danger';
                    }

                    $button = sprintf('<button class="btn btn-xs no-padding btn-block bg-gradient-%s" title="%s">%s</button>', $buttonColor, $titleText, $buttonText);

                    if(!$minStock && $numArticles == 0) {
                        return '';
                    }else {
                        return $button;
                    }
                },
                'className' => 'min text-center',
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
            ->createAdapter(FetchJoinORMAdapter::class, [
                'entity' => Commodity::class,
                'query' => function(QueryBuilder $builder) {
                    // Note: It's important to include all relevant fields with "addSelect" if "where/andWhere" is used.
                    // Otherwise, omitted fields would be fetched by additional queries and *without* the conditions.
                    $builder
                        ->select('c')
                        ->addSelect('products')
                        ->addSelect('articles')
                        ->from(Commodity::class, 'c')
                        ->leftJoin('c.category', 'category')
                        ->leftJoin('c.products', 'products')
                        ->leftJoin('products.articles', 'articles')
                        ->andWhere($builder->expr()->isNull('articles.withdrawalDate'))
                        ->andWhere($builder->expr()->isNull('articles.discardDate'))
                    ;
                },
            ])
            ->handleRequest($request);

        if ($table->isCallback()) {
            return $table->getResponse();
        }

        return $this->render('supplies/commodity/index.html.twig', [
            'pageTitle' => 'app.supplies.commodities.title',
            'datatable' => $table,
        ]);
    }

    #[Route('/new', name: 'app_supplies_commodity_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, LoggerInterface $logger): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $commodity = new Commodity();
        $form = $this->createForm(CommodityType::class, $commodity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($commodity);
            $entityManager->flush();

            $logger->info("New commodity '{name}' was created.", ['name' => $commodity->getName()]);
            $this->addFlash('success', new TranslatableMessage(
                "app.supplies.commodity.form.success.created", ['%name%' => $commodity->getName()]));

            return $this->redirectToRoute('app_supplies_commodity_new');
        }

        return $this->render('supplies/commodity/form.html.twig', [
            'form' => $form,
            'pageTitle' => 'app.supplies.commodity.form.create.title',
        ]);
    }

    #[Route('/{id}', name: 'app_supplies_commodity_show', methods: ['GET'])]
    public function show(Commodity $commodity): Response
    {
        return $this->render('supplies/commodity/show.html.twig', [
            'pageTitle' => new TranslatableMessage(
                "app.supplies.commodity.title", ['%name%' => $commodity->getName()]),
            'commodity' => $commodity,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_supplies_commodity_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Commodity $commodity, EntityManagerInterface $entityManager, LoggerInterface $logger): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $form = $this->createForm(CommodityType::class, $commodity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $logger->info("Commodity '{name}' ({id}) was updated.", ['name' => $commodity->getName(), 'id' => $commodity->getId()]);
            $this->addFlash('success', new TranslatableMessage(
                "app.supplies.commodity.form.success.updated", [
                    '%name%' => $commodity->getName(),
                    '%id%' => $commodity->getId()
                ]));

            return $this->redirectToRoute('app_supplies_commodity_show', ['id' => $commodity->getId()]);
        }

        return $this->render('supplies/commodity/form.html.twig', [
            'pageTitle' => new TranslatableMessage(
                "app.supplies.commodity.form.edit.title", ['%name%' => $commodity->getName()]),
            'form' => $form->createView(),
            'commodity' => $commodity,
        ]);
    }

    #[Route('/{id}', name: 'app_supplies_commodity_delete', methods: ['POST'])]
    public function delete(Request $request, Commodity $commodity, EntityManagerInterface $entityManager, LoggerInterface $logger): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $id = $commodity->getId();

        try {
            if ($this->isCsrfTokenValid('delete_commodity_' . $commodity->getId(), $request->request->get('_token'))) {
                $entityManager->remove($commodity);
                $entityManager->flush();

                $logger->info("Commodity '{name}' ({id}) was deleted.", ['name' => $commodity->getName(), 'id' => $id]);
                $this->addFlash('success', new TranslatableMessage(
                    "app.supplies.commodity.form.success.deleted", ['%name%' => $commodity->getName(), '%id%' => $id]));

                return $this->redirectToRoute('app_supplies_commodity_index');
            }else {
                $logger->error("Invalid CSRF token used while deleting commodity '{name}' ({id}).", ['name' => $commodity->getName(), 'id' => $id]);
                throw new Exception('invalid CSRF token');
            }
        } catch (ForeignKeyConstraintViolationException) {
            $this->addFlash('error', new TranslatableMessage(
                "app.supplies.commodity.form.delete.error.inuse", ['%name%' => $commodity->getName(), '%id%' => $id]));
        }catch (Exception $e) {
            $logger->error('Error occurred during commodity deletion: {error}', ['error' => $e->getMessage()]);
            $this->addFlash('error', new TranslatableMessage(
                "app.supplies.commodity.form.delete.error", ['%name%' => $commodity->getName(), '%id%' => $id]));
        }

        return $this->redirectToRoute('app_supplies_commodity_show', ['id' => $commodity->getId()]);
    }
}
