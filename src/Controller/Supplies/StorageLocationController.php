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

use App\Entity\Supplies\StorageLocation;
use App\Form\Supplies\StorageLocationType;
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

#[IsGranted('ROLE_USER')]
#[Route('/{_locale<%app.supported_locales%>}/supplies/components/storagelocation')]
class StorageLocationController extends AbstractController
{
    #[Route('/', name: 'app_supplies_storagelocation_index', methods: ['GET', 'POST'])]
    public function index(Request $request, DataTableFactory $dataTableFactory): Response
    {
        $table = $dataTableFactory->create()
            ->add('name', TextColumn::class, [
                'label' => 'form.storagelocation.name',
                'render' => function($value, StorageLocation $storageLocation) {
                    return sprintf(
                        '<a href="%s">%s</a>',
                        $this->generateUrl('app_supplies_storagelocation_show', ['id' => $storageLocation->getId()]),
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
                'entity' => StorageLocation::class,
            ])
            ->handleRequest($request);

        if ($table->isCallback()) {
            return $table->getResponse();
        }

        return $this->render('supplies/storage_location/index.html.twig', [
            'pageTitle' => 'app.supplies.storagelocations.title',
            'datatable' => $table,
        ]);
    }

    #[Route('/new', name: 'app_supplies_storagelocation_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, LoggerInterface $logger): Response
    {
        $storageLocation = new StorageLocation();
        $form = $this->createForm(StorageLocationType::class, $storageLocation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($storageLocation);
            $entityManager->flush();

            $logger->info("New storage location '{name}' was created.", ['name' => $storageLocation->getName()]);
            $this->addFlash('success', new TranslatableMessage(
                "app.supplies.storagelocation.form.success.created", ['%name%' => $storageLocation->getName()]));

            return $this->redirectToRoute('app_supplies_storagelocation_new');
        }

        return $this->render('supplies/storage_location/form.html.twig', [
            'form' => $form,
            'pageTitle' => 'app.supplies.storagelocation.form.create.title',
        ]);
    }

    #[Route('/{id}', name: 'app_supplies_storagelocation_show', methods: ['GET'])]
    public function show(StorageLocation $storageLocation): Response
    {
        return $this->render('supplies/storage_location/show.html.twig', [
            'pageTitle' => new TranslatableMessage(
                "app.supplies.storagelocation.title", ['%name%' => $storageLocation->getName()]),
            'storageLocation' => $storageLocation,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_supplies_storagelocation_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, StorageLocation $storageLocation, EntityManagerInterface $entityManager, LoggerInterface $logger): Response
    {
        $form = $this->createForm(StorageLocationType::class, $storageLocation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $logger->info("Storage location '{name}' ({id}) was updated.", ['name' => $storageLocation->getName(), 'id' => $storageLocation->getId()]);
            $this->addFlash('success', new TranslatableMessage(
                "app.supplies.storagelocation.form.success.updated", [
                    '%name%' => $storageLocation->getName(),
                    '%id%' => $storageLocation->getId()
                ]));

            return $this->redirectToRoute('app_supplies_storagelocation_show', ['id' => $storageLocation->getId()]);
        }

        return $this->render('supplies/storage_location/form.html.twig', [
            'pageTitle' => new TranslatableMessage(
                "app.supplies.storagelocation.form.edit.title", ['%name%' => $storageLocation->getName()]),
            'form' => $form->createView(),
            'storageLocation' => $storageLocation,
        ]);
    }

    #[Route('/{id}', name: 'app_supplies_storagelocation_delete', methods: ['POST'])]
    public function delete(Request $request, StorageLocation $storageLocation, EntityManagerInterface $entityManager, LoggerInterface $logger): Response
    {
        $id = $storageLocation->getId();

        try {
            if ($this->isCsrfTokenValid('delete_storagelocation_' . $storageLocation->getId(), $request->request->get('_token'))) {
                $entityManager->remove($storageLocation);
                $entityManager->flush();

                $logger->info("Storage location '{name}' ({id}) was deleted.", ['name' => $storageLocation->getName(), 'id' => $id]);
                $this->addFlash('success', new TranslatableMessage(
                    "app.supplies.storagelocation.form.success.deleted", ['%name%' => $storageLocation->getName(), '%id%' => $id]));
            }else {
                $logger->error("Invalid CSRF token used while deleting storage location '{name}' ({id}).", ['name' => $storageLocation->getName(), 'id' => $id]);
                throw new Exception('invalid CSRF token');
            }
        } catch (ForeignKeyConstraintViolationException) {
            $this->addFlash('error', new TranslatableMessage(
                "app.supplies.storagelocation.form.delete.error.inuse", ['%name%' => $storageLocation->getName(), '%id%' => $id]));
        }catch (Exception $e) {
            $logger->error('Error occuring during storage location deletion: {error}', ['error' => $e->getMessage()]);
            $this->addFlash('error', new TranslatableMessage(
                "app.supplies.storagelocation.form.delete.error", ['%name%' => $storageLocation->getName(), '%id%' => $id]));
        }

        return $this->redirectToRoute('app_supplies_storagelocation_index');
    }
}
