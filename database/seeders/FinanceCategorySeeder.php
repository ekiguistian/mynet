<?php

namespace Database\Seeders;

use App\Models\FinanceCategory;
use Illuminate\Database\Seeder;

class FinanceCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['Pendapatan langganan', 'income'],
            ['Biaya instalasi', 'income'],
            ['Penjualan barang', 'income'],
            ['Pendapatan lain', 'income'],
            ['Gaji dan tunjangan', 'expense'],
            ['Operasional', 'expense'],
            ['Perangkat jaringan', 'expense'],
            ['Pemeliharaan jaringan', 'expense'],
            ['Pengeluaran lain', 'expense'],
        ];

        foreach ($categories as [$name, $type]) {
            FinanceCategory::query()->updateOrCreate(
                ['branch_id' => null, 'name' => $name, 'type' => $type],
                ['active' => true],
            );
        }
    }
}
