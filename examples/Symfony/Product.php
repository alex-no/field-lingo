<?php
declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use FieldLingo\Adapters\Symfony\LingoEntity;

/**
 * Example Product entity with localized attributes.
 *
 * This entity demonstrates how to use FieldLingo with Symfony/Doctrine
 * to handle multilingual database columns.
 *
 * Database schema example:
 * - id (int)
 * - name_en (varchar)
 * - name_uk (varchar)
 * - name_de (varchar)
 * - description_en (text)
 * - description_uk (text)
 * - description_de (text)
 * - price (decimal)
 * - created_at (datetime)
 */
#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[ORM\Table(name: 'products')]
class Product extends LingoEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $name_en = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $name_uk = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $name_de = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description_en = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description_uk = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description_de = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private ?string $price = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    // English getters/setters
    public function getNameEn(): ?string
    {
        return $this->name_en;
    }

    public function setNameEn(string $name_en): static
    {
        $this->name_en = $name_en;
        return $this;
    }

    public function getNameUk(): ?string
    {
        return $this->name_uk;
    }

    public function setNameUk(?string $name_uk): static
    {
        $this->name_uk = $name_uk;
        return $this;
    }

    public function getNameDe(): ?string
    {
        return $this->name_de;
    }

    public function setNameDe(?string $name_de): static
    {
        $this->name_de = $name_de;
        return $this;
    }

    public function getDescriptionEn(): ?string
    {
        return $this->description_en;
    }

    public function setDescriptionEn(?string $description_en): static
    {
        $this->description_en = $description_en;
        return $this;
    }

    public function getDescriptionUk(): ?string
    {
        return $this->description_uk;
    }

    public function setDescriptionUk(?string $description_uk): static
    {
        $this->description_uk = $description_uk;
        return $this;
    }

    public function getDescriptionDe(): ?string
    {
        return $this->description_de;
    }

    public function setDescriptionDe(?string $description_de): static
    {
        $this->description_de = $description_de;
        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(string $price): static
    {
        $this->price = $price;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }
}
