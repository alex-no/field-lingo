<?php
declare(strict_types=1);

namespace FieldLingo\Tests;

use PHPUnit\Framework\TestCase;
use FieldLingo\Core\Localizer;
use FieldLingo\Core\Contracts\ConfigInterface;

/**
 * Tests for Core\Localizer class
 */
class LocalizerTest extends TestCase
{
    private function createConfig(
        array $prefixes = ['@@'],
        string $defaultLanguage = 'en',
        bool $strict = false
    ): ConfigInterface {
        return new class($prefixes, $defaultLanguage, $strict) implements ConfigInterface {
            public function __construct(
                private array $prefixes,
                private string $defaultLanguage,
                private bool $strict
            ) {}

            public function getPrefixes(): array
            {
                return $this->prefixes;
            }

            public function getDefaultLanguage(): string
            {
                return $this->defaultLanguage;
            }

            public function isStrict(): bool
            {
                return $this->strict;
            }
        };
    }

    public function testConvertSingleFieldWithSinglePrefix(): void
    {
        $config = $this->createConfig(['@@']);
        $localizer = new Localizer($config);

        $result = $localizer->convert('@@title');
        $this->assertEquals('title_en', $result);
    }

    public function testConvertFieldWithoutPrefixRemainsUnchanged(): void
    {
        $config = $this->createConfig(['@@']);
        $localizer = new Localizer($config);

        $result = $localizer->convert('title');
        $this->assertEquals('title', $result);
    }

    public function testConvertWithMultiplePrefixes(): void
    {
        $config = $this->createConfig(['@@', '##']);
        $localizer = new Localizer($config);

        $result1 = $localizer->convert('@@title');
        $this->assertEquals('title_en', $result1);

        $result2 = $localizer->convert('##name');
        $this->assertEquals('name_en', $result2);
    }

    public function testConvertMixedWithSimpleArray(): void
    {
        $config = $this->createConfig(['@@']);
        $localizer = new Localizer($config);

        $input = ['id', '@@name', '@@title', 'created_at'];
        $result = $localizer->convertMixed($input);

        $this->assertEquals([
            'id',
            'name_en',
            'title_en',
            'created_at'
        ], $result);
    }

    public function testConvertMixedWithAssociativeArray(): void
    {
        $config = $this->createConfig(['@@']);
        $localizer = new Localizer($config);

        $input = [
            '@@title' => 'Hello',
            'status' => 'active',
            '@@category' => 'News'
        ];
        $result = $localizer->convertMixed($input);

        $this->assertEquals([
            'title_en' => 'Hello',
            'status' => 'active',
            'category_en' => 'News'
        ], $result);
    }

    public function testConvertMixedWithNestedArray(): void
    {
        $config = $this->createConfig(['@@']);
        $localizer = new Localizer($config);

        $input = [
            '@@title' => 'Product',
            'conditions' => [
                '@@category' => 'Electronics',
                'price' => ['>', 100]
            ]
        ];
        $result = $localizer->convertMixed($input);

        $this->assertEquals([
            'title_en' => 'Product',
            'conditions' => [
                'category_en' => 'Electronics',
                'price' => ['>', 100]
            ]
        ], $result);
    }

    public function testConvertWithDifferentDefaultLanguage(): void
    {
        $config = $this->createConfig(['@@'], 'uk');
        $localizer = new Localizer($config);

        $result = $localizer->convert('@@title');
        $this->assertEquals('title_uk', $result);
    }

    public function testConvertMixedWithString(): void
    {
        $config = $this->createConfig(['@@']);
        $localizer = new Localizer($config);

        $result = $localizer->convertMixed('@@title');
        $this->assertEquals('title_en', $result);
    }

    public function testConvertMixedWithScalarValues(): void
    {
        $config = $this->createConfig(['@@']);
        $localizer = new Localizer($config);

        $this->assertEquals(123, $localizer->convertMixed(123));
        $this->assertEquals(45.67, $localizer->convertMixed(45.67));
        $this->assertEquals(true, $localizer->convertMixed(true));
        $this->assertEquals(null, $localizer->convertMixed(null));
    }
}
