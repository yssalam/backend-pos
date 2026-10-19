<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\DemoDataStore as Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class DemoApiController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);
        $user = Store::userByEmail($data['email']);
        $passwordMatches = $user && password_verify($data['password'], $user['password']);
        if (!$passwordMatches) {
            return response()->json(['success' => false, 'message' => 'Email atau password salah.'], 401);
        }
        $token = Store::issueToken((int) $user['id']);
        return response()->json(['success' => true, 'message' => 'Login berhasil.', 'data' => ['user' => $this->publicUser($user), 'token' => $token]]);
    }

    public function profile(Request $request): JsonResponse
    {
        $user = $request->attributes->get('demo_user');
        abort_unless(is_array($user), 401, 'Unauthenticated.');
        return response()->json(['success' => true, 'data' => ['user' => $this->publicUser($user)]]);
    }

    public function logout(Request $request): JsonResponse
    {
        Store::revokeToken($request->bearerToken());
        return response()->json(['success' => true, 'message' => 'Logout berhasil.']);
    }

    public function users(): JsonResponse
    {
        return response()->json(['data' => array_map(fn (array $user) => $this->publicUser($user), Store::users())]);
    }

    public function createAdmin(Request $request): JsonResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'email' => ['required', 'email', 'max:255'], 'password' => ['required', 'confirmed', 'min:8'], 'role' => ['required', 'in:admin,cashier']]);
        $user = $this->storeCall(fn () => Store::createUser($data));
        return response()->json(['success' => true, 'message' => 'Akun admin berhasil dibuat.', 'data' => ['user' => $this->publicUser($user)]], 201);
    }

    public function updateUser(Request $request, int $id): JsonResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'email' => ['required', 'email', 'max:255'], 'role' => ['required', 'in:admin,cashier'], 'password' => ['nullable', 'string', 'min:8', 'confirmed']]);
        $user = $this->storeCall(fn () => Store::updateUser($id, $data));
        abort_unless($user, 404);
        return response()->json(['success' => true, 'message' => 'Data pengguna berhasil diperbarui.', 'data' => ['user' => $this->publicUser($user)]]);
    }

    public function products(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => Store::products()]);
    }

    public function product(int $id): JsonResponse
    {
        $product = Store::product($id);
        abort_unless($product, 404);
        return response()->json(['success' => true, 'data' => $product]);
    }

    public function createProduct(Request $request): JsonResponse
    {
        $data = $request->validate(['category_id' => ['required', 'integer'], 'name' => ['required', 'string', 'max:255'], 'sku' => ['required', 'string', 'max:255'], 'price' => ['required', 'numeric', 'min:0'], 'stock' => ['required', 'integer', 'min:0'], 'desc' => ['nullable', 'string']]);
        $product = $this->storeCall(fn () => Store::createProduct($data));
        return response()->json(['success' => true, 'message' => 'Product created successfully', 'data' => $product], 201);
    }

    public function updateProduct(Request $request, int $id): JsonResponse
    {
        $data = $request->validate(['category_id' => ['required', 'integer'], 'name' => ['required', 'string', 'max:255'], 'sku' => ['required', 'string', 'max:255'], 'price' => ['required', 'numeric', 'min:0'], 'stock' => ['required', 'integer', 'min:0'], 'desc' => ['nullable', 'string']]);
        unset($data['stock']);
        $product = $this->storeCall(fn () => Store::updateProduct($id, $data));
        abort_unless($product, 404);
        return response()->json(['success' => true, 'message' => 'Product updated successfully', 'data' => $product]);
    }

    public function deleteProduct(int $id): JsonResponse
    {
        abort_unless(Store::deleteProduct($id), 404);
        return response()->json(['success' => true, 'message' => 'Product deleted successfully']);
    }

    public function categories(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => Store::categories()]);
    }

    public function category(int $id): JsonResponse
    {
        $category = Store::category($id);
        abort_unless($category, 404);
        return response()->json(['success' => true, 'data' => $category]);
    }

    public function createCategory(Request $request): JsonResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'desc' => ['nullable', 'string']]);
        return response()->json(['success' => true, 'message' => 'Category created successfully', 'data' => Store::createCategory($data)], 201);
    }

    public function updateCategory(Request $request, int $id): JsonResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'desc' => ['nullable', 'string']]);
        $category = Store::updateCategory($id, $data);
        abort_unless($category, 404);
        return response()->json(['success' => true, 'message' => 'Category updated successfully', 'data' => $category]);
    }

    public function deleteCategory(int $id): JsonResponse
    {
        $this->storeCall(fn () => Store::deleteCategory($id));
        return response()->json(['success' => true, 'message' => 'Category deleted successfully']);
    }

    public function stockIn(Request $request): JsonResponse
    {
        $data = $request->validate(['product_id' => ['required', 'integer'], 'quantity' => ['required', 'integer', 'min:1'], 'note' => ['nullable', 'string']]);
        $result = $this->storeCall(fn () => Store::stockIn($data['product_id'], $data['quantity'], $this->userId($request), $data['note'] ?? null));
        return response()->json(['success' => true, 'message' => 'Stock berhasil ditambahkan', 'data' => $result], 201);
    }

    public function stockOut(Request $request): JsonResponse
    {
        $data = $request->validate(['product_id' => ['required', 'integer'], 'quantity' => ['required', 'integer', 'min:1'], 'note' => ['nullable', 'string']]);
        $result = $this->storeCall(fn () => Store::stockOut($data['product_id'], $data['quantity'], $this->userId($request), $data['note'] ?? null));
        return response()->json(['success' => true, 'message' => 'Stock keluar berhasil', 'data' => $result], 201);
    }

    public function adjustment(Request $request): JsonResponse
    {
        $data = $request->validate(['product_id' => ['required', 'integer'], 'stock' => ['required', 'integer', 'min:0'], 'note' => ['required', 'string']]);
        $result = $this->storeCall(fn () => Store::adjustment($data['product_id'], $data['stock'], $this->userId($request), $data['note']));
        return response()->json(['success' => true, 'message' => 'Stock berhasil di perbarui', 'data' => $result]);
    }

    public function movements(Request $request): JsonResponse
    {
        $filters = $request->validate(['type' => ['nullable', 'in:IN,OUT,ADJUSTMENT'], 'per_page' => ['nullable', 'integer', 'min:1', 'max:100'], 'page' => ['nullable', 'integer', 'min:1'], 'from' => ['nullable', 'date'], 'to' => ['nullable', 'date', 'after_or_equal:from']]);
        $items = Store::movements($filters);
        $perPage = (int) ($filters['per_page'] ?? 10);
        $page = (int) ($filters['page'] ?? 1);
        $total = count($items);
        $lastPage = max(1, (int) ceil($total / $perPage));
        $items = array_slice($items, ($page - 1) * $perPage, $perPage);
        return response()->json(['success' => true, 'data' => ['current_page' => $page, 'data' => $items, 'per_page' => $perPage, 'last_page' => $lastPage, 'total' => $total, 'from' => $total ? (($page - 1) * $perPage + 1) : null, 'to' => $total ? (($page - 1) * $perPage + count($items)) : null]]);
    }

    public function productMovements(int $id): JsonResponse
    {
        abort_unless(Store::product($id), 404);
        return response()->json(['success' => true, 'data' => Store::productMovements($id)]);
    }

    public function sale(Request $request): JsonResponse
    {
        $data = $request->validate(['items' => ['required', 'array', 'min:1'], 'items.*.product_id' => ['required', 'integer', 'distinct'], 'items.*.quantity' => ['required', 'integer', 'min:1'], 'payment_method' => ['required', 'in:cash,qris,transfer']]);
        $sale = $this->storeCall(fn () => Store::checkout($data['items'], $this->userId($request), $data['payment_method']));
        return response()->json(['success' => true, 'message' => 'Transaksi berhasil dicatat dan stok diperbarui.', 'data' => $sale], 201);
    }

    private function userId(Request $request): int
    {
        return (int) $request->attributes->get('demo_user')['id'];
    }

    private function publicUser(array $user): array
    {
        return array_intersect_key($user, array_flip(['id', 'name', 'email', 'role', 'created_at']));
    }

    private function storeCall(callable $callback): mixed
    {
        try {
            return $callback();
        } catch (RuntimeException $exception) {
            if (in_array($exception->getCode(), [403, 404], true)) {
                abort($exception->getCode(), $exception->getMessage());
            }
            if ($exception->getCode() === 422) {
                throw ValidationException::withMessages(['message' => [$exception->getMessage()]]);
            }
            abort(in_array($exception->getCode(), [403, 404], true) ? $exception->getCode() : 500, $exception->getMessage());
        }
    }
}
