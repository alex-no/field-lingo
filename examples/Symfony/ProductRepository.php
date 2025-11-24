<?php
declare(strict_types=1);

namespace App\Repository;

use App\Entity\Product;
use FieldLingo\Adapters\Symfony\LingoRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Example Product repository with localized query support.
 *
 * This repository demonstrates how to use FieldLingo's LingoRepository
 * to create queries with localized field names.
 */
class ProductRepository extends LingoRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    /**
     * Find products by localized name
     *
     * @param string $name
     * @param string $locale
     * @return Product[]
     */
    public function findByName(string $name, string $locale = 'en'): array
    {
        return $this->setLocale($locale)
            ->createQueryBuilder('p')
            ->where('p.@@name LIKE :name')
            ->setParameter('name', '%' . $name . '%')
            ->orderBy('p.@@name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find products with localized name and description
     *
     * @param string $locale
     * @return array
     */
    public function findAllWithLocalizedFields(string $locale = 'en'): array
    {
        return $this->setLocale($locale)
            ->createQueryBuilder('p')
            ->select('p.id', 'p.@@name', 'p.@@description', 'p.price')
            ->orderBy('p.@@name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Search products by localized description
     *
     * @param string $searchTerm
     * @param string $locale
     * @return Product[]
     */
    public function searchByDescription(string $searchTerm, string $locale = 'en'): array
    {
        return $this->setLocale($locale)
            ->createQueryBuilder('p')
            ->where('p.@@description LIKE :search')
            ->setParameter('search', '%' . $searchTerm . '%')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find products with price range and localized sorting
     *
     * @param float $minPrice
     * @param float $maxPrice
     * @param string $locale
     * @return Product[]
     */
    public function findByPriceRange(float $minPrice, float $maxPrice, string $locale = 'en'): array
    {
        return $this->setLocale($locale)
            ->createQueryBuilder('p')
            ->where('p.price BETWEEN :minPrice AND :maxPrice')
            ->setParameter('minPrice', $minPrice)
            ->setParameter('maxPrice', $maxPrice)
            ->orderBy('p.@@name', 'ASC')
            ->addOrderBy('p.price', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Example using the findByLocalized helper method
     *
     * @param string $name
     * @param string $locale
     * @return Product[]
     */
    public function findByLocalizedName(string $name, string $locale = 'en'): array
    {
        return $this->setLocale($locale)
            ->findByLocalized(
                ['@@name' => $name],
                ['@@name' => 'ASC']
            );
    }
}
