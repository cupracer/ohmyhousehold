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

use App\Entity\Supplies\Commodity;
use App\Form\Supplies\CommodityType;
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
#[Route('/{_locale<%app.supported_locales%>}/supplies/commodity')]
class CommodityController extends AbstractController
{
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
            ->addOrderBy('name')
            ->createAdapter(ORMAdapter::class, [
                'entity' => Commodity::class,
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
