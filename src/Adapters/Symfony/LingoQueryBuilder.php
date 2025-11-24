<?php
declare(strict_types=1);

namespace FieldLingo\Adapters\Symfony;

use Doctrine\ORM\QueryBuilder;

/**
 * Class LingoQueryBuilder
 *
 * Custom Doctrine QueryBuilder that supports localized attribute names (e.g. @@name).
 * Extends Doctrine's QueryBuilder to intercept query methods and translate
 * structured field names into language-specific column names.
 *
 * Example:
 *   $qb->select('p.@@name')
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
class LingoQueryBuilder extends QueryBuilder
{
    /**
     * Current locale
     * @var ?string
     */
    private ?string $currentLocale = null;

    /**
     * Localized prefixes
     * @var array
     */
    private array $localizedPrefixes = ['@@'];

    /**
     * Default language
     * @var string
     */
    private string $defaultLanguage = 'en';

    /**
     * Set current locale
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
     * Set localized prefixes
     *
     * @param array $prefixes
     * @return static
     */
    public function setLocalizedPrefixes(array $prefixes): static
    {
        $this->localizedPrefixes = $prefixes;
        return $this;
    }

    /**
     * Set default language
     *
     * @param string $language
     * @return static
     */
    public function setDefaultLanguage(string $language): static
    {
        $this->defaultLanguage = $language;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function select($select = null): static
    {
        $select = $this->localizeSelect($select);
        return parent::select($select);
    }

    /**
     * {@inheritdoc}
     */
    public function addSelect($select = null): static
    {
        $select = $this->localizeSelect($select);
        return parent::addSelect($select);
    }

    /**
     * {@inheritdoc}
     */
    public function where($predicates): static
    {
        $predicates = $this->localizeDQL($predicates);
        return parent::where($predicates);
    }

    /**
     * {@inheritdoc}
     */
    public function andWhere($where): static
    {
        $where = $this->localizeDQL($where);
        return parent::andWhere($where);
    }

    /**
     * {@inheritdoc}
     */
    public function orWhere($where): static
    {
        $where = $this->localizeDQL($where);
        return parent::orWhere($where);
    }

    /**
     * {@inheritdoc}
     */
    public function orderBy($sort, $order = null): static
    {
        $sort = $this->localizeDQL($sort);
        return parent::orderBy($sort, $order);
    }

    /**
     * {@inheritdoc}
     */
    public function addOrderBy($sort, $order = null): static
    {
        $sort = $this->localizeDQL($sort);
        return parent::addOrderBy($sort, $order);
    }

    /**
     * {@inheritdoc}
     */
    public function groupBy($groupBy): static
    {
        $groupBy = $this->localizeDQL($groupBy);
        return parent::groupBy($groupBy);
    }

    /**
     * {@inheritdoc}
     */
    public function addGroupBy($groupBy): static
    {
        $groupBy = $this->localizeDQL($groupBy);
        return parent::addGroupBy($groupBy);
    }

    /**
     * {@inheritdoc}
     */
    public function having($having): static
    {
        $having = $this->localizeDQL($having);
        return parent::having($having);
    }

    /**
     * {@inheritdoc}
     */
    public function andHaving($having): static
    {
        $having = $this->localizeDQL($having);
        return parent::andHaving($having);
    }

    /**
     * {@inheritdoc}
     */
    public function orHaving($having): static
    {
        $having = $this->localizeDQL($having);
        return parent::orHaving($having);
    }

    /**
     * Localize select expressions
     *
     * @param mixed $select
     * @return mixed
     */
    private function localizeSelect(mixed $select): mixed
    {
        if (is_array($select)) {
            return array_map(fn($s) => $this->localizeDQL($s), $select);
        }

        return $this->localizeDQL($select);
    }

    /**
     * Localize DQL string by replacing localized field references
     *
     * @param mixed $dql
     * @return mixed
     */
    private function localizeDQL(mixed $dql): mixed
    {
        if (!is_string($dql)) {
            return $dql;
        }

        // Pattern to match entity.@@field or alias.@@field
        foreach ($this->localizedPrefixes as $prefix) {
            $pattern = '/(\w+)\.' . preg_quote($prefix, '/') . '(\w+)/';
            $dql = preg_replace_callback($pattern, function ($matches) {
                $alias = $matches[1];
                $base = $matches[2];

                $lang = $this->currentLocale;
                $lang = (is_string($lang) && $lang !== '')
                    ? strtolower(preg_split('/[_-]/', $lang)[0])
                    : $this->defaultLanguage;

                return "{$alias}.{$base}_{$lang}";
            }, $dql);
        }

        return $dql;
    }
}
