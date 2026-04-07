<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('contract_attachments', function (Blueprint $table) {
            if (! Schema::hasColumn('contract_attachments', 'contract_id')) {
                $table->foreignId('contract_id')->nullable()->constrained('contracts')->onDelete('cascade')->after('id');
            }
            if (! Schema::hasColumn('contract_attachments', 'file_name')) {
                $table->string('file_name')->nullable()->after('contract_id');
            }
            if (! Schema::hasColumn('contract_attachments', 'file_path')) {
                $table->string('file_path')->nullable()->after('file_name');
            }
            if (! Schema::hasColumn('contract_attachments', 'file_type')) {
                $table->string('file_type')->nullable()->after('file_path');
            }
        });

        Schema::table('contract_comments', function (Blueprint $table) {
            if (! Schema::hasColumn('contract_comments', 'contract_id')) {
                $table->foreignId('contract_id')->nullable()->constrained('contracts')->onDelete('cascade')->after('id');
            }
            if (! Schema::hasColumn('contract_comments', 'comment_by')) {
                $table->unsignedBigInteger('comment_by')->nullable()->after('contract_id');
            }
            if (! Schema::hasColumn('contract_comments', 'comment_text')) {
                $table->text('comment_text')->nullable()->after('comment_by');
            }
        });

        Schema::table('contract_notes', function (Blueprint $table) {
            if (! Schema::hasColumn('contract_notes', 'contract_id')) {
                $table->foreignId('contract_id')->nullable()->constrained('contracts')->onDelete('cascade')->after('id');
            }
            if (! Schema::hasColumn('contract_notes', 'note_text')) {
                $table->text('note_text')->nullable()->after('contract_id');
            }
            if (! Schema::hasColumn('contract_notes', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('note_text');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contract_attachments', function (Blueprint $table) {
            if (Schema::hasColumn('contract_attachments', 'contract_id')) {
                $table->dropForeign(['contract_id']);
                $table->dropColumn('contract_id');
            }
            if (Schema::hasColumn('contract_attachments', 'file_name')) {
                $table->dropColumn('file_name');
            }
            if (Schema::hasColumn('contract_attachments', 'file_path')) {
                $table->dropColumn('file_path');
            }
            if (Schema::hasColumn('contract_attachments', 'file_type')) {
                $table->dropColumn('file_type');
            }
        });

        Schema::table('contract_comments', function (Blueprint $table) {
            if (Schema::hasColumn('contract_comments', 'contract_id')) {
                $table->dropForeign(['contract_id']);
                $table->dropColumn('contract_id');
            }
            if (Schema::hasColumn('contract_comments', 'comment_by')) {
                $table->dropColumn('comment_by');
            }
            if (Schema::hasColumn('contract_comments', 'comment_text')) {
                $table->dropColumn('comment_text');
            }
        });

        Schema::table('contract_notes', function (Blueprint $table) {
            if (Schema::hasColumn('contract_notes', 'contract_id')) {
                $table->dropForeign(['contract_id']);
                $table->dropColumn('contract_id');
            }
            if (Schema::hasColumn('contract_notes', 'note_text')) {
                $table->dropColumn('note_text');
            }
            if (Schema::hasColumn('contract_notes', 'created_by')) {
                $table->dropColumn('created_by');
            }
        });
    }
};
