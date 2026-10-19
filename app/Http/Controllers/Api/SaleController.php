<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'payment_method' => ['required', 'in:cash,qris,transfer'],
        ]);

        $sale = DB::transaction(function () use ($validated, $request) {
            $items = collect($validated['items'])->keyBy('product_id');
            $products = Product::whereIn('id', $items->keys())
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($items as $productId => $item) {
                $product = $products->get($productId);
                if (!$product || $product->stock < $item['quantity']) {
                    $name = $product?->name ?? "produk #{$productId}";
                    throw ValidationException::withMessages([
                        'items' => ["Stok {$name} tidak mencukupi. Muat ulang katalog dan coba lagi."],
                    ]);
                }
            }

            $total = 0;
            $saleItems = [];
            foreach ($items as $productId => $item) {
                $product = $products->get($productId);
                $quantity = $item['quantity'];
                $unitPrice = (float) $product->price;
                $product->decrement('stock', $quantity);
                StockMovement::create([
                    'product_id' => $product->id,
                    'user_id' => $request->user()->id,
                    'type' => 'OUT',
                    'quantity' => $quantity,
                    'note' => 'Penjualan',
                ]);
                $saleItems[] = [
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'subtotal' => $unitPrice * $quantity,
                    'stock' => $product->fresh()->stock,
                ];
                $total += $unitPrice * $quantity;
            }

            return [
                'id' => 'TRX-' . now()->format('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(3))),
                'created_at' => now()->toISOString(),
                'payment_method' => $validated['payment_method'],
                'total' => $total,
                'items_count' => array_sum(array_column($saleItems, 'quantity')),
                'items' => $saleItems,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Transaksi berhasil dicatat dan stok diperbarui.',
            'data' => $sale,
        ], 201);
    }
}
