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

namespace App\Entity;

class NavbarNotificationItem
{
    /** @var string */
    private string $category;

    /** @var string */
    private string $title;

    /** @var string */
    private string $note;

    /** @var int */
    private int $itemId;

    /** @var string */
    private string $cssClass;

    /**
     * @return string
     */
    public function getCategory(): string
    {
        return $this->category;
    }

    /**
     * @param string $category
     * @return NavbarNotificationItem
     */
    public function setCategory(string $category): NavbarNotificationItem
    {
        $this->category = $category;
        return $this;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     * @return NavbarNotificationItem
     */
    public function setTitle(string $title): NavbarNotificationItem
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return string
     */
    public function getNote(): string
    {
        return $this->note;
    }

    /**
     * @param string $note
     * @return NavbarNotificationItem
     */
    public function setNote(string $note): NavbarNotificationItem
    {
        $this->note = $note;
        return $this;
    }

    /**
     * @return int
     */
    public function getItemId(): int
    {
        return $this->itemId;
    }

    /**
     * @param int $itemId
     * @return NavbarNotificationItem
     */
    public function setItemId(int $itemId): NavbarNotificationItem
    {
        $this->itemId = $itemId;
        return $this;
    }

    /**
     * @return string
     */
    public function getCssClass(): ?string
    {
        return $this->cssClass;
    }

    /**
     * @param string $cssClass
     * @return NavbarNotificationItem
     */
    public function setCssClass(string $cssClass): NavbarNotificationItem
    {
        $this->cssClass = $cssClass;
        return $this;
    }
}