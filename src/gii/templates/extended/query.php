<?php
namespace <?= $generator->queryNs ?>;

class <?= $className ?> extends <?= '\\' . ltrim($generator->queryBaseClass, '\\') ?>
{
    public function all($db = null)
    {
        return parent::all($db);
    }

    public function one($db = null)
    {
        return parent::one($db);
    }
}
