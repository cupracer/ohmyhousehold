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
use Doctrine\ORM\EntityManagerInterface;
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
    #[Route('/', name: 'app_supplies_brand_index')]
    public function index(): Response
    {
        return $this->render('supplies/brand/index.html.twig', [
            'controller_name' => 'BrandController',
        ]);
    }

    #[Route('/create', name: 'app_supplies_brand_create')]
    public function register(Request $request, EntityManagerInterface $entityManager, LoggerInterface $logger, SessionInterface $session): Response
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

            return $this->redirectToRoute('app_supplies_brand_create');
        }

        return $this->render('supplies/brand/form.html.twig', [
            'form' => $form,
            'pageTitle' => 'app.supplies.brand.form.create.title',
        ]);
    }
}
