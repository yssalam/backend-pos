<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_deducts_stock_and_records_sale_movement(): void
    {
        $user = User::factory()->create();
        $category = Category::create(['name' => 'Minuman', 'desc' => null]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Kopi',
            'sku' => 'KOPI-01',
            'price' => 12000,
            'stock' => 5,
            'desc' => null,
        ]);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/sales', [
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
            'payment_method' => 'cash',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.total', 24000)
            ->assertJsonPath('data.items_count', 2);
        $this->assertSame(3, $product->fresh()->stock);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'user_id' => $user->id,
            'type' => 'OUT',
            'quantity' => 2,
            'note' => 'Penjualan',
        ]);
    }

    public function test_checkout_rejects_insufficient_stock_without_partial_changes(): void
    {
        $user = User::factory()->create();
        $category = Category::create(['name' => 'Minuman', 'desc' => null]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Kopi',
            'sku' => 'KOPI-02',
            'price' => 12000,
            'stock' => 1,
            'desc' => null,
        ]);

        $this->actingAs($user, 'sanctum')->postJson('/api/sales', [
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
            'payment_method' => 'cash',
        ])->assertUnprocessable();

        $this->assertSame(1, $product->fresh()->stock);
        $this->assertSame(0, StockMovement::count());
    }
}
