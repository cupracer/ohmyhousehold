<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class AccessDeniedHandler implements AccessDeniedHandlerInterface
{
    private RequestStack $requestStack;
    private UrlGeneratorInterface $urlGenerator;
    private TranslatorInterface $translator;

    public function __construct(RequestStack $requestStack, UrlGeneratorInterface $urlGenerator, TranslatorInterface $translator)
    {
        $this->requestStack = $requestStack;
        $this->urlGenerator = $urlGenerator;
        $this->translator = $translator;
    }

    public function handle(Request $request, AccessDeniedException $accessDeniedException): ?Response
    {
        if(str_starts_with($request->attributes->get('_route'), 'api_')) {
            return new JsonResponse([
                'success' => false,
                "message" => $this->translator->trans("Permission denied.")
            ]);
        }

        // add a custom flash message and redirect to the login page
        $this->requestStack->getSession()->getFlashBag()->add('info', 'You don\'t have the permission to access this page.');


        return new RedirectResponse($this->urlGenerator->generate('homepage'));
    }
}