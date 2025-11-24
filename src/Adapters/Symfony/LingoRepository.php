<?php
declare(strict_types=1);

namespace FieldLingo\Adapters\Symfony;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

/**
 * Class LingoRepository
 *
 * Custom Doctrine Repository that supports localized attribute names (e.g. @@name).
 * Extend your repositories from this class to benefit from automatic
 * localized field name resolution in queries.
 *
 * Example:
 *   $repository->createQueryBuilder('p')
 *       ->where('p.@@name = :name')
 *       ->orderBy('p.@@name', 'ASC');
 *
 * This file is part of FieldLingo package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package FieldLingo\Adapters\Symfony
 * @license MIT
 * @author  Oleksandr Nosov <alex@4n.com.ua>
 * @copyright 2025 Oleksandr Nosov
 * @since 1.0.0
 */
class LingoRepository extends EntityRepository
{
    /**
     * Current locale for query building
     * @var ?string
     */
    protected ?string $currentLocale = null;

    /**
     * Prefixes to identify localized fields
     * @var array
     */
    protected array $localizedPrefixes = ['@@'];

    /**
     * Default language
     * @var string
     */
    protected string $defaultLanguage = 'en';

    /**
     * Set current locale for queries
     *
     * @param string $locale
     * @return static
     */
    public function setLocale(string $locale): static
    {
        $this->currentLocale = $locale;
        return $this;
    }

    /**
     * Create a new QueryBuilder instance with localization support
     *
     * @param string $alias
     * @param string|null $indexBy
     * @return LingoQueryBuilder
     */
    public function createQueryBuilder($alias, $indexBy = null): LingoQueryBuilder
    {
        $qb = new LingoQueryBuilder($this->getEntityManager());
        $qb->select($alias)
            ->from($this->getEntityName(), $alias, $indexBy);

        if ($this->currentLocale !== null) {
            $qb->setLocale($this->currentLocale);
        }

        $qb->setLocalizedPrefixes($this->localizedPrefixes);
        $qb->setDefaultLanguage($this->defaultLanguage);

        return $qb;
    }

    /**
     * Find entities by localized criteria
     *
     * @param array $criteria
     * @param array|null $orderBy
     * @param int|null $limit
     * @param int|null $offset
     * @return array
     */
    public function findByLocalized(
        array $criteria,
        ?array $orderBy = null,
        ?int $limit = null,
        ?int $offset = null
    ): array {
        $qb = $this->createQueryBuilder('e');

        foreach ($criteria as $field => $value) {
            $localizedField = $this->localizeFieldName($field);
            $paramName = str_replace('.', '_', $localizedField);
            $qb->andWhere("e.{$localizedField} = :{$paramName}")
                ->setParameter($paramName, $value);
        }

        if ($orderBy !== null) {
            foreach ($orderBy as $field => $direction) {
                $localizedField = $this->localizeFieldName($field);
                $qb->addOrderBy("e.{$localizedField}", $direction);
            }
        }

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }

        if ($offset !== null) {
            $qb->setFirstResult($offset);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Localize a field name
     *
     * @param string $fieldName
     * @return string
     */
    protected function localizeFieldName(string $fieldName): string
    {
        foreach ($this->localizedPrefixes as $prefix) {
            if ($prefix !== '' && str_starts_with($fieldName, $prefix)) {
                $base = substr($fieldName, strlen($prefix));

                $lang = $this->currentLocale;
                $lang = (is_string($lang) && $lang !== '')
                    ? strtolower(preg_split('/[_-]/', $lang)[0])
                    : $this->defaultLanguage;

                return "{$base}_{$lang}";
            }
        }

        return $fieldName;
    }
}
