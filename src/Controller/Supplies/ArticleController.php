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

namespace App\Controller\Supplies;

use App\Entity\Supplies\Article;
use App\Form\Supplies\ArticleNewType;
use App\Form\Supplies\ArticleType;
use App\Repository\Supplies\StorageLocationRepository;
use DateTime;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Omines\DataTablesBundle\Adapter\Doctrine\ORMAdapter;
use Omines\DataTablesBundle\Column\DateTimeColumn;
use Omines\DataTablesBundle\Column\TextColumn;
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
#[Route('/{_locale<%app.supported_locales%>}/supplies/article')]
class ArticleController extends AbstractController
{
    #[Route('/', name: 'app_supplies_article_index', methods: ['GET', 'POST'])]
    public function index(Request $request, DataTableFactory $dataTableFactory, TranslatorInterface $translator): Response
    {
        $table = $dataTableFactory->create()
            ->add('product', TextColumn::class, [
                'label' => 'form.article.product',
                'field' => 'product.name',
                'render' => function($value, Article $article) {
                    return sprintf(
                        '<a href="%s">%s</a>',
                        $this->generateUrl('app_supplies_article_show', ['id' => $article->getId()]),
                        $value);
                },
            ])
            ->add('purchaseDate', DateTimeColumn::class, [
                'label' => 'form.article.purchaseDate',
                'format' => 'Y-m-d',
                'className' => 'min text-center',
                'visible' => false,
            ])
            ->add('bestBeforeDate', DateTimeColumn::class, [
                'label' => 'form.article.bestBeforeDate',
                'format' => 'Y-m-d',
                'className' => 'min text-center',
            ])
            ->add('createdAt', DateTimeColumn::class, [
                'label' => 'label.createdAt',
                'format' => 'Y-m-d H:i:s',
                'className' => 'min',
            ])
            ->add('updatedAt', DateTimeColumn::class, [
                'label' => 'label.updatedAt',
                'format' => 'Y-m-d H:i:s',
                'className' => 'min',
            ])
            ->addOrderBy('product')
            ->createAdapter(ORMAdapter::class, [
                'entity' => Article::class,
            ])
            ->handleRequest($request);

        if ($table->isCallback()) {
            return $table->getResponse();
        }

        return $this->render('supplies/article/index.html.twig', [
            'pageTitle' => 'app.supplies.articles.title',
            'datatable' => $table,
        ]);
    }

    #[Route('/new', name: 'app_supplies_article_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, LoggerInterface $logger): Response
    {
        $article = new Article();
        $form = $this->createForm(ArticleNewType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $quantity = $form->get('quantity')->getData();
            if (!is_int($quantity) || $quantity < 1) {
                $quantity = 1;
            }

            for($i = 0; $i < $quantity; $i++) {
                $newArticle = new Article();
                $newArticle->setProduct($article->getProduct());
                $newArticle->setStorageLocation($article->getStorageLocation());
                $newArticle->setPurchaseDate($article->getPurchaseDate());
                $newArticle->setBestBeforeDate($article->getBestBeforeDate());

                $entityManager->persist($newArticle);
                $entityManager->flush();

                $logger->info("New article '{name}' was created.", ['name' => $article->getProduct()->getName()]);
            }

            if($quantity > 1) {
                $flashMsg = new TranslatableMessage(
                    "app.supplies.article.form.success.created.multiple", [
                        '%name%' => $article->getProduct()->getName(),
                        '%quantity%' => $quantity
                    ]);
            }else {
                $flashMsg = new TranslatableMessage(
                    "app.supplies.article.form.success.created", [
                        '%name%' => $article->getProduct()->getName()
                    ]);
            }

            $this->addFlash('success', $flashMsg);


            return $this->redirectToRoute('app_supplies_article_new');
        }

        return $this->render('supplies/article/form.html.twig', [
            'form' => $form,
            'pageTitle' => 'app.supplies.article.form.create.title',
            // get the referrer url and  if it is this method's url, enable autofocus on product select
            'autoFocusProductSelect' => ($request->headers->get('referer') == $request->getUri()),
        ]);
    }

    #[Route('/{id}', name: 'app_supplies_article_show', methods: ['GET'])]
    public function show(Article $article): Response
    {
        return $this->render('supplies/article/show.html.twig', [
            'pageTitle' => new TranslatableMessage(
                "app.supplies.article.title", ['%name%' => $article->getProduct()->getName()]),
            'article' => $article,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_supplies_article_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Article $article, EntityManagerInterface $entityManager, LoggerInterface $logger): Response
    {
        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $logger->info("Article '{name}' ({id}) was updated.", ['name' => $article->getProduct()->getName(), 'id' => $article->getId()]);
            $this->addFlash('success', new TranslatableMessage(
                "app.supplies.article.form.success.updated", [
                '%name%' => $article->getProduct()->getName(),
                '%id%' => $article->getId()
            ]));

            return $this->redirectToRoute('app_supplies_article_show', ['id' => $article->getId()]);
        }

        return $this->render('supplies/article/form.html.twig', [
            'pageTitle' => new TranslatableMessage(
                "app.supplies.article.form.edit.title", ['%name%' => $article->getProduct()->getName()]),
            'form' => $form->createView(),
            'article' => $article,
        ]);
    }

    #[Route('/{id}', name: 'app_supplies_article_delete', methods: ['POST'])]
    public function delete(Request $request, Article $article, EntityManagerInterface $entityManager, LoggerInterface $logger): Response
    {
        $id = $article->getId();

        try {
            if ($this->isCsrfTokenValid('delete_article_' . $article->getId(), $request->request->get('_token'))) {
                $entityManager->remove($article);
                $entityManager->flush();

                $logger->info("Article '{name}' ({id}) was deleted.", ['name' => $article->getProduct()->getName(), 'id' => $id]);
                $this->addFlash('success', new TranslatableMessage(
                    "app.supplies.article.form.success.deleted", ['%name%' => $article->getProduct()->getName(), '%id%' => $id]));

                return $this->redirectToRoute('app_supplies_article_index');
            }else {
                $logger->error("Invalid CSRF token used while deleting article '{name}' ({id}).", ['name' => $article->getProduct()->getName(), 'id' => $id]);
                throw new Exception('invalid CSRF token');
            }
        } catch (ForeignKeyConstraintViolationException) {
            $this->addFlash('error', new TranslatableMessage(
                "app.supplies.article.form.delete.error.inuse", ['%name%' => $article->getProduct()->getName(), '%id%' => $id]));
        }catch (Exception $e) {
            $logger->error('Error occurred during article deletion: {error}', ['error' => $e->getMessage()]);
            $this->addFlash('error', new TranslatableMessage(
                "app.supplies.article.form.delete.error", ['%name%' => $article->getProduct()->getName(), '%id%' => $id]));
        }

        return $this->redirectToRoute('app_supplies_article_show', ['id' => $article->getId()]);
    }
}
