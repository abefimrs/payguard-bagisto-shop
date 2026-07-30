<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Category\Repositories\CategoryRepository;
use Webkul\Core\Models\Channel;
use Webkul\Inventory\Models\InventorySource;
use Webkul\Product\Repositories\ProductRepository;

/**
 * BDShop demo data — categories and sample products for a Bangladesh
 * storefront, priced in BDT.
 *
 * Run with:
 *   php artisan db:seed --class=Database\\Seeders\\BdShopDemoDataSeeder
 *
 * Safe to re-run: SKUs and category slugs are checked before creating,
 * so running it twice won't create duplicates.
 *
 * NOTE ON VERIFICATION: this was written directly against Bagisto's public
 * CategoryRepository/ProductRepository create/update contract and confirmed
 * community usage patterns, but was not executed against a live Bagisto
 * instance (no PHP runtime was available while building it). Run it on a
 * staging copy first. If a field name doesn't match your exact version,
 * the error will name the offending key — compare against Admin →
 * Catalog → an existing product's edit-save request, or open an issue
 * comment where noted below.
 */
class BdShopDemoDataSeeder extends Seeder
{
    protected int $familyId;

    protected int $channelId;

    protected string $locale = 'en';

    protected ?int $inventorySourceId = null;

    public function run(CategoryRepository $categoryRepository, ProductRepository $productRepository): void
    {
        $family = AttributeFamily::where('code', 'default')->first();
        $channel = Channel::first();
        $inventorySource = InventorySource::first();

        if (! $family || ! $channel) {
            $this->command?->error('Could not find a default attribute family or channel — run this after `php artisan bagisto:install`.');

            return;
        }

        $this->familyId = $family->id;
        $this->channelId = $channel->id;
        $this->inventorySourceId = $inventorySource?->id;

        $rootCategoryId = $channel->root_category_id;

        // ---- Categories -----------------------------------------------------
        $categoryTree = [
            'fashion' => [
                'name' => 'Fashion',
                'children' => [
                    'sarees'          => 'Sarees',
                    'panjabi'         => 'Panjabi & Kurta',
                    'salwar-kameez'   => 'Salwar Kameez',
                    'kids-wear'       => "Kids' Wear",
                ],
            ],
            'electronics' => [
                'name' => 'Electronics',
                'children' => [
                    'mobile-accessories' => 'Mobile Accessories',
                    'home-appliances'    => 'Home Appliances',
                ],
            ],
            'grocery' => [
                'name' => 'Grocery & Food',
                'children' => [
                    'spices-masala'   => 'Spices & Masala',
                    'snacks-bakery'   => 'Snacks & Bakery',
                ],
            ],
            'home-living' => [
                'name' => 'Home & Living',
                'children' => [
                    'kitchen'  => 'Kitchen & Dining',
                    'bedding'  => 'Bedding & Linen',
                ],
            ],
            'health-beauty' => [
                'name' => 'Health & Beauty',
                'children' => [
                    'skincare'  => 'Skincare',
                    'haircare'  => 'Haircare',
                ],
            ],
        ];

        $categoryIds = [];

        foreach ($categoryTree as $topSlug => $top) {
            $topId = $this->findOrCreateCategory($categoryRepository, $topSlug, $top['name'], $rootCategoryId);
            $categoryIds[$topSlug] = $topId;

            foreach ($top['children'] as $childSlug => $childName) {
                $categoryIds[$childSlug] = $this->findOrCreateCategory($categoryRepository, $childSlug, $childName, $topId);
            }
        }

        $this->command?->info('Categories ready: '.implode(', ', array_keys($categoryIds)));

        // ---- Products ---------------------------------------------------
        // price is in BDT (store currency). Adjust default currency under
        // Admin → Configuration → General → Locale if not already BDT.
        $products = [
            [
                'sku' => 'BD-SAREE-TNG-001',
                'name' => 'Handloom Tangail Cotton Saree',
                'price' => 2450,
                'weight' => 0.6,
                'category' => 'sarees',
                'short_description' => 'Traditional Tangail handloom cotton saree with woven border, breathable for daily wear.',
                'description' => 'This handloom cotton saree is woven by artisans using the traditional Tangail technique, known for its light weight and comfortable drape. Suitable for both daily wear and festive occasions. Length: 6.5 yards with blouse piece.',
            ],
            [
                'sku' => 'BD-SAREE-JAM-002',
                'name' => 'Jamdani Silk-Blend Saree',
                'price' => 6800,
                'weight' => 0.7,
                'category' => 'sarees',
                'short_description' => 'Hand-woven Jamdani motif saree, silk-cotton blend, ideal for weddings and festivals.',
                'description' => 'A hand-woven Jamdani saree featuring intricate traditional motifs on a silk-cotton blend base. Each piece takes several days to weave and carries slight natural variation, a mark of genuine handwork.',
            ],
            [
                'sku' => 'BD-PANJ-CTN-001',
                'name' => 'Cotton Panjabi — Eid Collection',
                'price' => 1350,
                'weight' => 0.35,
                'category' => 'panjabi',
                'short_description' => 'Regular-fit cotton panjabi with mandarin collar, available in pastel shades.',
                'description' => 'Soft, breathable cotton panjabi cut for a comfortable regular fit. Mandarin collar with matched button placket. Machine washable.',
            ],
            [
                'sku' => 'BD-PANJ-LIN-002',
                'name' => 'Linen Panjabi — Semi-Fitted',
                'price' => 1950,
                'weight' => 0.3,
                'category' => 'panjabi',
                'short_description' => 'Premium linen panjabi, semi-fitted cut, breathable for humid weather.',
                'description' => 'A premium linen panjabi with a semi-fitted silhouette, designed to stay cool and comfortable through Dhaka summers. Pairs well with pajama or churidar.',
            ],
            [
                'sku' => 'BD-SK-COTTON-001',
                'name' => 'Cotton Salwar Kameez — 3 Piece',
                'price' => 2200,
                'weight' => 0.5,
                'category' => 'salwar-kameez',
                'short_description' => 'Unstitched 3-piece cotton salwar kameez set with dupatta.',
                'description' => 'Unstitched 3-piece set: kameez fabric, salwar fabric, and matching dupatta, in printed cotton. Tailor to your preferred fit.',
            ],
            [
                'sku' => 'BD-KID-FROCK-001',
                'name' => "Kids' Cotton Frock",
                'price' => 850,
                'weight' => 0.2,
                'category' => 'kids-wear',
                'short_description' => 'Soft cotton frock for girls, ages 3–8, available in multiple colours.',
                'description' => 'Comfortable everyday cotton frock for girls. Breathable fabric suitable for daily wear and school events. Available in sizes for ages 3 to 8.',
            ],
            [
                'sku' => 'BD-ELEC-EARBUD-001',
                'name' => 'Wireless Earbuds — 20H Battery',
                'price' => 1450,
                'weight' => 0.05,
                'category' => 'mobile-accessories',
                'short_description' => 'True wireless earbuds with charging case, up to 20 hours combined battery life.',
                'description' => 'Bluetooth 5.3 true wireless earbuds with touch controls, IPX4 splash resistance, and a compact charging case delivering up to 20 hours of combined playback.',
            ],
            [
                'sku' => 'BD-ELEC-POWERBANK-001',
                'name' => 'Power Bank 10000mAh',
                'price' => 1150,
                'weight' => 0.22,
                'category' => 'mobile-accessories',
                'short_description' => 'Slim 10000mAh power bank with dual USB output and fast charging support.',
                'description' => 'Compact 10000mAh power bank with dual USB-A output ports and USB-C input, supporting fast charging for most phones. Includes LED charge indicator.',
            ],
            [
                'sku' => 'BD-ELEC-KETTLE-001',
                'name' => 'Electric Kettle 1.7L',
                'price' => 1650,
                'weight' => 1.1,
                'category' => 'home-appliances',
                'short_description' => 'Stainless steel electric kettle, 1.7L capacity, auto shut-off.',
                'description' => 'Stainless steel 1.7L electric kettle with auto shut-off and boil-dry protection. 220V, suitable for standard BD household outlets.',
            ],
            [
                'sku' => 'BD-GRO-MASALA-001',
                'name' => 'Mixed Curry Masala 200g',
                'price' => 180,
                'weight' => 0.2,
                'category' => 'spices-masala',
                'short_description' => 'Traditional blended curry masala, 200g pack, no added colour.',
                'description' => 'A traditional blend of roasted and ground spices for everyday curry preparation. No artificial colour added. 200g resealable pack.',
            ],
            [
                'sku' => 'BD-GRO-GHEE-001',
                'name' => 'Pure Cow Ghee 500ml',
                'price' => 650,
                'weight' => 0.55,
                'category' => 'spices-masala',
                'short_description' => 'Pure cow ghee, slow-cooked, 500ml glass jar.',
                'description' => 'Slow-cooked pure cow ghee with rich aroma, packed in a 500ml glass jar. Suitable for cooking and traditional sweets.',
            ],
            [
                'sku' => 'BD-GRO-SNACK-001',
                'name' => 'Handmade Nimki 400g',
                'price' => 220,
                'weight' => 0.4,
                'category' => 'snacks-bakery',
                'short_description' => 'Crispy handmade nimki, savoury tea-time snack, 400g pack.',
                'description' => 'Crispy, savoury nimki made fresh in small batches — a classic Bangladeshi tea-time snack. 400g resealable pack.',
            ],
            [
                'sku' => 'BD-HOME-DINNERSET-001',
                'name' => 'Melamine Dinner Set — 18 Piece',
                'price' => 2850,
                'weight' => 3.2,
                'category' => 'kitchen',
                'short_description' => '18-piece melamine dinner set, chip-resistant, for 6 people.',
                'description' => 'Durable, chip-resistant melamine dinner set for 6: plates, bowls, and serving dishes. Dishwasher safe, lightweight.',
            ],
            [
                'sku' => 'BD-HOME-BEDSHEET-001',
                'name' => 'Cotton Bedsheet Set — King Size',
                'price' => 1450,
                'weight' => 1.0,
                'category' => 'bedding',
                'short_description' => '100% cotton king-size bedsheet with two pillow covers.',
                'description' => '100% cotton king-size bedsheet set including two matching pillow covers. Soft-wash finish, fade-resistant print.',
            ],
            [
                'sku' => 'BD-BEAUTY-SUNSCREEN-001',
                'name' => 'Daily Sunscreen SPF 50 PA+++',
                'price' => 590,
                'weight' => 0.06,
                'category' => 'skincare',
                'short_description' => 'Lightweight, non-greasy SPF 50 sunscreen suited for humid climates.',
                'description' => 'Lightweight, non-greasy daily sunscreen with SPF 50 PA+++ protection, formulated to sit comfortably under makeup in humid conditions.',
            ],
            [
                'sku' => 'BD-BEAUTY-HAIROIL-001',
                'name' => 'Coconut Hair Oil 200ml',
                'price' => 320,
                'weight' => 0.22,
                'category' => 'haircare',
                'short_description' => 'Cold-pressed coconut hair oil, 200ml, for daily use.',
                'description' => 'Cold-pressed pure coconut hair oil for daily nourishment. Lightweight, non-sticky finish. 200ml bottle.',
            ],
        ];

        foreach ($products as $productData) {
            $this->findOrCreateProduct($productRepository, $productData, $categoryIds[$productData['category']]);
        }

        $this->command?->info('Seeded '.count($products).' sample BD products.');
    }

    protected function findOrCreateCategory(CategoryRepository $categoryRepository, string $slug, string $name, ?int $parentId): int
    {
        $existing = $categoryRepository->findOneWhere(['slug' => $slug]);

        if ($existing) {
            return $existing->id;
        }

        $category = $categoryRepository->create([
            'locale'           => 'all',
            'name'             => $name,
            'slug'             => $slug,
            'status'           => 1,
            'position'         => 1,
            'display_mode'     => 'products_and_description',
            'description'      => '',
            'parent_id'        => $parentId,
            'meta_title'       => $name,
            'meta_description' => $name.' — BDShop',
            'meta_keywords'    => $name,
        ]);

        return $category->id;
    }

    protected function findOrCreateProduct(ProductRepository $productRepository, array $data, int $categoryId): void
    {
        if ($productRepository->findOneWhere(['sku' => $data['sku']])) {
            return;
        }

        $product = $productRepository->create([
            'type'                => 'simple',
            'attribute_family_id' => $this->familyId,
            'sku'                 => $data['sku'],
        ]);

        $updatePayload = [
            'locale'             => $this->locale,
            'channel'            => $this->channelId,
            'channels'           => [$this->channelId],
            'url_key'            => \Illuminate\Support\Str::slug($data['sku']),
            'status'             => 1,
            'visible_individually' => 1,
            'new'                => 1,
            'featured'           => 0,
            'guest_checkout'     => 1,
            'name'               => $data['name'],
            'price'              => $data['price'],
            'weight'             => $data['weight'],
            'short_description'  => $data['short_description'],
            'description'        => $data['description'],
            'meta_title'         => $data['name'],
            'meta_description'   => $data['short_description'],
            'categories'         => [$categoryId],
        ];

        if ($this->inventorySourceId) {
            $updatePayload['inventories'] = [
                $this->inventorySourceId => 50,
            ];
        }

        $productRepository->update($updatePayload, $product->id);
    }
}
