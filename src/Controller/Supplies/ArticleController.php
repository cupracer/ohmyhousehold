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

use App\Entity\Supplies\Article;
use App\Form\Supplies\ArticleCheckoutType;
use App\Form\Supplies\ArticleNewType;
use App\Form\Supplies\ArticleType;
use App\Repository\Supplies\ArticleRepository;
use App\Repository\Supplies\StorageLocationRepository;
use DateTime;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Exception;
use Omines\DataTablesBundle\Adapter\Doctrine\ORM\SearchCriteriaProvider;
use Omines\DataTablesBundle\Adapter\Doctrine\ORMAdapter;
use Omines\DataTablesBundle\Column\DateTimeColumn;
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
#[Route('/{_locale<%app.supported_locales%>}/supplies/article')]
class ArticleController extends AbstractController
{
    #[Route('/', name: 'app_supplies_article_index', methods: ['GET', 'POST'])]
    public function index(Request $request, DataTableFactory $dataTableFactory, TranslatorInterface $translator): Response
    {
        $table = $dataTableFactory->create()
            ->add('commodity', TextColumn::class, [
                'label' => 'form.article.commodity',
                'field' => 'commodity.name',
                'visible' => false,
            ])
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
            ->add('purchaseDate', TwigStringColumn::class, [
                'label' => 'form.article.purchaseDate',
                'template' => '{% if value is not empty %}{{ value|format_date }}{% endif %}',
                'className' => 'min text-center',
                'visible' => false,
            ])
            ->add('bestBeforeDate', TwigStringColumn::class, [
                'label' => 'form.article.bestBeforeDate',
                'template' => '{% if value is not empty %}{{ value|format_date }}{% endif %}',
                'className' => 'min text-center',
            ])
            ->add('withdrawalDate', TwigStringColumn::class, [
                'label' => 'form.article.withdrawalDate',
                'template' => '{% if value is not empty %}{{ value|format_date }}{% endif %}',
                'className' => 'min text-center',
                'visible' => false, // would be empty anyway, because search criteria is used
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
            ->addOrderBy('product')
            ->createAdapter(ORMAdapter::class, [
                'entity' => Article::class,
                'query' => function(QueryBuilder $builder) {
                    $builder
                        ->select('a')
                        ->from(Article::class, 'a')
                        ->leftJoin('a.product', 'product')
                        ->innerJoin('product.commodity', 'commodity')
                        ->addSelect('product')
                        ->addSelect('commodity')
                        ->andWhere($builder->expr()->isNull('a.withdrawalDate'))
                    ;
                },
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
    public function new(Request $request, EntityManagerInterface $entityManager, StorageLocationRepository $storageLocationRepository, LoggerInterface $logger): Response
    {
        $article = new Article();

        // if session contains last purchase date, set it as default, if not, set now
        if($request->getSession()->has('lastPurchaseDate')) {
            $article->setPurchaseDate($request->getSession()->get('lastPurchaseDate'));
        }else {
            $article->setPurchaseDate(new DateTime());
        }

        // if session contains last storage location, set it as default
        if($request->getSession()->has('lastStorageLocationId')) {
            $article->setStorageLocation($storageLocationRepository
                    ->findOneBy(['id' => $request->getSession()->get('lastStorageLocationId')]));
        }

        $form = $this->createForm(ArticleNewType::class, $article);

        // set initial value for quantity to 1
        $form->get('quantity')->setData(1);

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

            // set last purchase date and storage location in session
            $request->getSession()->set('lastPurchaseDate', $article->getPurchaseDate());
            $request->getSession()->set('lastStorageLocationId', $article->getStorageLocation()->getId());

            return $this->redirectToRoute('app_supplies_article_new');
        }

        return $this->render('supplies/article/form.html.twig', [
            'form' => $form,
            'pageTitle' => 'app.supplies.article.form.create.title',
            // get the referrer url and  if it is this method's url, enable autofocus on product select
            'autoFocusProductSelect' => ($request->headers->get('referer') == $request->getUri()),
        ]);
    }

    #[Route('/show/{id}', name: 'app_supplies_article_show', methods: ['GET'])]
    public function show(Article $article): Response
    {
        return $this->render('supplies/article/show.html.twig', [
            'pageTitle' => new TranslatableMessage(
                "app.supplies.article.title", ['%name%' => $article->getProduct()->getName()]),
            'article' => $article,
        ]);
    }

    #[Route('/edit/{id}', name: 'app_supplies_article_edit', methods: ['GET', 'POST'])]
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

    #[Route('/delete/{id}', name: 'app_supplies_article_delete', methods: ['POST'])]
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

    #[Route('/checkout/{checkoutArticle}', name: 'app_supplies_article_checkout', requirements: ['checkoutArticle' => '\d+'], methods: ['GET', 'POST'])]
    public function checkout(Request $request, EntityManagerInterface $entityManager, ArticleRepository $articleRepository, LoggerInterface $logger, Article $checkoutArticle = null): Response
    {
        // if article is given, set withdrawal date to today and redirect to article checkout page
        if($checkoutArticle) {
            if(!$checkoutArticle->getWithdrawalDate()) {
                $checkoutArticle->setWithdrawalDate(new DateTime());
                $entityManager->flush();

                $logger->info("Article '{name}' ({id}) was checked out.", ['name' => $checkoutArticle->getProduct()->getName(), 'id' => $checkoutArticle->getId()]);
                $this->addFlash('success', new TranslatableMessage(
                    "app.supplies.article.form.success.checkedout", [
                    '%name%' => $checkoutArticle->getProduct()->getName(),
                    '%bbd%' => $checkoutArticle->getBestBeforeDate() ? $checkoutArticle->getBestBeforeDate()->format('Y-m-d') : '-',
                ]));
            }else {
                $logger->error("Article '{name}' ({id}) was already checked out.", ['name' => $checkoutArticle->getProduct()->getName(), 'id' => $checkoutArticle->getId()]);
                $this->addFlash('error', new TranslatableMessage(
                    "app.supplies.article.form.checkout.error.alreadycheckedout", [
                    '%name%' => $checkoutArticle->getProduct()->getName(),
                    '%id%' => $checkoutArticle->getId()
                ]));
            }

            // redirect to article checkout page
            return $this->redirectToRoute('app_supplies_article_checkout');
        }

        // if no article is given, prepare checkout form

        $form = $this->createForm(ArticleCheckoutType::class, $checkoutArticle);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $checkoutFormData = $form->getData();

            $availableArticles = $articleRepository->findAllInStockByProduct($checkoutFormData['product']);

            // build and array of bestBeforeDates for the selected article.
            // This array also includes the count for each bestBeforeDate or '-1' if no bestBeforeDate is set.
            // It also contains the first article id for each key.
            $bestBeforeDates = [];
            foreach($availableArticles as $availableArticle) {
                if($availableArticle->getBestBeforeDate()) {
                    $key = $availableArticle->getBestBeforeDate()->format('Y-m-d');
                }else {
                    $key = 'none';
                }
                // if key does not exist, create it
                if(!isset($bestBeforeDates[$key])) {
                    $bestBeforeDates[$key] = ['count' => 0];
                }
                // increase count for this key
                $bestBeforeDates[$key]['count']++;

                if(!isset($bestBeforeDates[$key]['id'])) {
                    $bestBeforeDates[$key]['id'] = $availableArticle->getId();
                }
            }

            // order ascending by bestBeforeDate
            ksort($bestBeforeDates);

            // if smart checkout is clicked and all articles have the same best before date, check out the first article
            if(($form->has('smartCheckout') && $form->get('smartCheckout')->isClicked()) &&
                (count($bestBeforeDates) == 1)) {

                $selectedArticle = $availableArticles[0];
                $selectedArticle->setWithdrawalDate(new DateTime());
                $entityManager->flush();

                $logger->info("Article '{name}' ({id}) was checked out.", ['name' => $selectedArticle->getProduct()->getName(), 'id' => $selectedArticle->getId()]);
                $this->addFlash('success', new TranslatableMessage(
                    "app.supplies.article.form.success.checkedout", [
                    '%name%' => $selectedArticle->getProduct()->getName(),
                    '%bbd%' => $selectedArticle->getBestBeforeDate() ? $selectedArticle->getBestBeforeDate()->format('Y-m-d') : '-',
                ]));

                // redirect to article checkout page
                return $this->redirectToRoute('app_supplies_article_checkout');
            }else {
                // if smart checkout is not clicked or there are multiple best before dates, render article selection page

                // get name and brand of the article's product as array
                $productData = [
                    'name' => $checkoutFormData['product']->getName(),
                    'brand' => $checkoutFormData['product']->getBrand()->getName(),
                ];

                // render article selection page
                return $this->render('supplies/article/form_checkout.html.twig', [
                    'pageTitle' => new TranslatableMessage(
                        "app.supplies.article.form.checkout.item.title", ['%name%' => $checkoutFormData['product']->getName()]),
                    'product' => $productData,
                    'bestBeforeDates' => $bestBeforeDates,
                ]);
            }
        }

        return $this->render('supplies/article/form_checkout.html.twig', [
            'pageTitle' => new TranslatableMessage(
                "app.supplies.article.form.checkout.title"),
            'form' => $form->createView(),
            'article' => $checkoutArticle,
        ]);
    }
}
