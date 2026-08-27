<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE meetings ALTER COLUMN meeting_code TYPE varchar(25)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE meetings ALTER COLUMN meeting_code TYPE varchar(12)');
    }
};
