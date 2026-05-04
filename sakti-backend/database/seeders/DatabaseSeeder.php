<?php
// =============================================================================
// File   : database/seeders/DatabaseSeeder.php
// Fungsi : Seed user default: 1 admin + 1 penjaga pos
// Author : SAKTI Dev Team
// Date   : 2026-05-01
// =============================================================================

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@sakti.id'],
            [
                'name'     => 'Administrator SAKTI',
                'password' => Hash::make('sakti2026'),
                'role'     => 'admin',
            ]
        );

        User::updateOrCreate(
            ['email' => 'penjaga@sakti.id'],
            [
                'name'     => 'Penjaga Pos Utama',
                'password' => Hash::make('sakti2026'),
                'role'     => 'penjaga',
            ]
        );
    }
}
