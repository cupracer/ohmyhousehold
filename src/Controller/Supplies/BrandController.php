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

use App\Entity\Supplies\Brand;
use App\Form\Supplies\BrandType;
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
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Translation\TranslatableMessage;

#[IsGranted('ROLE_USER')]
#[Route('/{_locale<%app.supported_locales%>}/supplies/components/brand')]
class BrandController extends AbstractController
{
    #[Route('/', name: 'app_supplies_brand_index', methods: ['GET', 'POST'])]
    public function index(Request $request, DataTableFactory $dataTableFactory): Response
    {
        $table = $dataTableFactory->create()
            ->add('name', TextColumn::class, [
                'label' => 'form.brand.name',
                'render' => function($value, Brand $brand) {
                    return sprintf(
                        '<a href="%s">%s</a>',
                        $this->generateUrl('app_supplies_brand_show', ['id' => $brand->getId()]),
                        $value);
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
                'entity' => Brand::class,
            ])
            ->handleRequest($request);

        if ($table->isCallback()) {
            return $table->getResponse();
        }

        return $this->render('supplies/brand/index.html.twig', [
            'pageTitle' => 'app.supplies.brands.title',
            'datatable' => $table,
        ]);
    }

    #[Route('/new', name: 'app_supplies_brand_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, LoggerInterface $logger, SessionInterface $session): Response
    {
        $brand = new Brand();
        $form = $this->createForm(BrandType::class, $brand);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($brand);
            $entityManager->flush();

            $logger->info("New brand '{name}' was created.", ['name' => $brand->getName()]);
            $this->addFlash('success', new TranslatableMessage(
                "app.supplies.brand.form.success.created", ['%name%' => $brand->getName()]));

            return $this->redirectToRoute('app_supplies_brand_new');
        }

        return $this->render('supplies/brand/form.html.twig', [
            'form' => $form,
            'pageTitle' => 'app.supplies.brand.form.create.title',
        ]);
    }

    #[Route('/{id}', name: 'app_supplies_brand_show', methods: ['GET'])]
    public function show(Brand $brand): Response
    {
        return $this->render('supplies/brand/show.html.twig', [
            'pageTitle' => new TranslatableMessage(
                "app.supplies.brand.title", ['%name%' => $brand->getName()]),
            'brand' => $brand,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_supplies_brand_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Brand $brand, EntityManagerInterface $entityManager, LoggerInterface $logger): Response
    {
        $form = $this->createForm(BrandType::class, $brand);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $logger->info("Brand '{name}' ({id}) was updated.", ['name' => $brand->getName(), 'id' => $brand->getId()]);
            $this->addFlash('success', new TranslatableMessage(
                "app.supplies.brand.form.success.updated", [
                    '%name%' => $brand->getName(),
                    '%id%' => $brand->getId()
                ]));

            return $this->redirectToRoute('app_supplies_brand_show', ['id' => $brand->getId()]);
        }

        return $this->render('supplies/brand/form.html.twig', [
            'pageTitle' => new TranslatableMessage(
                "app.supplies.brand.form.edit.title", ['%name%' => $brand->getName()]),
            'form' => $form->createView(),
            'brand' => $brand,
        ]);
    }

    #[Route('/{id}', name: 'app_supplies_brand_delete', methods: ['POST'])]
    public function delete(Request $request, Brand $brand, EntityManagerInterface $entityManager, LoggerInterface $logger): Response
    {
        $id = $brand->getId();

        try {
            if ($this->isCsrfTokenValid('delete_brand_' . $brand->getId(), $request->request->get('_token'))) {
                $entityManager->remove($brand);
                $entityManager->flush();

                $logger->info("Brand '{name}' ({id}) was deleted.", ['name' => $brand->getName(), 'id' => $id]);
                $this->addFlash('success', new TranslatableMessage(
                    "app.supplies.brand.form.success.deleted", ['%name%' => $brand->getName(), '%id%' => $id]));
            }else {
                $logger->error("Invalid CSRF token used while deleting brand '{name}' ({id}).", ['name' => $brand->getName(), 'id' => $id]);
                throw new Exception('invalid CSRF token');
            }
        } catch (ForeignKeyConstraintViolationException) {
            $this->addFlash('error', new TranslatableMessage(
                "app.supplies.brand.form.delete.error.inuse", ['%name%' => $brand->getName(), '%id%' => $id]));
        }catch (Exception $e) {
            $logger->error('Error occuring during brand deletion: {error}', ['error' => $e->getMessage()]);
            $this->addFlash('error', new TranslatableMessage(
                "app.supplies.brand.form.delete.error", ['%name%' => $brand->getName(), '%id%' => $id]));
        }

        return $this->redirectToRoute('app_supplies_brand_index');
    }
}
