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

/*
 * Source: https://symfony.com/doc/current/session.html
 * TODO: Implement: Encryption of Session Data
 */

namespace App\EventSubscriber;

use App\Service\LocaleService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class LocaleSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly LocaleService $localeService
    )
    {
    }

    public function onKernelRequest(RequestEvent $event)
    {
        $request = $event->getRequest();

        // Upon every request this Subscriber is setting the request's locale.
        // If "_locale" is already available as a session variable (see AppController -> setUserLocale() ),
        // the detection of available and preferred locales is skipped.

        if(!$request->getSession()->has('_locale')) {

            // This array contains the App's default locale as the first element. This is also used as fallback.
            // All other elements are the App's supported locales.
            $availableOrderedLocales = array_unique(
                array_merge(
                    [$this->localeService->getDefaultLocale()],
                    array_keys($this->localeService->getSupportedLocales())
                )
            );

            // This chooses a matching language as requested by the browser
            // or the first element of the array if not match exists (see fallback above).
            $detectedLocale = $request->getPreferredLanguage($availableOrderedLocales);

            $request->setLocale($detectedLocale);
        }else {
            $request->setLocale($request->getSession()->get('_locale'));
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // must be registered before (i.e. with a higher priority than) the default Locale listener
            KernelEvents::REQUEST => [['onKernelRequest', 20]],
        ];
    }
}