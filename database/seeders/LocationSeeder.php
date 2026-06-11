<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('cities')->truncate();
        DB::table('states')->truncate();
        DB::table('countries')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $drcId = DB::table('countries')->insertGetId([
            'sortname' => 'CD',
            'name' => 'Democratic Republic of the Congo',
            'phonecode' => 243,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $drcProvinces = require database_path('seeders/data/drc_provinces.php');
        foreach ($drcProvinces as $provinceName => $cities) {
            $stateId = DB::table('states')->insertGetId([
                'name' => $provinceName,
                'country_id' => $drcId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            foreach ($cities as $cityName) {
                DB::table('cities')->insert([
                    'name' => $cityName,
                    'state_id' => $stateId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $usId = DB::table('countries')->insertGetId([
            'sortname' => 'US',
            'name' => 'United States',
            'phonecode' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $nyId = DB::table('states')->insertGetId([
            'name' => 'New York',
            'country_id' => $usId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        foreach (['New York City', 'Buffalo', 'Manhattan', 'Brooklyn'] as $city) {
            DB::table('cities')->insert([
                'name' => $city,
                'state_id' => $nyId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $frId = DB::table('countries')->insertGetId([
            'sortname' => 'FR',
            'name' => 'France',
            'phonecode' => 33,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $parisId = DB::table('states')->insertGetId([
            'name' => 'Paris',
            'country_id' => $frId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        foreach (['Paris', 'Paris 1er'] as $city) {
            DB::table('cities')->insert([
                'name' => $city,
                'state_id' => $parisId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->command?->info('Locations seeded: DRC (26 provinces), US, France.');
    }
}
