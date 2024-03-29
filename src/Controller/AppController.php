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

namespace App\Controller;

use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class AppController extends AbstractController
{
    #[Route('/ping', name: 'app_ping')]
    public function ping(): Response
    {
        return new Response("PONG", 200);
    }

    #[Route('/', name: 'app_start')]
    public function indexNoLocale(Request $request): Response
    {
        return $this->redirectToRoute('app_start_localized', [

            // Since no locale was provided with the request, this method just uses
            // whatever was detected and chosen by the LocaleSubscriber:
            '_locale' => $request->getLocale()
        ]);
    }

    #[Route('/{_locale<%app.supported_locales%>}/', name: 'app_start_localized')]
    public function indexWithLocale(): Response
    {
        return $this->render('base.html.twig', [
            'pageTitle' => 'welcome'
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route('/locale/{_locale<%app.supported_locales%>}/', name: 'app_set_locale')]
    public function setUserLocale(string $_locale, SessionInterface $session, ManagerRegistry $managerRegistry): Response
    {
        // The requester explicitly decided to use a specified locale.
        // This is going to be persisted in the current session:
        $session->set('_locale', $_locale);

        // If the requester is a logged in use, the locale is also persisted in the database for further sessions.
        /** @var User $user */
        $user = $this->getUser();

        if($user) {
            $user->getUserProfile()->setLocale($session->get('_locale'));
            $em = $managerRegistry->getManager();
            $em->persist($user);
            $em->flush();
        }

        return $this->redirectToRoute('app_start_localized', ['_locale' => $_locale]);
    }

//    #[Route(path: '/toasts', name: 'app_toasts')]
//    public function toasts(): Response
//    {
//        return $this->render('_toasts.html.twig');
//    }
}
