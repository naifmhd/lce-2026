<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('role_permissions')
            ->whereIn('permission', [
                'pledge.council.view',
                'pledge.council.update',
                'pledge.mayor.view',
                'pledge.mayor.update',
                'pledge.wdc.view',
                'pledge.wdc.update',
                'pledge.raeesa.view',
                'pledge.raeesa.update',
            ])
            ->delete();
    }
};
