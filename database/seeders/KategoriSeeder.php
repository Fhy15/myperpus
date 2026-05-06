<?php

namespace Database\Seeders;

use App\Models\Kategori;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class KategoriSeeder extends Seeder
{
    public function run(): void
    {
        $kategoriList = [
            ['nama' => 'Fiksi',         'icon' => '📖', 'deskripsi' => 'Novel, cerpen, dan karya fiksi'],
            ['nama' => 'Sains',         'icon' => '🔬', 'deskripsi' => 'Ilmu pengetahuan alam dan sains'],
            ['nama' => 'Teknologi',     'icon' => '💻', 'deskripsi' => 'Komputer, IT, dan teknologi modern'],
            ['nama' => 'Sejarah',       'icon' => '🏛️', 'deskripsi' => 'Sejarah Indonesia dan dunia'],
            ['nama' => 'Filsafat',      'icon' => '🧠', 'deskripsi' => 'Filsafat, etika, dan pemikiran'],
            ['nama' => 'Ekonomi',       'icon' => '📊', 'deskripsi' => 'Ekonomi, bisnis, dan keuangan'],
            ['nama' => 'Psikologi',     'icon' => '🧩', 'deskripsi' => 'Psikologi dan ilmu perilaku'],
            ['nama' => 'Pendidikan',    'icon' => '🎓', 'deskripsi' => 'Buku teks dan pendidikan'],
            ['nama' => 'Seni & Budaya', 'icon' => '🎨', 'deskripsi' => 'Seni, budaya, dan kreativitas'],
            ['nama' => 'Agama',         'icon' => '☪️', 'deskripsi' => 'Buku agama dan spiritual'],
            ['nama' => 'Kesehatan',     'icon' => '🏥', 'deskripsi' => 'Kesehatan, kedokteran, dan gizi'],
            ['nama' => 'Sastra',        'icon' => '✍️', 'deskripsi' => 'Sastra Indonesia dan dunia'],
        ];

        foreach ($kategoriList as $k) {
            Kategori::firstOrCreate(
                ['nama' => $k['nama']],
                ['slug' => Str::slug($k['nama']), 'icon' => $k['icon'], 'deskripsi' => $k['deskripsi']]
            );
        }
    }
}
