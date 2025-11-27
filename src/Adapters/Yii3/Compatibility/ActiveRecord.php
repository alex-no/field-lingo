<?php

declare(strict_types=1);

namespace Yiisoft\ActiveRecord;

/**
 * Compatibility stub for ActiveRecord
 * This is a temporary compatibility layer for alex-no/field-lingo package
 * since Yii3 doesn't have an official ActiveRecord implementation yet.
 *
 * This class provides minimal ActiveRecord-like functionality using Yii3's database layer.
 */
abstract class ActiveRecord
{
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
}
