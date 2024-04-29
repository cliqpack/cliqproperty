<?php

namespace Modules\Settings\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Settings\Database\factories\FileFormatFactory;
use Modules\Settings\Entities\FileFormat;

class FileFormatTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $seedData = [
            ['file_name' => 'Standard ABA format'],
            ['file_name' => 'ABA format with 1 debit per file'],
            ['file_name' => 'ABA format with 1 debit per row'],

        ];
        // Check if the seeding has already been done
        if (FileFormat::count() > 0) {
            return;
        }

        Model::unguard();
        $count = 3;

        for ($i = 1; $i <= $count; $i++) {
            // Check if a region with specific attributes (e.g., name) exists
            $regionAttributes = [
                'file_name' => FileFormatFactory::new()->raw()['file_name'],
                // Add other attributes as needed
            ];

            FileFormat::firstOrCreate($regionAttributes);
        }
    }
}
