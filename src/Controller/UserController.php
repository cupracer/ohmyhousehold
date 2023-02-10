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

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use App\Security\EmailVerifier;
use App\Security\LoginFormControllerAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

#[Route('/{_locale<%app.supported_locales%>}/user')]
class UserController extends AbstractController
{
    private EmailVerifier $emailVerifier;

    public function __construct(EmailVerifier $emailVerifier)
    {
        $this->emailVerifier = $emailVerifier;
    }

    #[Route('/register', name: 'user_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, UserAuthenticatorInterface $userAuthenticator, LoginFormControllerAuthenticator $authenticator, EntityManagerInterface $entityManager, LoggerInterface $logger, SessionInterface $session): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            $user->getUserProfile()->setLocale($session->get('_locale'));

            $entityManager->persist($user);
            $entityManager->flush();

            // generate a signed url and email it to the user
            $this->emailVerifier->sendEmailConfirmation('user_verify_email', $user,
                (new TemplatedEmail())
                    ->to($user->getEmail())
                    ->subject('Please Confirm your Email')
                    ->htmlTemplate('user/register_confirmation_email.html.twig')
            );

            // do anything else you need here, like send an email
            $logger->info("New registration for User '{username}'", ['username' => $user->getUserIdentifier()]);

            // automatically authenticate user after registration
//            return $userAuthenticator->authenticateUser(
//                $user,
//                $authenticator,
//                $request
//            );
            return $this->render('user/register_confirmation_sent.html.twig', [
                'pageTitle' => 'app.register.confirmation',
            ]);
        }

        return $this->render('user/register.html.twig', [
            'registrationForm' => $form->createView(),
            'pageTitle' => 'app.register',
        ]);
    }

    #[Route('/verify/email', name: 'user_verify_email')]
    public function verifyUserEmail(Request $request, TranslatorInterface $translator, UserRepository $userRepository, LoggerInterface $logger): Response
    {
        $id = $request->get('id');

        if (null === $id) {
            return $this->redirectToRoute('user_register');
        }

        $user = $userRepository->find($id);

        if (null === $user) {
            return $this->redirectToRoute('user_register');
        }

        // validate email confirmation link, sets User::isVerified=true and persists
        try {
            $this->emailVerifier->handleEmailConfirmation($request, $user);
        } catch (VerifyEmailExceptionInterface $exception) {
            $this->addFlash('verify_email_error', $translator->trans($exception->getReason(), [], 'VerifyEmailBundle'));

            // TODO: Redirect to some better location (info page?) instead of showing the registration form
            return $this->redirectToRoute('user_register');
        }

        // @TODO Change the redirect on success and handle or remove the flash message in your templates
        $this->addFlash('success', 'Your email address has been verified.');
        $logger->info("E-mail address for User '{username}' has just been verified.", ['username' => $user->getUserIdentifier()]);

        return $this->render('user/register_confirmation_success.html.twig', [
            'pageTitle' => 'app.register.confirmation_success',
        ]);
    }

    #[Route(path: '/login', name: 'user_login')]
    public function login(AuthenticationUtils $authenticationUtils, LoggerInterface $logger): Response
    {
        $user = $this->getUser();
        if($user) {
            $logger->info("User '{username}' successfully logged in.", ['username' => $user->getUserIdentifier()]);
            return $this->redirectToRoute('app_start');
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        if($error) {
            $logger->info("Failed login attempt for User '{username}'.", ['username' => $lastUsername]);
        }

        return $this->render('user/login.html.twig', [
            'pageTitle' => 'app.sign-in',
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }
}
