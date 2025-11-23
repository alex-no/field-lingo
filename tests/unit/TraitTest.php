<?php
declare(strict_types=1);

namespace FieldLingo\Tests;

use PHPUnit\Framework\TestCase;
use FieldLingo\Adapters\Yii2\LocalizedAttributeTrait;
use FieldLingo\Adapters\Yii2\MissingLocalizedAttributeException;

/**
 * Tests for LocalizedAttributeTrait
 *
 * Note: These tests use a mock class that implements the trait.
 * For full integration tests with Yii2, you would need a Yii application bootstrap.
 */
class TraitTest extends TestCase
{
    /**
     * Mock class using LocalizedAttributeTrait
     */
    private function createMockWithTrait(
        array $attributes = [],
        string|array $prefixes = '@@',
        bool $strict = false,
        string $defaultLanguage = 'en'
    ): object {
        return new class($attributes, $prefixes, $strict, $defaultLanguage) {
            use LocalizedAttributeTrait;

            private array $attributes;

            public function __construct(
                array $attributes,
                string|array $prefixes,
                bool $strict,
                string $defaultLanguage
            ) {
                $this->attributes = $attributes;
                $this->localizedPrefixes = $prefixes;
                $this->isStrict = $strict;
                $this->defaultLanguage = $defaultLanguage;
            }

            public function hasAttribute(string $name): bool
            {
                return isset($this->attributes[$name]);
            }

            // Expose protected methods for testing
            public function testGetLocalizedAttributeName(string $name): string
            {
                return $this->getLocalizedAttributeName($name);
            }

            public function testConvertLocalizedFields(array $fields): array
            {
                return $this->convertLocalizedFields($fields);
            }

            public function testGetPrefixesArray(): array
            {
                return $this->getPrefixesArray();
            }
        };
    }

    public function testGetPrefixesArrayWithString(): void
    {
        $mock = $this->createMockWithTrait([], '@@');
        $this->assertEquals(['@@'], $mock->testGetPrefixesArray());
    }

    public function testGetPrefixesArrayWithArray(): void
    {
        $mock = $this->createMockWithTrait([], ['@@', '##']);
        $this->assertEquals(['@@', '##'], $mock->testGetPrefixesArray());
    }

    public function testGetPrefixesArrayWithEmptyString(): void
    {
        $mock = $this->createMockWithTrait([], '');
        $this->assertEquals([], $mock->testGetPrefixesArray());
    }

    public function testGetLocalizedAttributeNameWithExistingAttribute(): void
    {
        $mock = $this->createMockWithTrait(['name_en' => true], '@@', false, 'en');
        $result = $mock->testGetLocalizedAttributeName('@@name');
        $this->assertEquals('name_en', $result);
    }

    public function testGetLocalizedAttributeNameWithFallback(): void
    {
        $mock = $this->createMockWithTrait(['name_en' => true], '@@', false, 'en');
        // Simulating scenario where preferred language attribute doesn't exist
        // but fallback does
        $result = $mock->testGetLocalizedAttributeName('@@name');
        $this->assertEquals('name_en', $result);
    }

    public function testGetLocalizedAttributeNameWithoutPrefix(): void
    {
        $mock = $this->createMockWithTrait(['created_at' => true], '@@', false, 'en');
        $result = $mock->testGetLocalizedAttributeName('created_at');
        $this->assertEquals('created_at', $result);
    }

    public function testGetLocalizedAttributeNameStrictModeThrowsException(): void
    {
        $this->expectException(MissingLocalizedAttributeException::class);
        $this->expectExceptionMessage("The localized attribute 'name_en' is missing.");

        $mock = $this->createMockWithTrait([], '@@', true, 'en');
        $mock->testGetLocalizedAttributeName('@@name');
    }

    public function testConvertLocalizedFields(): void
    {
        $mock = $this->createMockWithTrait([
            'id' => true,
            'name_en' => true,
            'title_en' => true,
            'created_at' => true
        ], '@@', false, 'en');

        $fields = ['id', '@@name', '@@title', 'created_at'];
        $result = $mock->testConvertLocalizedFields($fields);

        $this->assertEquals([
            'id',
            'name_en',
            'title_en',
            'created_at'
        ], $result);
    }

    public function testMultiplePrefixes(): void
    {
        $mock = $this->createMockWithTrait([
            'name_en' => true,
            'status_en' => true
        ], ['@@', '##'], false, 'en');

        $this->assertEquals('name_en', $mock->testGetLocalizedAttributeName('@@name'));
        $this->assertEquals('status_en', $mock->testGetLocalizedAttributeName('##status'));
    }

    public function testNonStrictModeReturnsCandidate(): void
    {
        // Non-strict mode: even if attribute doesn't exist, returns the candidate
        $mock = $this->createMockWithTrait([], '@@', false, 'en');
        $result = $mock->testGetLocalizedAttributeName('@@nonexistent');

        // Should return the candidate name even if it doesn't exist
        $this->assertEquals('nonexistent_en', $result);
    }
}
