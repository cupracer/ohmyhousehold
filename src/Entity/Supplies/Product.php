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

use App\Repository\Supplies\ProductRepository;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[ORM\Table(name: 'supplies_product')]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: [
        'name', 'commodity', 'brand', 'measure', 'quantity', 'organicCertification', 'packaging',
    ], message: 'form.supplies.product.not-unique')]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(min: 2, max: 255)]
    #[Assert\Regex(
        pattern: '/^[[:alpha:][:digit:]äöüÄÖÜ\(\[][[:alpha:][:digit:]äöüÄÖÜ\-\s_\.:;!\(\)\[\]]*[[:alpha:][:digit:]äöüÄÖÜ!\)\]]$/',
        message: 'form.regex.invalid')]
    private ?string $name = null;

    #[ORM\ManyToOne(inversedBy: 'products')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    private ?Commodity $commodity = null;

    #[ORM\ManyToOne(inversedBy: 'products')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    private ?Brand $brand = null;

    #[ORM\ManyToOne(inversedBy: 'products')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    private ?Measure $measure = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^\d+(\.\d{1,2})?$/', message: 'form.regex.invalid')]
    private ?string $quantity = null;

    #[ORM\Column]
    #[Assert\NotNull]
    #[Assert\Type(type: 'bool')]
    private ?bool $organicCertification = null;

    #[ORM\ManyToOne(inversedBy: 'products')]
    private ?Packaging $packaging = null;

    #[ORM\OneToMany(mappedBy: 'product', targetEntity: MinimumProductStock::class, cascade: ['persist'], orphanRemoval: true)]
    #[Assert\Valid]
    private Collection $minimumProductStocks;

    #[ORM\Column(type: 'datetime', options: ["default" => "CURRENT_TIMESTAMP"])]
    private DateTimeInterface $createdAt;

    #[ORM\Column(type: 'datetime', options: ["default" => "CURRENT_TIMESTAMP"])]
    private DateTimeInterface $updatedAt;

    #[ORM\OneToMany(mappedBy: 'product', targetEntity: IdentifierCode::class, cascade: ['persist'], orphanRemoval: true)]
    #[Assert\Valid]
    private Collection $identifierCodes;

    #[ORM\Column(nullable: true)]
    #[Assert\Type(type: 'integer')]
    #[Assert\Length(max: 4)]
    #[Assert\Positive]
    private ?int $minimumGlobalStock = null;

    #[ORM\OneToMany(mappedBy: 'product', targetEntity: Article::class)]
    private Collection $articles;

    public function __construct()
    {
        $this->minimumProductStocks = new ArrayCollection();
        $this->identifierCodes = new ArrayCollection();
        $this->articles = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        // return concatenated name and commodity name if name is set, otherwise return commodity name
        if ($this->name) {
            return '[' . $this->getCommodity()?->getName() . '] ' . $this->name;
        }else {
            return $this->getCommodity()?->getName();
        }
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getCommodity(): ?Commodity
    {
        return $this->commodity;
    }

    public function setCommodity(?Commodity $commodity): self
    {
        $this->commodity = $commodity;

        return $this;
    }

    public function getBrand(): ?Brand
    {
        return $this->brand;
    }

    public function setBrand(?Brand $brand): self
    {
        $this->brand = $brand;

        return $this;
    }

    public function getMeasure(): ?Measure
    {
        return $this->measure;
    }

    public function setMeasure(?Measure $measure): self
    {
        $this->measure = $measure;

        return $this;
    }

    public function getQuantity(): ?string
    {
        return $this->quantity;
    }

    public function setQuantity(string $quantity): self
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function isOrganicCertification(): ?bool
    {
        return $this->organicCertification;
    }

    public function setOrganicCertification(bool $organicCertification): self
    {
        $this->organicCertification = $organicCertification;

        return $this;
    }

    public function getPackaging(): ?Packaging
    {
        return $this->packaging;
    }

    public function setPackaging(?Packaging $packaging): self
    {
        $this->packaging = $packaging;

        return $this;
    }

    /**
     * @return Collection<int, MinimumProductStock>
     */
    public function getMinimumProductStocks(): Collection
    {
        return $this->minimumProductStocks;
    }

    public function addMinimumProductStock(MinimumProductStock $minimumProductStock): self
    {
        if (!$this->minimumProductStocks->contains($minimumProductStock)) {
            $this->minimumProductStocks->add($minimumProductStock);
            $minimumProductStock->setProduct($this);
        }

        return $this;
    }

    public function removeMinimumProductStock(MinimumProductStock $minimumProductStock): self
    {
        if ($this->minimumProductStocks->removeElement($minimumProductStock)) {
            // set the owning side to null (unless already changed)
            if ($minimumProductStock->getProduct() === $this) {
                $minimumProductStock->setProduct(null);
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

    /**
     * @return Collection<int, IdentifierCode>
     */
    public function getIdentifierCodes(): Collection
    {
        return $this->identifierCodes;
    }

    public function addIdentifierCode(IdentifierCode $identifierCode): self
    {
        if (!$this->identifierCodes->contains($identifierCode)) {
            $this->identifierCodes->add($identifierCode);
            $identifierCode->setProduct($this);
        }

        return $this;
    }

    public function removeIdentifierCode(IdentifierCode $identifierCode): self
    {
        if ($this->identifierCodes->removeElement($identifierCode)) {
            // set the owning side to null (unless already changed)
            if ($identifierCode->getProduct() === $this) {
                $identifierCode->setProduct(null);
            }
        }

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

    /**
     * @return Collection<int, Article>
     */
    public function getArticles(): Collection
    {
        return $this->articles;
    }

    public function addArticle(Article $article): self
    {
        if (!$this->articles->contains($article)) {
            $this->articles->add($article);
            $article->setProduct($this);
        }

        return $this;
    }

    public function removeArticle(Article $article): self
    {
        if ($this->articles->removeElement($article)) {
            // set the owning side to null (unless already changed)
            if ($article->getProduct() === $this) {
                $article->setProduct(null);
            }
        }

        return $this;
    }
}
