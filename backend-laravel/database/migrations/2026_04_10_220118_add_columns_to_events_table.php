<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            if (!Schema::hasColumn('events', 'title')) {
                $table->string('title')->nullable()->after('id');
            }
            if (!Schema::hasColumn('events', 'description')) {
                $table->text('description')->nullable()->after('title');
            }
            if (!Schema::hasColumn('events', 'event_date')) {
                $table->date('event_date')->nullable()->after('description');
            }
            if (!Schema::hasColumn('events', 'start_time')) {
                $table->time('start_time')->nullable()->after('event_date');
            }
            if (!Schema::hasColumn('events', 'end_time')) {
                $table->time('end_time')->nullable()->after('start_time');
            }
            if (!Schema::hasColumn('events', 'location')) {
                $table->string('location')->nullable()->after('end_time');
            }
            if (!Schema::hasColumn('events', 'type')) {
                $table->string('type')->nullable()->default('general')->after('location');
            }
            if (!Schema::hasColumn('events', 'color')) {
                $table->string('color')->nullable()->after('type');
            }
            if (!Schema::hasColumn('events', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('color');
            }
            if (!Schema::hasColumn('events', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('is_active');
            }
        });

        // Backfill null titles for existing rows
        DB::table('events')->whereNull('title')->update(['title' => 'Untitled Event']);
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $columns = ['title', 'description', 'event_date', 'start_time',
                        'end_time', 'location', 'type', 'color', 'is_active', 'user_id'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('events', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
