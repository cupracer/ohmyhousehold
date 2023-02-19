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

namespace App\Service;

use Symfony\Contracts\Translation\TranslatorInterface;

class LocaleService
{
    private string $defaultLocale;

    private array $supportedLocales;
    private TranslatorInterface $translator;

    public function __construct(string $defaultLocale, string $supportedLocales, TranslatorInterface $translator)
    {
        $this->defaultLocale = $defaultLocale;

        // explode creates an array from string, array_filter removes empty elements, array_values creates new index
        $this->supportedLocales = array_values(array_filter(explode('|', $supportedLocales)));
        $this->translator = $translator;
    }

    /**
     * @return string
     */
    public function getDefaultLocale(): string
    {
        return $this->defaultLocale;
    }

    public function getSupportedLocales(bool $reverse = false): array
    {
        $result = [];

        foreach($this->supportedLocales as $locale) {
            if($reverse) {
                $result[$this->translator->trans($locale)] = $locale;
            }else {
                $result[$locale] = $this->translator->trans($locale);
            }
        }

        return $result;
    }
}