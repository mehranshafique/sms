<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;
use App\Enums\RoleEnum;

return new class extends Migration
{
    public function up(): void
    {
        Role::firstOrCreate([
            'name' => RoleEnum::GATE_ATTENDANT->value,
            'guard_name' => 'web',
            'institution_id' => null,
        ]);
    }

    public function down(): void
    {
        Role::where('name', RoleEnum::GATE_ATTENDANT->value)
            ->where('guard_name', 'web')
            ->whereNull('institution_id')
            ->delete();
    }
};
