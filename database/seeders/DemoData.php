<?php

/**
 * Portfolio/demo fixture data. This file only defines data; it does not seed a database.
 * Load it from the demo data store that will be added in a later step.
 */

return [
    'users' => [
        [
            'id' => 1,
            'name' => 'Super Admin Demo',
            'email' => 'superadmin@pos-demo.test',
                        'password' => bcrypt('pos123'),
            'role' => 'super_admin',
            'created_at' => '2026-10-15T08:00:00+07:00',
        ],
        [
            'id' => 2,
            'name' => 'Admin Demo ',
            'email' => 'admin@pos-demo.test',
                        'password' => bcrypt('pos123'),
            'role' => 'admin',
            'created_at' => '2026-10-15T08:00:00+07:00',
        ],
        [
            'id' => 3,
            'name' => 'Kasir Demo',
            'email' => 'kasir@pos-demo.test',
                        'password' => bcrypt('pos123'),
            'role' => 'cashier',
            'created_at' => '2026-10-15T08:00:00+07:00',
        ],
    ],
    'categories' => [
        ['id' => 1, 'name' => 'Minuman', 'desc' => 'Minuman kemasan dan siap minum'],
        ['id' => 2, 'name' => 'Makanan', 'desc' => 'Makanan ringan dan instan'],
        ['id' => 3, 'name' => 'Kebutuhan Harian', 'desc' => 'Keperluan sehari-hari'],
    ],
    'products' => [
        ['id' => 1, 'category_id' => 1, 'name' => 'Air Mineral 600ml', 'sku' => 'MIN-001', 'price' => 4000, 'stock' => 60, 'desc' => 'Air mineral botol 600ml'],
        ['id' => 2, 'category_id' => 1, 'name' => 'Teh Botol', 'sku' => 'MIN-002', 'price' => 5000, 'stock' => 40, 'desc' => 'Teh siap minum'],
        ['id' => 3, 'category_id' => 1, 'name' => 'Kopi Susu', 'sku' => 'MIN-003', 'price' => 12000, 'stock' => 30, 'desc' => 'Kopi susu siap minum'],
        ['id' => 4, 'category_id' => 1, 'name' => 'Coco Drink', 'sku' => 'MIN-004', 'price' => 7000, 'stock' => 35, 'desc' => 'Minuman kelapa'],
        ['id' => 5, 'category_id' => 2, 'name' => 'Roti Cokelat', 'sku' => 'MAK-001', 'price' => 8000, 'stock' => 25, 'desc' => 'Roti isi cokelat'],
        ['id' => 6, 'category_id' => 2, 'name' => 'Keripik Singkong', 'sku' => 'MAK-002', 'price' => 10000, 'stock' => 20, 'desc' => 'Keripik singkong gurih'],
        ['id' => 7, 'category_id' => 2, 'name' => 'Mi Instan', 'sku' => 'MAK-003', 'price' => 3500, 'stock' => 50, 'desc' => 'Mi instan kemasan'],
        ['id' => 8, 'category_id' => 3, 'name' => 'Sabun Mandi', 'sku' => 'HAR-001', 'price' => 6000, 'stock' => 20, 'desc' => 'Sabun mandi batang'],
        ['id' => 9, 'category_id' => 3, 'name' => 'Tisu Wajah', 'sku' => 'HAR-002', 'price' => 9000, 'stock' => 15, 'desc' => 'Tisu wajah kemasan'],
        ['id' => 10, 'category_id' => 3, 'name' => 'Baterai AA', 'sku' => 'HAR-003', 'price' => 5000, 'stock' => 24, 'desc' => 'Baterai AA satuan'],
    ],
    'stock_movements' => [
        ['id' => 1, 'product_id' => 1, 'user_id' => 1, 'type' => 'IN', 'quantity' => 10, 'note' => 'Barang masuk dari supplier', 'created_at' => '2026-10-15T08:30:00+07:00'],
        ['id' => 2, 'product_id' => 3, 'user_id' => 2, 'type' => 'OUT', 'quantity' => 2, 'note' => 'Penjualan', 'created_at' => '2026-10-15T09:15:00+07:00'],
        ['id' => 3, 'product_id' => 5, 'user_id' => 1, 'type' => 'ADJUSTMENT', 'quantity' => -1, 'note' => 'Koreksi hasil stok opname', 'created_at' => '2026-10-15T09:45:00+07:00'],
        ['id' => 4, 'product_id' => 2, 'user_id' => 2, 'type' => 'OUT', 'quantity' => 1, 'note' => 'Penjualan', 'created_at' => '2026-10-15T10:20:00+07:00'],
        ['id' => 5, 'product_id' => 8, 'user_id' => 1, 'type' => 'IN', 'quantity' => 5, 'note' => 'Barang masuk dari supplier', 'created_at' => '2026-10-14T13:00:00+07:00'],
        ['id' => 6, 'product_id' => 4, 'user_id' => 2, 'type' => 'OUT', 'quantity' => 1, 'note' => 'Penjualan', 'created_at' => '2026-10-13T16:10:00+07:00'],
    ],
];
