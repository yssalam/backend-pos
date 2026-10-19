<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{

    public function stockIn(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'note' => ['nullable', 'string'],
        ]);

        $userId = $request->user()->id;

        $result = DB::transaction(function () use ($validated, $userId) {
            $product = Product::findOrFail($validated['product_id']);

            $product->increment('stock', $validated['quantity']);

            $movement = StockMovement::create([
                'product_id' => $product->id,
                'user_id' => $userId,
                'type' => 'IN',
                'quantity' => $validated['quantity'],
                'note' => $validated['note'] ?? null,
            ]);

            return [
                'product' => $product->fresh(),
                'movement' => $movement->load('user'),
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Stock berhasil ditambahkan',
            'data' => $result,
        ], 201);
    }

    public function stockOut(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'note' => ['nullable', 'string'],
        ]);

        $userId = $request->user()->id;

        $result = DB::transaction(function () use ($validated, $userId) {
            $product = Product::whereKey($validated['product_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if ($product->stock < $validated['quantity']) {
                abort(422, 'Stock tidak mencukupi');
            }

            $product->decrement('stock', $validated['quantity']);

            $movement = StockMovement::create([
                'product_id' => $product->id,
                'user_id' => $userId,
                'type' => 'OUT',
                'quantity' => $validated['quantity'],
                'note' => $validated['note'] ?? null,
            ]);

            return [
                'product' => $product->fresh(),
                'movement' => $movement,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Stock keluar berhasil',
            'data' => $result,
        ], 201);
    }

    public function adjustment(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'stock' => ['required', 'integer', 'min:0'],
            'note' => ['required', 'string'],
        ]);

        $userId = $request->user()->id;

        $result = DB::transaction(function () use ($validated, $userId) {
            $product = Product::whereKey($validated['product_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $currentStock = $product->stock;
            $actualStock = $validated['stock'];
            $difference = $actualStock - $currentStock;

            $product->update([
                'stock' => $actualStock,
            ]);

            $movement = StockMovement::create([
                'product_id' => $product->id,
                'user_id' => $userId,
                'type' => 'ADJUSTMENT',
                'quantity' => $difference,
                'note' => $validated['note'],
            ]);

            return [
                'product' => $product->fresh(),
                'movement' => $movement,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Stock berhasil di perbarui',
            'data' => $result,
        ]);
    }

    public function movements(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['nullable', 'in:IN,OUT,ADJUSTMENT'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $movements = StockMovement::with([
            'product:id,name,sku,price,stock',
            'user:id,name',
        ])
            ->when(
                $validated['type'] ?? null,
                fn($query, $type) => $query->where('type', $type)
            )
            ->when(
                $validated['from'] ?? null,
                fn($query, $from) => $query->whereDate('created_at', '>=', $from)
            )
            ->when(
                $validated['to'] ?? null,
                fn($query, $to) => $query->whereDate('created_at', '<=', $to)
            )
            ->latest()
            ->paginate($validated['per_page'] ?? 10);

        return response()->json([
            'success' => true,
            'data' => $movements,
        ]);
    }

    public function productMovements(Product $product): JsonResponse
    {
        $movements = $product->stockMovements()
            ->with('user:id,name')
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $movements,
        ]);
    }


}