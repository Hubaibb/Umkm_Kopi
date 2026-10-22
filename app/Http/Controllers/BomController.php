<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use App\Models\Category;
use App\Models\Material;
use App\Models\RecipeItem;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BomController extends Controller
{
    public function index(Request $request)
    {
        $categories = Category::where('is_active', true)->orderBy('name')->get();
        $materials  = Material::where('is_active', true)->orderBy('name')->get();
        $menus      = Menu::with(['category', 'recipeItems.material'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $selectedMenuId = $request->get('menu_id', $menus->first()?->id);
        $selectedMenu   = $menus->find($selectedMenuId);

        return view('bom.index', compact('categories', 'materials', 'menus', 'selectedMenu'));
    }

    // ─── Menu CRUD ──────────────────────────────────────────────────────────────

    public function storeMenu(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name'        => 'required|string|max:150',
            'price'       => 'required|numeric|min:0',
            'description' => 'nullable|string|max:1000',
            'is_active'   => 'boolean',
        ]);

        $slug = Str::slug($validated['name']);
        // Pastikan slug unik
        $slugBase = $slug;
        $counter  = 1;
        while (Menu::where('slug', $slug)->exists()) {
            $slug = $slugBase . '-' . $counter++;
        }

        Menu::create([
            ...$validated,
            'slug'       => $slug,
            'is_active'  => $request->boolean('is_active', true),
            'created_by' => auth()->id(),
        ]);

        return back()->with('success', "Menu \"{$validated['name']}\" berhasil ditambahkan.");
    }

    public function updateMenu(Request $request, Menu $menu)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name'        => ['required', 'string', 'max:150'],
            'price'       => 'required|numeric|min:0',
            'description' => 'nullable|string|max:1000',
            'is_active'   => 'boolean',
        ]);

        // Update slug jika nama berubah
        if ($validated['name'] !== $menu->name) {
            $slug     = Str::slug($validated['name']);
            $slugBase = $slug;
            $counter  = 1;
            while (Menu::where('slug', $slug)->where('id', '!=', $menu->id)->exists()) {
                $slug = $slugBase . '-' . $counter++;
            }
            $validated['slug'] = $slug;
        }

        $menu->update([
            ...$validated,
            'is_active'  => $request->boolean('is_active', true),
            'updated_by' => auth()->id(),
        ]);

        return back()->with('success', "Menu \"{$menu->name}\" berhasil diperbarui.");
    }

    public function destroyMenu(Menu $menu)
    {
        $menu->update(['is_active' => false, 'updated_by' => auth()->id()]);
        return back()->with('success', "Menu \"{$menu->name}\" berhasil dinonaktifkan.");
    }

    // ─── BOM / RecipeItem CRUD ──────────────────────────────────────────────────

    public function storeBomItem(Request $request)
    {
        $validated = $request->validate([
            'menu_id'              => 'required|exists:menus,id',
            'material_id'          => 'required|exists:materials,id',
            'quantity_per_serving' => 'required|numeric|min:0.001',
        ]);

        // Cek duplikasi bahan dalam menu yang sama
        if (RecipeItem::where('menu_id', $validated['menu_id'])
            ->where('material_id', $validated['material_id'])
            ->exists()) {
            return back()->withErrors(['material_id' => 'Bahan ini sudah ada dalam resep menu tersebut. Ubah takarannya jika perlu.']);
        }

        RecipeItem::create([
            ...$validated,
            'created_by' => auth()->id(),
        ]);

        return back()->with('success', 'Bahan berhasil ditambahkan ke resep.');
    }

    public function updateBomItem(Request $request, RecipeItem $recipeItem)
    {
        $validated = $request->validate([
            'quantity_per_serving' => 'required|numeric|min:0.001',
        ]);

        $recipeItem->update([
            ...$validated,
            'updated_by' => auth()->id(),
        ]);

        return back()->with('success', 'Takaran bahan berhasil diperbarui.');
    }

    public function destroyBomItem(RecipeItem $recipeItem)
    {
        $materialName = $recipeItem->material->name;
        $recipeItem->delete();
        return back()->with('success', "Bahan \"{$materialName}\" berhasil dihapus dari resep.");
    }

    // ─── Kategori CRUD ─────────────────────────────────────────────────────────

    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:categories,name',
        ]);

        Category::create([
            'name'      => $validated['name'],
            'slug'      => Str::slug($validated['name']),
            'is_active' => true,
        ]);

        return back()->with('success', "Kategori \"{$validated['name']}\" berhasil ditambahkan.");
    }

    // ─── Simulasi BOM ──────────────────────────────────────────────────────────

    public function simulate(Request $request)
    {
        $validated = $request->validate([
            'menu_id'  => 'required|exists:menus,id',
            'quantity' => 'required|integer|min:1|max:9999',
        ]);

        $menu  = Menu::with('recipeItems.material')->findOrFail($validated['menu_id']);
        $qty   = (int) $validated['quantity'];
        $items = [];

        foreach ($menu->recipeItems as $ri) {
            $needed = $ri->quantity_per_serving * $qty;
            $items[] = [
                'material_name' => $ri->material->name,
                'unit'          => $ri->material->unit,
                'needed'        => $needed,
                'available'     => (float) $ri->material->current_stock,
                'sufficient'    => $ri->material->current_stock >= $needed,
            ];
        }

        return response()->json([
            'menu_name' => $menu->name,
            'quantity'  => $qty,
            'items'     => $items,
        ]);
    }
}
