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

namespace App\Entity\Supplies;

use App\Repository\Supplies\CommodityRepository;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CommodityRepository::class)]
#[ORM\Table(name: 'supplies_commodity')]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['name'], message: 'form.supplies.commodity.name.not-unique')]
class Commodity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 255)]
    #[Assert\Regex(pattern: '/^[[:alpha:][:digit:]äöüÄÖÜ][[:alpha:][:digit:]äöüÄÖÜ\-\s_:;!]*[[:alpha:][:digit:]äöüÄÖÜ!]$/', message: 'form.regex.invalid')]
    private ?string $name = null;

    #[ORM\ManyToOne(inversedBy: 'commodities')]
    #[Assert\NotNull]
    private ?Category $category = null;

    #[ORM\OneToMany(mappedBy: 'commodity', targetEntity: Product::class)]
    private Collection $products;

    #[ORM\OneToMany(mappedBy: 'commodity', targetEntity: MinimumCommodityStock::class, cascade: ['persist'], orphanRemoval: true)]
    #[Assert\Valid]
    private Collection $minimumCommodityStocks;

    #[ORM\Column(type: 'datetime', options: ["default" => "CURRENT_TIMESTAMP"])]
    private DateTimeInterface $createdAt;

    #[ORM\Column(type: 'datetime', options: ["default" => "CURRENT_TIMESTAMP"])]
    private DateTimeInterface $updatedAt;

    #[ORM\Column(nullable: true)]
    #[Assert\Type(type: 'integer')]
    #[Assert\Length(max: 4)]
    #[Assert\Positive]
    private ?int $minimumGlobalStock = null;

    public function __construct()
    {
        $this->products = new ArrayCollection();
        $this->minimumCommodityStocks = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): self
    {
        $this->category = $category;

        return $this;
    }

    /**
     * @return Collection<int, Product>
     */
    public function getProducts(): Collection
    {
        return $this->products;
    }

    public function addProduct(Product $product): self
    {
        if (!$this->products->contains($product)) {
            $this->products->add($product);
            $product->setCommodity($this);
        }

        return $this;
    }

    public function removeProduct(Product $product): self
    {
        if ($this->products->removeElement($product)) {
            // set the owning side to null (unless already changed)
            if ($product->getCommodity() === $this) {
                $product->setCommodity(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, MinimumCommodityStock>
     */
    public function getMinimumCommodityStocks(): Collection
    {
        return $this->minimumCommodityStocks;
    }

    public function addMinimumCommodityStock(MinimumCommodityStock $minimumCommodityStock): self
    {
        if (!$this->minimumCommodityStocks->contains($minimumCommodityStock)) {
            $this->minimumCommodityStocks->add($minimumCommodityStock);
            $minimumCommodityStock->setCommodity($this);
        }

        return $this;
    }

    public function removeMinimumCommodityStock(MinimumCommodityStock $minimumCommodityStock): self
    {
        if ($this->minimumCommodityStocks->removeElement($minimumCommodityStock)) {
            // set the owning side to null (unless already changed)
            if ($minimumCommodityStock->getCommodity() === $this) {
                $minimumCommodityStock->setCommodity(null);
            }
        }

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

    public function getMinimumGlobalStock(): ?int
    {
        return $this->minimumGlobalStock;
    }

    public function setMinimumGlobalStock(?int $minimumGlobalStock): self
    {
        $this->minimumGlobalStock = $minimumGlobalStock;

        return $this;
    }
}
