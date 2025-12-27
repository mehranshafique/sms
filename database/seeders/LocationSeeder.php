<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Run using: php artisan db:seed --class=LocationSeeder
     */
    public function run()
    {
        // Optional: Clear tables to prevent duplicates when re-seeding
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('cities')->truncate();
        DB::table('states')->truncate();
        DB::table('countries')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 1. Democratic Republic of the Congo
        $drcId = DB::table('countries')->insertGetId([
            'sortname' => 'CD',
            'name' => 'Democratic Republic of the Congo',
            'phonecode' => 243,
            'created_at' => now(), 'updated_at' => now()
        ]);

        // --- State: Kinshasa ---
        $kinshasaId = DB::table('states')->insertGetId(['name' => 'Kinshasa', 'country_id' => $drcId]);
        
        $kinshasaCommunes = [
            'Bandalungwa', 'Barumbu', 'Bumbu', 'Gombe', 'Kalamu', 'Kasa-Vubu', 'Kimbanseke', 
            'Kinshasa', 'Kintambo', 'Kisenso', 'Lemba', 'Limete', 'Lingwala', 'Makala', 
            'Maluku', 'Masina', 'Matete', 'Mont-Ngafula', 'Ndjili', 'Ngaba', 'Ngaliema', 
            'Ngiri-Ngiri', 'Nsele', 'Selembao'
        ];

        foreach ($kinshasaCommunes as $name) {
            DB::table('cities')->insert(['name' => $name, 'state_id' => $kinshasaId]);
        }

        // --- State: Lubumbashi (Mapped as a State-level entity for your specific data structure) ---
        $lubumbashiId = DB::table('states')->insertGetId(['name' => 'Lubumbashi', 'country_id' => $drcId]);
        
        $lshiCommunes = ['Kamalondo', 'Kampemba', 'Katuba', 'Kenya', 'Lubumbashi', 'Ruashi', 'Annexe'];
        foreach ($lshiCommunes as $name) {
            DB::table('cities')->insert(['name' => $name, 'state_id' => $lubumbashiId]);
        }
        
        // --- State: North Kivu (Standard Province) ---
        $northKivuId = DB::table('states')->insertGetId(['name' => 'North Kivu', 'country_id' => $drcId]);
        DB::table('cities')->insert([
            ['name' => 'Goma', 'state_id' => $northKivuId],
            ['name' => 'Beni', 'state_id' => $northKivuId],
            ['name' => 'Butembo', 'state_id' => $northKivuId],
        ]);

        // --- State: South Kivu ---
        $southKivuId = DB::table('states')->insertGetId(['name' => 'South Kivu', 'country_id' => $drcId]);
        DB::table('cities')->insert([
            ['name' => 'Bukavu', 'state_id' => $southKivuId],
            ['name' => 'Uvira', 'state_id' => $southKivuId],
        ]);

        // 2. United States
        $usId = DB::table('countries')->insertGetId([
            'sortname' => 'US',
            'name' => 'United States',
            'phonecode' => 1,
            'created_at' => now(), 'updated_at' => now()
        ]);
        
        $nyId = DB::table('states')->insertGetId(['name' => 'New York', 'country_id' => $usId]);
        DB::table('cities')->insert([
            ['name' => 'New York City', 'state_id' => $nyId],
            ['name' => 'Buffalo', 'state_id' => $nyId],
            ['name' => 'Manhattan', 'state_id' => $nyId],
            ['name' => 'Brooklyn', 'state_id' => $nyId],
        ]);

        // 3. France
        $frId = DB::table('countries')->insertGetId([
            'sortname' => 'FR',
            'name' => 'France',
            'phonecode' => 33,
            'created_at' => now(), 'updated_at' => now()
        ]);
        
        $parisId = DB::table('states')->insertGetId(['name' => 'Paris', 'country_id' => $frId]);
        DB::table('cities')->insert([
            ['name' => 'Paris', 'state_id' => $parisId],
            ['name' => 'Paris 1er', 'state_id' => $parisId],
        ]);
    }
}