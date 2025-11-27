<?php

declare(strict_types=1);

namespace Yiisoft\ActiveRecord;

/**
 * Compatibility stub for ActiveRecord
 * This is a temporary compatibility layer for alex-no/field-lingo package
 * since Yii3 doesn't have an official ActiveRecord implementation yet.
 *
 * This class provides minimal ActiveRecord-like functionality using PDO.
 */
abstract class ActiveRecord
{
    private static ?\PDO $db = null;
    protected array $attributes = [];
    protected array $oldAttributes = [];
    private bool $isNewRecord = true;

    /**
     * Returns the database table name
     */
    abstract public function getTableName(): string;

    /**
     * Returns validation rules
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * Returns attribute labels
     */
    public function attributeLabels(): array
    {
        return [];
    }

    /**
     * Creates a query instance
     */
    public static function find(): ActiveQueryInterface
    {
        return new ActiveQuery(static::class);
    }

    /**
     * Returns primary key name
     */
    public static function primaryKey(): array
    {
        return ['id'];
    }

    /**
     * Find one record by primary key
     */
    public static function findOne(int|array $condition): ?static
    {
        $query = static::find();

        if (is_int($condition)) {
            $pk = static::primaryKey()[0] ?? 'id';
            $query->where([$pk => $condition]);
        } elseif (is_array($condition)) {
            $query->where($condition);
        }

        return $query->one();
    }

    /**
     * Get PDO connection
     */
    public static function getDb(): \PDO
    {
        if (self::$db === null) {
            // Try to get PDO from global DI container
            if (class_exists('\Yiisoft\Di\Container') && isset($GLOBALS['container'])) {
                self::$db = $GLOBALS['container']->get(\PDO::class);
            } else {
                // Fallback: create PDO from env variables
                $host = $_ENV['DB_HOST'] ?? 'allsto_db';
                $port = $_ENV['DB_PORT'] ?? '3306';
                $dbname = $_ENV['DB_NAME'] ?? 'allsto_db';
                $username = $_ENV['DB_USER'] ?? 'root';
                $password = $_ENV['DB_PASSWORD'] ?? 'root';

                $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
                self::$db = new \PDO($dsn, $username, $password);
                self::$db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
                self::$db->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
                self::$db->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
            }
        }

        return self::$db;
    }

    /**
     * Set PDO connection (for DI)
     */
    public static function setDb(\PDO $pdo): void
    {
        self::$db = $pdo;
    }

    /**
     * Magic getter for attributes
     */
    public function __get(string $name)
    {
        if (isset($this->attributes[$name])) {
            return $this->attributes[$name];
        }

        // Check for relation getter
        $getter = 'get' . str_replace(' ', '', ucwords(str_replace('_', ' ', $name)));
        if (method_exists($this, $getter)) {
            return $this->$getter();
        }

        return null;
    }

    /**
     * Magic setter for attributes
     */
    public function __set(string $name, $value): void
    {
        $this->attributes[$name] = $value;
    }

    /**
     * Magic isset for attributes
     */
    public function __isset(string $name): bool
    {
        return isset($this->attributes[$name]);
    }

    /**
     * Get attribute value
     */
    public function getAttribute(string $name)
    {
        return $this->attributes[$name] ?? null;
    }

    /**
     * Set attribute value
     */
    public function setAttribute(string $name, $value): void
    {
        $this->attributes[$name] = $value;
    }

    /**
     * Get all attributes
     */
    public function getAttributes(array $names = [], array $except = []): array
    {
        $attributes = $this->attributes;

        if (!empty($names)) {
            $attributes = array_intersect_key($attributes, array_flip($names));
        }

        if (!empty($except)) {
            $attributes = array_diff_key($attributes, array_flip($except));
        }

        return $attributes;
    }

    /**
     * Set multiple attributes
     */
    public function setAttributes(array $values): void
    {
        foreach ($values as $name => $value) {
            $this->attributes[$name] = $value;
        }
    }

    /**
     * Populate model from database row
     */
    public static function populateRecord(array $row): static
    {
        $model = new static();
        $model->attributes = $row;
        $model->oldAttributes = $row;
        $model->isNewRecord = false;
        return $model;
    }

    /**
     * Check if record is new
     */
    public function getIsNewRecord(): bool
    {
        return $this->isNewRecord;
    }

    /**
     * Convert to array for API responses
     */
    public function toArray(array $fields = [], array $expand = [], bool $recursive = true): array
    {
        return $this->getAttributes();
    }

    /**
     * Relation helper: hasMany
     */
    protected function hasMany(string $class, array $link): ActiveQueryInterface
    {
        $query = $class::find();

        foreach ($link as $foreignKey => $primaryKey) {
            if (isset($this->attributes[$primaryKey])) {
                $query->where([$foreignKey => $this->attributes[$primaryKey]]);
            }
        }

        return $query;
    }

    /**
     * Relation helper: hasOne
     */
    protected function hasOne(string $class, array $link): ActiveQueryInterface
    {
        return $this->hasMany($class, $link);
    }
}
