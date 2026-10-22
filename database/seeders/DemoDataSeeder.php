<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Menu;
use App\Models\Material;
use App\Models\RecipeItem;
use App\Models\StockMovement;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // ─── Kategori ──────────────────────────────────────────────────────────
        $categories = [
            ['name' => 'Kopi Susu',  'slug' => 'kopi-susu'],
            ['name' => 'Kopi Hitam', 'slug' => 'kopi-hitam'],
            ['name' => 'Non-Kopi',   'slug' => 'non-kopi'],
        ];

        foreach ($categories as $cat) {
            Category::updateOrCreate(['slug' => $cat['slug']], array_merge($cat, ['is_active' => true]));
        }

        // ─── Bahan Baku ────────────────────────────────────────────────────────
        $materials = [
            ['name' => 'Biji Kopi',      'unit' => 'g',    'current_stock' => 2500, 'minimum_stock' => 500],
            ['name' => 'Susu Segar',     'unit' => 'ml',   'current_stock' => 8000, 'minimum_stock' => 1500],
            ['name' => 'Gula Aren Cair', 'unit' => 'ml',   'current_stock' => 1800, 'minimum_stock' => 400],
            ['name' => 'Air',            'unit' => 'ml',   'current_stock' => 15000, 'minimum_stock' => 2000],
            ['name' => 'Lemon',          'unit' => 'buah', 'current_stock' => 8,    'minimum_stock' => 3],
            ['name' => 'Bubuk Cokelat',  'unit' => 'g',    'current_stock' => 900,  'minimum_stock' => 200],
        ];

        foreach ($materials as $mat) {
            $material = Material::updateOrCreate(['name' => $mat['name']], array_merge($mat, ['is_active' => true]));

            // Buat stock movement awal (seeder)
            if (!StockMovement::where('material_id', $material->id)->where('movement_type', 'purchase_in')->exists()) {
                StockMovement::create([
                    'material_id'     => $material->id,
                    'user_id'         => null,
                    'sale_id'         => null,
                    'movement_type'   => 'purchase_in',
                    'quantity_change' => $mat['current_stock'],
                    'stock_before'    => 0,
                    'stock_after'     => $mat['current_stock'],
                    'source'          => 'Seeder Data Demo',
                    'notes'           => 'Stok awal dari data demo',
                    'created_at'      => now(),
                ]);
            }
        }

        // ─── Menu & BOM ────────────────────────────────────────────────────────
        $menus = [
            [
                'name'        => 'Kopi Susu Gula Aren',
                'category'    => 'kopi-susu',
                'price'       => 22000,
                'description' => 'Espresso, susu segar, dan gula aren pilihan',
                'bom'         => [
                    'Biji Kopi'      => 18,
                    'Susu Segar'     => 120,
                    'Gula Aren Cair' => 20,
                ],
            ],
            [
                'name'        => 'Americano',
                'category'    => 'kopi-hitam',
                'price'       => 18000,
                'description' => 'Espresso dan air panas berkualitas',
                'bom'         => [
                    'Biji Kopi' => 18,
                    'Air'       => 150,
                ],
            ],
            [
                'name'        => 'Cafe Latte',
                'category'    => 'kopi-susu',
                'price'       => 24000,
                'description' => 'Espresso lembut dengan susu segar',
                'bom'         => [
                    'Biji Kopi'  => 18,
                    'Susu Segar' => 180,
                ],
            ],
            [
                'name'        => 'Teh Lemon',
                'category'    => 'non-kopi',
                'price'       => 16000,
                'description' => 'Teh segar dengan perasan lemon',
                'bom'         => [
                    'Air'   => 200,
                    'Lemon' => 0.5,
                ],
            ],
            [
                'name'        => 'Cokelat',
                'category'    => 'non-kopi',
                'price'       => 20000,
                'description' => 'Bubuk cokelat premium dan susu segar',
                'bom'         => [
                    'Bubuk Cokelat' => 25,
                    'Susu Segar'    => 150,
                ],
            ],
        ];

        foreach ($menus as $menuData) {
            $category = Category::where('slug', $menuData['category'])->first();
            if (!$category) continue;

            $slug = Str::slug($menuData['name']);
            $menu = Menu::updateOrCreate(
                ['slug' => $slug],
                [
                    'category_id' => $category->id,
                    'name'        => $menuData['name'],
                    'slug'        => $slug,
                    'description' => $menuData['description'],
                    'price'       => $menuData['price'],
                    'is_active'   => true,
                ]
            );

            // BOM
            foreach ($menuData['bom'] as $materialName => $qty) {
                $material = Material::where('name', $materialName)->first();
                if (!$material) continue;

                RecipeItem::updateOrCreate(
                    ['menu_id' => $menu->id, 'material_id' => $material->id],
                    ['quantity_per_serving' => $qty]
                );
            }
        }

        $this->command->info('Data demo (kategori, menu, bahan baku, BOM) berhasil di-seed.');
    }
}
