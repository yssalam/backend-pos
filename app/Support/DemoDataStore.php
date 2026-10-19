<?php

namespace App\Support;

use Illuminate\Support\Str;
use RuntimeException;

/** Local JSON-backed demo data with a shared lock for concurrent requests. */
class DemoDataStore
{
    private static function load(): array
    {
        $path = config('demo.data_file');
        if (!is_file($path)) {
            $lock = fopen($path . '.lock', 'c+');
            if (!$lock || !flock($lock, LOCK_EX)) throw new RuntimeException('Tidak dapat mengunci file data demo.');
            try {
                if (!is_file($path)) {
                    $initial = require base_path('database/seeders/DemoData.php');
                    $initial['tokens'] = [];
                    self::write($initial);
                }
            } finally {
                flock($lock, LOCK_UN);
                fclose($lock);
            }
        }

        $contents = file_get_contents($path);
        $data = $contents === false ? null : json_decode($contents, true);
        if (!is_array($data)) {
            throw new RuntimeException('File data demo tidak valid. Hapus storage/app/demo-data.json untuk membuat ulang data demo.');
        }
        $data['tokens'] ??= [];
        return $data;
    }

    private static function write(array $state): void
    {
        $path = config('demo.data_file');
        $directory = dirname($path);
        if (!is_dir($directory)) mkdir($directory, 0775, true);
        $temporaryPath = $path . '.tmp';
        file_put_contents($temporaryPath, json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), LOCK_EX);
        rename($temporaryPath, $path);
    }

    private static function change(callable $callback): mixed
    {
        $path = config('demo.data_file');
        $directory = dirname($path);
        if (!is_dir($directory)) mkdir($directory, 0775, true);
        $lock = fopen($path . '.lock', 'c+');
        if (!$lock || !flock($lock, LOCK_EX)) throw new RuntimeException('Tidak dapat mengunci file data demo.');
        try {
            $state = self::load();
            $result = $callback($state);
            self::write($state);
            return $result;
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    public static function reset(): void
    {
        $state = require base_path('database/seeders/DemoData.php');
        $state['tokens'] = [];
        self::write($state);
    }

    public static function users(): array
    {
        return self::load()['users'];
    }

    public static function createUser(array $data): array
    {
        return self::change(function (array &$state) use ($data): array {
            if (self::findBy($state['users'], 'email', $data['email'])) throw new RuntimeException('Email sudah digunakan.', 422);
            $user = ['id' => self::nextId($state['users']), 'name' => $data['name'], 'email' => $data['email'], 'password' => password_hash($data['password'], PASSWORD_BCRYPT), 'role' => $data['role'], 'created_at' => now()->toIso8601String()];
            $state['users'][] = $user;
            return $user;
        });
    }

    public static function updateUser(int $id, array $data): ?array
    {
        return self::change(function (array &$state) use ($id, $data): ?array {
            foreach ($state['users'] as $index => $user) {
                if ((int) $user['id'] !== $id) continue;
                if ($user['role'] === 'super_admin') throw new RuntimeException('Akun Super Admin tidak dapat diubah dari panel ini.', 403);
                foreach ($state['users'] as $other) if ((int) $other['id'] !== $id && strcasecmp($other['email'], $data['email']) === 0) throw new RuntimeException('Email sudah digunakan.', 422);
                $state['users'][$index]['name'] = $data['name'];
                $state['users'][$index]['email'] = $data['email'];
                $state['users'][$index]['role'] = $data['role'];
                if (!empty($data['password'])) $state['users'][$index]['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
                return $state['users'][$index];
            }
            return null;
        });
    }

    public static function userByEmail(string $email): ?array
    {
        foreach (self::load()['users'] as $user) {
            if (strcasecmp($user['email'], $email) === 0) return $user;
        }
        return null;
    }

    public static function issueToken(int $userId): string
    {
        $token = Str::random(64);
        $hash = hash('sha256', $token);
        self::change(function (array &$state) use ($hash, $userId): void {
            $state['tokens'][$hash] = $userId;
        });
        return $token;
    }

    public static function userForToken(?string $token): ?array
    {
        if (!$token) return null;
        $state = self::load();
        $id = $state['tokens'][hash('sha256', $token)] ?? null;
        foreach ($state['users'] as $user) {
            if ($id !== null && (int) $user['id'] === (int) $id) return $user;
        }
        return null;
    }

    public static function revokeToken(?string $token): void
    {
        if (!$token) return;
        $hash = hash('sha256', $token);
        self::change(function (array &$state) use ($hash): void {
            unset($state['tokens'][$hash]);
        });
    }

    public static function categories(): array
    {
        $state = self::load();
        return array_map(function (array $category) use ($state) {
            $category['products_count'] = count(array_filter($state['products'], fn (array $product) => (int) $product['category_id'] === (int) $category['id']));
            return $category;
        }, $state['categories']);
    }

    public static function category(int $id): ?array
    {
        return self::findById(self::load()['categories'], $id);
    }

    public static function products(): array
    {
        return array_map(fn (array $product) => self::withCategory($product), self::load()['products']);
    }

    public static function product(int $id): ?array
    {
        $product = self::findById(self::load()['products'], $id);
        return $product ? self::withCategory($product) : null;
    }

    public static function createProduct(array $data): array
    {
        return self::change(function (array &$state) use ($data): array {
            self::requireCategory($state, (int) $data['category_id']);
            if (self::findBy($state['products'], 'sku', $data['sku'])) throw new RuntimeException('SKU sudah digunakan.', 422);
            $product = array_merge(['id' => self::nextId($state['products']), 'desc' => null], $data);
            $state['products'][] = $product;
            return self::withCategory($product);
        });
    }

    public static function updateProduct(int $id, array $data): ?array
    {
        return self::change(function (array &$state) use ($id, $data): ?array {
            self::requireCategory($state, (int) $data['category_id']);
            foreach ($state['products'] as $index => $product) {
                if ((int) $product['id'] !== $id) continue;
                $duplicate = self::findBy($state['products'], 'sku', $data['sku']);
                if ($duplicate && (int) $duplicate['id'] !== $id) throw new RuntimeException('SKU sudah digunakan.', 422);
                $state['products'][$index] = array_merge($product, $data);
                return self::withCategory($state['products'][$index]);
            }
            return null;
        });
    }

    public static function deleteProduct(int $id): bool
    {
        return self::change(function (array &$state) use ($id): bool {
            $count = count($state['products']);
            $state['products'] = array_values(array_filter($state['products'], fn (array $item) => (int) $item['id'] !== $id));
            if (count($state['products']) === $count) return false;
            $state['stock_movements'] = array_values(array_filter($state['stock_movements'], fn (array $item) => (int) $item['product_id'] !== $id));
            return true;
        });
    }

    public static function createCategory(array $data): array
    {
        return self::change(function (array &$state) use ($data): array {
            $category = array_merge(['id' => self::nextId($state['categories']), 'desc' => null], $data);
            $state['categories'][] = $category;
            return $category;
        });
    }

    public static function updateCategory(int $id, array $data): ?array
    {
        return self::change(function (array &$state) use ($id, $data): ?array {
            foreach ($state['categories'] as $index => $category) {
                if ((int) $category['id'] !== $id) continue;
                $state['categories'][$index] = array_merge($category, $data);
                return $state['categories'][$index];
            }
            return null;
        });
    }

    public static function deleteCategory(int $id): bool
    {
        return self::change(function (array &$state) use ($id): bool {
            if (array_filter($state['products'], fn (array $product) => (int) $product['category_id'] === $id)) throw new RuntimeException('Kategori tidak dapat dihapus karena masih digunakan oleh produk.', 422);
            $count = count($state['categories']);
            $state['categories'] = array_values(array_filter($state['categories'], fn (array $item) => (int) $item['id'] !== $id));
            return count($state['categories']) < $count;
        });
    }

    public static function movements(array $filters = []): array
    {
        $state = self::load();
        $items = array_map(function (array $movement) use ($state) {
            $movement['product'] = self::findById($state['products'], (int) $movement['product_id']);
            $movement['user'] = self::findById($state['users'], (int) $movement['user_id']);
            return $movement;
        }, $state['stock_movements']);
        $items = array_filter($items, function (array $item) use ($filters) {
            if (!empty($filters['type']) && $item['type'] !== $filters['type']) return false;
            $date = substr($item['created_at'], 0, 10);
            if (!empty($filters['from']) && $date < $filters['from']) return false;
            if (!empty($filters['to']) && $date > $filters['to']) return false;
            return true;
        });
        usort($items, fn (array $a, array $b) => strcmp($b['created_at'], $a['created_at']));
        return array_values($items);
    }

    public static function productMovements(int $productId): array
    {
        return array_values(array_filter(self::movements(), fn (array $movement) => (int) $movement['product_id'] === $productId));
    }

    public static function stockIn(int $productId, int $quantity, int $userId, ?string $note = null): array
    {
        if ($quantity < 1) throw new RuntimeException('Jumlah stok masuk minimal 1.', 422);
        return self::moveStock($productId, $quantity, $userId, 'IN', $note);
    }

    public static function stockOut(int $productId, int $quantity, int $userId, ?string $note = null): array
    {
        if ($quantity < 1) throw new RuntimeException('Jumlah stok keluar minimal 1.', 422);
        return self::moveStock($productId, -$quantity, $userId, 'OUT', $note);
    }

    public static function adjustment(int $productId, int $stock, int $userId, string $note): array
    {
        return self::change(function (array &$state) use ($productId, $stock, $userId, $note): array {
            foreach ($state['products'] as $product) {
                if ((int) $product['id'] === $productId) return self::moveStockInState($state, $productId, $stock - (int) $product['stock'], $userId, 'ADJUSTMENT', $note);
            }
            throw new RuntimeException('Produk tidak ditemukan.', 404);
        });
    }

    private static function moveStock(int $productId, int $delta, int $userId, string $type, ?string $note): array
    {
        return self::change(fn (array &$state) => self::moveStockInState($state, $productId, $delta, $userId, $type, $note));
    }

    private static function moveStockInState(array &$state, int $productId, int $delta, int $userId, string $type, ?string $note): array
    {
        foreach ($state['products'] as $index => $product) {
            if ((int) $product['id'] !== $productId) continue;
            if ((int) $product['stock'] + $delta < 0) throw new RuntimeException('Stock tidak mencukupi.', 422);
            $state['products'][$index]['stock'] = (int) $product['stock'] + $delta;
            $movement = ['id' => self::nextId($state['stock_movements']), 'product_id' => $productId, 'user_id' => $userId, 'type' => $type, 'quantity' => $delta, 'note' => $note, 'created_at' => now()->toIso8601String()];
            $state['stock_movements'][] = $movement;
            return ['product' => self::withCategory($state['products'][$index]), 'movement' => $movement];
        }
        throw new RuntimeException('Produk tidak ditemukan.', 404);
    }

    public static function checkout(array $items, int $userId, string $paymentMethod): array
    {
        return self::change(function (array &$state) use ($items, $userId, $paymentMethod): array {
        $quantities = [];
        foreach ($items as $item) {
            $id = (int) $item['product_id'];
            if (isset($quantities[$id])) throw new RuntimeException('Produk duplikat pada keranjang.', 422);
            $quantities[$id] = (int) $item['quantity'];
        }
        foreach ($quantities as $id => $quantity) {
            $product = self::findById($state['products'], $id);
            if (!$product || $quantity < 1 || (int) $product['stock'] < $quantity) throw new RuntimeException('Stok tidak mencukupi. Muat ulang katalog dan coba lagi.', 422);
        }
        $saleItems = [];
        $total = 0;
        foreach ($quantities as $id => $quantity) {
            $product = self::findById($state['products'], $id);
            $subtotal = (float) $product['price'] * $quantity;
            $productIndex = null;
            foreach ($state['products'] as $index => $storedProduct) {
                if ((int) $storedProduct['id'] === $id) { $productIndex = $index; break; }
            }
            if ($productIndex === null) throw new RuntimeException('Produk tidak ditemukan.', 404);
            $state['products'][$productIndex]['stock'] -= $quantity;
            $movement = ['id' => self::nextId($state['stock_movements']), 'product_id' => $id, 'user_id' => $userId, 'type' => 'OUT', 'quantity' => -$quantity, 'note' => 'Penjualan', 'created_at' => now()->toIso8601String()];
            $state['stock_movements'][] = $movement;
            $saleItems[] = ['product_id' => $id, 'name' => $product['name'], 'sku' => $product['sku'], 'quantity' => $quantity, 'unit_price' => (float) $product['price'], 'subtotal' => $subtotal, 'stock' => $state['products'][$productIndex]['stock']];
            $total += $subtotal;
        }
        return ['id' => 'TRX-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(6)), 'created_at' => now()->toIso8601String(), 'payment_method' => $paymentMethod, 'total' => $total, 'items_count' => array_sum(array_column($saleItems, 'quantity')), 'items' => $saleItems];
        });
    }

    private static function withCategory(array $product): array
    {
        $product['category'] = null;
        foreach (self::load()['categories'] as $category) {
            if ((int) $category['id'] === (int) $product['category_id']) {
                $product['category'] = $category;
                break;
            }
        }
        return $product;
    }

    private static function requireCategory(array $state, int $id): void
    {
        if (!self::findById($state['categories'], $id)) throw new RuntimeException('Kategori tidak ditemukan.', 422);
    }

    private static function nextId(array $items): int
    {
        return $items ? max(array_map(fn (array $item) => (int) $item['id'], $items)) + 1 : 1;
    }

    private static function findById(array $items, int $id): ?array
    {
        foreach ($items as $item) if ((int) $item['id'] === $id) return $item;
        return null;
    }

    private static function findBy(array $items, string $field, mixed $value): ?array
    {
        foreach ($items as $item) if (($item[$field] ?? null) === $value) return $item;
        return null;
    }
}

