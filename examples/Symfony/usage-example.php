<?php
declare(strict_types=1);

/**
 * Example usage of FieldLingo with Symfony/Doctrine
 *
 * This file demonstrates various ways to use FieldLingo's localized
 * attribute functionality in a Symfony application.
 */

use App\Entity\Product;
use App\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\Request;

// Example 1: Creating a product with localized attributes
function createProduct($entityManager, string $locale = 'en'): Product
{
    $product = new Product();
    $product->setCurrentLocale($locale);

    // Using magic setters with @@ prefix
    $product->{'@@name'} = 'Laptop';
    $product->{'@@description'} = 'High-performance laptop for professionals';
    $product->setPrice('999.99');

    // Set other language versions
    $product->setCurrentLocale('uk');
    $product->{'@@name'} = 'Ноутбук';
    $product->{'@@description'} = 'Високопродуктивний ноутбук для професіоналів';

    $product->setCurrentLocale('de');
    $product->{'@@name'} = 'Laptop';
    $product->{'@@description'} = 'Hochleistungs-Laptop für Profis';

    $entityManager->persist($product);
    $entityManager->flush();

    return $product;
}

// Example 2: Reading localized attributes
function readProduct(Product $product, string $locale = 'en'): void
{
    $product->setCurrentLocale($locale);

    // Using magic getters
    echo "Name: " . $product->{'@@name'} . "\n";
    echo "Description: " . $product->{'@@description'} . "\n";

    // Using the helper methods
    echo "Name: " . $product->getLocalized('@@name') . "\n";
    echo "Description: " . $product->getLocalized('@@description') . "\n";
}

// Example 3: Querying with localized fields
function queryProducts(ProductRepository $repository, string $locale = 'en'): void
{
    // Find by localized name
    $products = $repository->findByName('Laptop', $locale);

    // Search by description
    $products = $repository->searchByDescription('professional', $locale);

    // Price range with localized sorting
    $products = $repository->findByPriceRange(500, 2000, $locale);

    foreach ($products as $product) {
        $product->setCurrentLocale($locale);
        echo $product->{'@@name'} . ": $" . $product->getPrice() . "\n";
    }
}

// Example 4: Advanced queries with QueryBuilder
function advancedQuery(ProductRepository $repository, string $locale = 'en'): array
{
    return $repository->setLocale($locale)
        ->createQueryBuilder('p')
        ->select('p.id', 'p.@@name as name', 'p.@@description as description', 'p.price')
        ->where('p.price > :minPrice')
        ->andWhere('p.@@name LIKE :search')
        ->setParameter('minPrice', 100)
        ->setParameter('search', '%laptop%')
        ->orderBy('p.@@name', 'ASC')
        ->setMaxResults(10)
        ->getQuery()
        ->getResult();
}

// Example 5: Controller example
class ProductController
{
    public function show(
        int $id,
        ProductRepository $repository,
        Request $request
    ): array {
        // Get locale from request
        $locale = $request->getLocale();

        // Find product
        $product = $repository->find($id);
        $product->setCurrentLocale($locale);

        // Return localized data
        return [
            'id' => $product->getId(),
            'name' => $product->{'@@name'},
            'description' => $product->{'@@description'},
            'price' => $product->getPrice(),
        ];
    }

    public function list(
        ProductRepository $repository,
        Request $request
    ): array {
        $locale = $request->getLocale();

        // Get all products with localized fields
        $products = $repository->findAllWithLocalizedFields($locale);

        return $products;
    }

    public function search(
        string $query,
        ProductRepository $repository,
        Request $request
    ): array {
        $locale = $request->getLocale();

        // Search in localized description
        return $repository->searchByDescription($query, $locale);
    }
}

// Example 6: Using with Symfony Forms
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\FormBuilderInterface;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $locale = $options['locale'] ?? 'en';

        $builder
            ->add("name_{$locale}", TextType::class, [
                'label' => 'Name',
                'mapped' => false,
            ])
            ->add("description_{$locale}", TextareaType::class, [
                'label' => 'Description',
                'mapped' => false,
                'required' => false,
            ])
            ->add('price', MoneyType::class, [
                'label' => 'Price',
            ]);
    }
}

// Example 7: Using in Twig templates
/*
{# In your Twig template #}

{% set locale = app.request.locale %}

<div class="product">
    <h1>{{ product.getLocalized('@@name') }}</h1>
    <p>{{ product.getLocalized('@@description') }}</p>
    <p class="price">${{ product.price }}</p>
</div>

{# Or using magic properties #}
<div class="product">
    {% set product.currentLocale = locale %}
    <h1>{{ attribute(product, '@@name') }}</h1>
    <p>{{ attribute(product, '@@description') }}</p>
    <p class="price">${{ product.price }}</p>
</div>
*/
