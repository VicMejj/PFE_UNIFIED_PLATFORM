<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->whereNotIn('lang', ['en', 'fr'])
            ->update(['lang' => 'en']);

        DB::table('languages')
            ->whereNotIn('code', ['en', 'fr'])
            ->delete();
    }

    public function down(): void
    {
        // Unsupported languages were intentionally removed.
    }
};
