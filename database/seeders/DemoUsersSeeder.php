<?php

namespace Database\Seeders;

use App\Models\{User, Petugas};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUsersSeeder extends Seeder
{
    public function run(): void
    {
        // PETUGAS
        $petugasData = [
            [
                'user' => [
                    'name'    => 'Budi Santoso',
                    'email'   => 'petugas@myperpus.id',
                    'phone'   => '081234567890',
                ],
                'petugas' => [
                    'nip'     => 'PT2024001',
                    'jabatan' => 'Kepala Perpustakaan',
                    'bagian'  => 'Manajemen',
                    'tentang' => 'Berpengalaman 10 tahun di bidang pengelolaan perpustakaan digital.',
                ],
            ],
            [
                'user' => [
                    'name'    => 'Siti Rahayu',
                    'email'   => 'siti@myperpus.id',
                    'phone'   => '081234567891',
                ],
                'petugas' => [
                    'nip'     => 'PT2024002',
                    'jabatan' => 'Petugas Layanan',
                    'bagian'  => 'Layanan Peminjaman',
                    'tentang' => 'Melayani anggota dengan ramah dan profesional.',
                ],
            ],
            [
                'user' => [
                    'name'    => 'Ahmad Fauzi',
                    'email'   => 'ahmad@myperpus.id',
                    'phone'   => '081234567892',
                ],
                'petugas' => [
                    'nip'     => 'PT2024003',
                    'jabatan' => 'Petugas Katalog',
                    'bagian'  => 'Pengolahan Koleksi',
                    'tentang' => 'Ahli dalam pengkatalogan dan pengolahan koleksi buku.',
                ],
            ],
            [
                'user' => [
                    'name'    => 'Dewi Kusuma',
                    'email'   => 'dewi@myperpus.id',
                    'phone'   => '081234567893',
                ],
                'petugas' => [
                    'nip'     => 'PT2024004',
                    'jabatan' => 'Petugas Digital',
                    'bagian'  => 'Layanan Digital',
                    'tentang' => 'Mengelola layanan baca online dan digitalisasi koleksi.',
                ],
            ],
        ];

        foreach ($petugasData as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['user']['email']],
                array_merge($data['user'], [
                    'password'   => Hash::make('password'),
                    'no_anggota' => User::generateNoAnggota(),
                    'status'     => 'aktif',
                ])
            );
            $user->assignRole('petugas');
            Petugas::firstOrCreate(['user_id' => $user->id], array_merge($data['petugas'], ['user_id' => $user->id]));
        }

        // ANGGOTA DEMO
        $anggotaDemo = User::firstOrCreate(
            ['email' => 'anggota@myperpus.id'],
            [
                'name'       => 'Rina Anggota',
                'password'   => Hash::make('password'),
                'no_anggota' => User::generateNoAnggota(),
                'phone'      => '081299999999',
                'status'     => 'aktif',
            ]
        );
        $anggotaDemo->assignRole('pengguna');

        for ($i = 1; $i <= 20; $i++) {
            $user = User::firstOrCreate(
                ['email' => "anggota{$i}@myperpus.id"],
                [
                    'name'       => "Anggota Demo {$i}",
                    'password'   => Hash::make('password'),
                    'no_anggota' => User::generateNoAnggota(),
                    'status'     => 'aktif',
                ]
            );
            $user->assignRole('pengguna');
        }
    }
}
