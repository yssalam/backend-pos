<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class CategoryProductSeeder extends Seeder
{
    public function run(): void
    {
        $catalog = [
            'Minuman' => [
                ['name' => 'Air Mineral 600ml', 'sku' => 'MIN-001', 'price' => 4000, 'stock' => 60, 'desc' => 'Air mineral botol 600ml'],
                ['name' => 'Teh Botol', 'sku' => 'MIN-002', 'price' => 5000, 'stock' => 40, 'desc' => 'Teh siap minum'],
                ['name' => 'Kopi Susu', 'sku' => 'MIN-003', 'price' => 12000, 'stock' => 30, 'desc' => 'Kopi susu siap minum'],
                ['name' => 'Coco Drink', 'sku' => 'MIN-004', 'price' => 7000, 'stock' => 35, 'desc' => 'Minuman kelapa'],
            ],
            'Makanan' => [
                ['name' => 'Roti Cokelat', 'sku' => 'MAK-001', 'price' => 8000, 'stock' => 25, 'desc' => 'Roti isi cokelat'],
                ['name' => 'Keripik Singkong', 'sku' => 'MAK-002', 'price' => 10000, 'stock' => 20, 'desc' => 'Keripik singkong gurih'],
                ['name' => 'Mi Instan', 'sku' => 'MAK-003', 'price' => 3500, 'stock' => 50, 'desc' => 'Mi instan kemasan'],
            ],
            'Kebutuhan Harian' => [
                ['name' => 'Sabun Mandi', 'sku' => 'HAR-001', 'price' => 6000, 'stock' => 20, 'desc' => 'Sabun mandi batang'],
                ['name' => 'Tisu Wajah', 'sku' => 'HAR-002', 'price' => 9000, 'stock' => 15, 'desc' => 'Tisu wajah kemasan'],
                ['name' => 'Baterai AA', 'sku' => 'HAR-003', 'price' => 5000, 'stock' => 24, 'desc' => 'Baterai AA satuan'],
            ],
        ];

        foreach ($catalog as $categoryName => $products) {
            $category = Category::updateOrCreate(
                ['name' => $categoryName],
                ['desc' => "Kategori $categoryName"]
            );

            foreach ($products as $product) {
                Product::updateOrCreate(
                    ['sku' => $product['sku']],
                    [
                        ...$product,
                        'category_id' => $category->id,
                    ]
                );
            }
        }
    }
}
