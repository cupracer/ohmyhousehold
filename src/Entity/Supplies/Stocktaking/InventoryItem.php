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

namespace App\Entity\Supplies\Stocktaking;

use App\Entity\Supplies\Article;
use App\Repository\Supplies\Stocktaking\InventoryItemRepository;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: InventoryItemRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\Table(name: 'supplies_stocktaking_inventory_item')]
class InventoryItem
{
    const STATUS_UNDECIDED = -1;
    const STATUS_CONFIRMED = 1;
    const STATUS_MISSING = 2;
    const STATUS_OVERSTOCKED = 3;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $brandName = null;

    #[ORM\Column(length: 255)]
    private ?string $commodityName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $productName = null;

    #[ORM\Column(length: 255)]
    private ?string $categoryName = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $bestBeforeDate = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    private ?Article $article = null;

    #[ORM\Column]
    #[Assert\NotNull]
    #[Assert\Choice(callback: [self::class, 'getStatuses'])]
    private ?int $status = self::STATUS_UNDECIDED;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, options: ["default" => "CURRENT_TIMESTAMP"])]
    private DateTimeInterface $createdAt;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, options: ["default" => "CURRENT_TIMESTAMP"])]
    private DateTimeInterface $updatedAt;

    #[ORM\ManyToOne(inversedBy: 'inventoryItems')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Stocktaking $stocktaking = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private array $identifierCodes = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBrandName(): ?string
    {
        return $this->brandName;
    }

    public function setBrandName(string $brandName): self
    {
        $this->brandName = $brandName;

        return $this;
    }

    public function getCommodityName(): ?string
    {
        return $this->commodityName;
    }

    public function setCommodityName(string $commodityName): self
    {
        $this->commodityName = $commodityName;

        return $this;
    }

    public function getProductName(): ?string
    {
        return $this->productName;
    }

    public function setProductName(?string $productName): self
    {
        $this->productName = $productName;

        return $this;
    }

    public function getCategoryName(): ?string
    {
        return $this->categoryName;
    }

    public function setCategoryName(string $categoryName): self
    {
        $this->categoryName = $categoryName;

        return $this;
    }

    public function getBestBeforeDate(): ?\DateTimeInterface
    {
        return $this->bestBeforeDate;
    }

    public function setBestBeforeDate(?\DateTimeInterface $bestBeforeDate): self
    {
        $this->bestBeforeDate = $bestBeforeDate;

        return $this;
    }

    public function getArticle(): ?Article
    {
        return $this->article;
    }

    public function setArticle(?Article $article): self
    {
        $this->article = $article;

        return $this;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return DateTimeInterface
     */
    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }

    /**
     * @param DateTimeInterface $createdAt
     * @return self
     */
    public function setCreatedAt(DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    #[ORM\PrePersist]
    public function setCreatedAtValue(): self
    {
        $this->createdAt = new DateTimeImmutable();
        return $this;
    }

    /**
     * @return DateTimeInterface|null
     */
    public function getUpdatedAt(): ?DateTimeInterface
    {
        return $this->updatedAt;
    }

    /**
     * @param DateTimeInterface $updatedAt
     * @return self
     */
    public function setUpdatedAt(DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): self
    {
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public static function getStatuses(): array
    {
        return [
            self::STATUS_UNDECIDED,
            self::STATUS_CONFIRMED,
            self::STATUS_MISSING,
            self::STATUS_OVERSTOCKED,
        ];
    }

    public function getStocktaking(): ?Stocktaking
    {
        return $this->stocktaking;
    }

    public function setStocktaking(?Stocktaking $stocktaking): self
    {
        $this->stocktaking = $stocktaking;

        return $this;
    }

    public function getIdentifierCodes(): ?array
    {
        return $this->identifierCodes;
    }

    public function setIdentifierCodes(?array $identifierCodes): self
    {
        $this->identifierCodes = $identifierCodes;

        return $this;
    }

    public function addIdentifierCode(string $identifierCode): self
    {
        $this->identifierCodes[] = $identifierCode;
        return $this;
    }

    public function removeIdentifierCode(string $identifierCode): self
    {
        $this->identifierCodes = array_diff($this->identifierCodes, [$identifierCode]);
        return $this;
    }
}
