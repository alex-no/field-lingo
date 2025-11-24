# FieldLingo Symfony Adapter

This directory contains examples and documentation for using FieldLingo with Symfony and Doctrine ORM.

## Installation

```bash
composer require alex-no/field-lingo
```

## Configuration

### 1. Create configuration file

Create `config/packages/field_lingo.yaml`:

```yaml
parameters:
    field_lingo:
        default:
            localizedPrefixes: ['@@']
            isStrict: true
            defaultLanguage: 'en'
```

### 2. Create Entity

```php
<?php
use Doctrine\ORM\Mapping as ORM;
use FieldLingo\Adapters\Symfony\LingoEntity;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
class Product extends LingoEntity
{
    #[ORM\Column(type: 'string')]
    private ?string $name_en = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $name_uk = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description_en = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description_uk = null;

    // Getters and setters...
}
```

### 3. Create Repository

```php
<?php
use FieldLingo\Adapters\Symfony\LingoRepository;

class ProductRepository extends LingoRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

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
}
```

## Usage Examples

### Basic Entity Usage

```php
// Create product
$product = new Product();
$product->setCurrentLocale('en');
$product->{'@@name'} = 'Laptop';
$product->{'@@description'} = 'High-performance laptop';

// Switch locale
$product->setCurrentLocale('uk');
$product->{'@@name'} = 'Ноутбук';
$product->{'@@description'} = 'Високопродуктивний ноутбук';

// Save
$entityManager->persist($product);
$entityManager->flush();
```

### Reading Localized Data

```php
$product->setCurrentLocale($request->getLocale());

// Using magic getters
echo $product->{'@@name'};
echo $product->{'@@description'};

// Using helper methods
echo $product->getLocalized('@@name');
echo $product->getLocalized('@@description');
```

### Repository Queries

```php
// Find by localized field
$products = $productRepository
    ->setLocale('uk')
    ->findByName('ноутбук');

// Custom query
$products = $productRepository
    ->setLocale('en')
    ->createQueryBuilder('p')
    ->where('p.@@name LIKE :search')
    ->andWhere('p.price > :minPrice')
    ->setParameter('search', '%laptop%')
    ->setParameter('minPrice', 100)
    ->orderBy('p.@@name', 'ASC')
    ->getQuery()
    ->getResult();
```

### Controller Usage

```php
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class ProductController extends AbstractController
{
    #[Route('/products/{id}')]
    public function show(
        int $id,
        ProductRepository $repository,
        Request $request
    ): JsonResponse {
        $locale = $request->getLocale();
        $product = $repository->find($id);
        $product->setCurrentLocale($locale);

        return $this->json([
            'id' => $product->getId(),
            'name' => $product->{'@@name'},
            'description' => $product->{'@@description'},
            'price' => $product->getPrice(),
        ]);
    }

    #[Route('/products')]
    public function list(
        ProductRepository $repository,
        Request $request
    ): JsonResponse {
        $locale = $request->getLocale();
        $products = $repository->findAllWithLocalizedFields($locale);

        return $this->json($products);
    }
}
```

### Twig Templates

```twig
{# Set current locale on entity #}
{% set product.currentLocale = app.request.locale %}

<div class="product">
    <h1>{{ product.getLocalized('@@name') }}</h1>
    <p>{{ product.getLocalized('@@description') }}</p>
    <p class="price">${{ product.price }}</p>
</div>

{# Or using attribute function #}
<h1>{{ attribute(product, '@@name') }}</h1>
```

### Advanced Query Builder

```php
$qb = $repository->setLocale('en')->createQueryBuilder('p');

$results = $qb
    ->select('p.id', 'p.@@name as productName', 'p.@@description', 'p.price')
    ->where('p.@@description LIKE :search')
    ->andWhere('p.price BETWEEN :min AND :max')
    ->setParameter('search', '%professional%')
    ->setParameter('min', 500)
    ->setParameter('max', 2000)
    ->orderBy('p.@@name', 'ASC')
    ->addOrderBy('p.price', 'DESC')
    ->setMaxResults(20)
    ->getQuery()
    ->getResult();
```

## Features

- **Magic getters/setters**: Access localized fields using `$entity->{'@@field'}`
- **Repository support**: Query localized fields in DQL using `@@` prefix
- **QueryBuilder integration**: Full support for all QueryBuilder methods
- **Locale switching**: Easy runtime locale changes
- **Strict/non-strict modes**: Choose whether to throw exceptions for missing fields
- **Multiple prefixes**: Configure multiple prefixes like `@@`, `##`, etc.
- **Fallback support**: Automatic fallback to default language in non-strict mode

## Database Schema

For a product with English and Ukrainian translations:

```sql
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name_en VARCHAR(255) NOT NULL,
    name_uk VARCHAR(255),
    description_en TEXT,
    description_uk TEXT,
    price DECIMAL(10,2) NOT NULL,
    created_at DATETIME NOT NULL
);
```

## Configuration Options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `localizedPrefixes` | array | `['@@']` | Prefixes to identify localized fields |
| `isStrict` | bool | `true` | Throw exception if field not found |
| `defaultLanguage` | string | `'en'` | Fallback language code |

## Requirements

- PHP >= 8.2
- Symfony >= 5.4 or >= 6.0
- Doctrine ORM >= 2.10

## Files in This Directory

- `Product.php` - Example entity with localized fields
- `ProductRepository.php` - Example repository with localized queries
- `usage-example.php` - Comprehensive usage examples
- `README.md` - This file

## See Also

- [Main README](../../README.md)
- [Laravel Examples](../Laravel/)
- [Yii2 Examples](../Yii2/)
