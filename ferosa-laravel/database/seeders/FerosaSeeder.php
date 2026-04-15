<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ServiceType;
use Illuminate\Database\Seeder;

class FerosaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Product::insert([
            ['name' => 'Monstera Deliciosa', 'description' => 'Lush indoor foliage plant in ceramic pot.', 'price' => 1200, 'category' => 'plants', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Boxwood Hedge (Set of 3)', 'description' => 'Perfect for defining garden borders.', 'price' => 3200, 'category' => 'plants', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Premium Garden Trowel', 'description' => 'Stainless steel with ergonomic handle.', 'price' => 450, 'category' => 'tools', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Irrigation Drip Kit', 'description' => 'Complete drip system for 25 m2 garden.', 'price' => 2100, 'category' => 'materials', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        ServiceType::insert([
            ['name' => 'Garden Design Consultation', 'default_fee' => 1500, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Routine Maintenance', 'default_fee' => 2500, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Irrigation System Check', 'default_fee' => 1200, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Hardscaping Quote', 'default_fee' => 0, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
