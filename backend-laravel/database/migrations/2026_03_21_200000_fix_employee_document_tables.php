<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            if (! Schema::hasColumn('documents', 'name')) {
                $table->string('name')->nullable()->after('id');
            }

            if (! Schema::hasColumn('documents', 'description')) {
                $table->text('description')->nullable()->after('name');
            }
        });

        Schema::table('employee_documents', function (Blueprint $table) {
            if (! Schema::hasColumn('employee_documents', 'employee_id')) {
                $table->unsignedBigInteger('employee_id')->nullable()->after('id');
                $table->foreign('employee_id')->references('id')->on('employees')->nullOnDelete();
            }

            if (! Schema::hasColumn('employee_documents', 'document_id')) {
                $table->unsignedBigInteger('document_id')->nullable()->after('employee_id');
                $table->foreign('document_id')->references('id')->on('documents')->nullOnDelete();
            }

            if (! Schema::hasColumn('employee_documents', 'file_path')) {
                $table->string('file_path')->nullable()->after('document_id');
            }

            if (! Schema::hasColumn('employee_documents', 'file_name')) {
                $table->string('file_name')->nullable()->after('file_path');
            }

            if (! Schema::hasColumn('employee_documents', 'expiry_date')) {
                $table->date('expiry_date')->nullable()->after('file_name');
            }
        });

        Schema::table('document_uploads', function (Blueprint $table) {
            if (! Schema::hasColumn('document_uploads', 'document_name')) {
                $table->string('document_name')->nullable()->after('id');
            }

            if (! Schema::hasColumn('document_uploads', 'document_type')) {
                $table->string('document_type')->nullable()->after('document_name');
            }

            if (! Schema::hasColumn('document_uploads', 'file_path')) {
                $table->string('file_path')->nullable()->after('document_type');
            }

            if (! Schema::hasColumn('document_uploads', 'file_size')) {
                $table->unsignedBigInteger('file_size')->nullable()->after('file_path');
            }

            if (! Schema::hasColumn('document_uploads', 'uploaded_by')) {
                $table->unsignedBigInteger('uploaded_by')->nullable()->after('file_size');
                $table->foreign('uploaded_by')->references('id')->on('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('document_uploads', 'uploaded_date')) {
                $table->date('uploaded_date')->nullable()->after('uploaded_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('document_uploads', function (Blueprint $table) {
            if (Schema::hasColumn('document_uploads', 'uploaded_by')) {
                $table->dropForeign(['uploaded_by']);
            }

            $columns = array_filter([
                Schema::hasColumn('document_uploads', 'uploaded_date') ? 'uploaded_date' : null,
                Schema::hasColumn('document_uploads', 'uploaded_by') ? 'uploaded_by' : null,
                Schema::hasColumn('document_uploads', 'file_size') ? 'file_size' : null,
                Schema::hasColumn('document_uploads', 'file_path') ? 'file_path' : null,
                Schema::hasColumn('document_uploads', 'document_type') ? 'document_type' : null,
                Schema::hasColumn('document_uploads', 'document_name') ? 'document_name' : null,
            ]);

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });

        Schema::table('employee_documents', function (Blueprint $table) {
            if (Schema::hasColumn('employee_documents', 'document_id')) {
                $table->dropForeign(['document_id']);
            }

            if (Schema::hasColumn('employee_documents', 'employee_id')) {
                $table->dropForeign(['employee_id']);
            }

            $columns = array_filter([
                Schema::hasColumn('employee_documents', 'expiry_date') ? 'expiry_date' : null,
                Schema::hasColumn('employee_documents', 'file_name') ? 'file_name' : null,
                Schema::hasColumn('employee_documents', 'file_path') ? 'file_path' : null,
                Schema::hasColumn('employee_documents', 'document_id') ? 'document_id' : null,
                Schema::hasColumn('employee_documents', 'employee_id') ? 'employee_id' : null,
            ]);

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });

        Schema::table('documents', function (Blueprint $table) {
            $columns = array_filter([
                Schema::hasColumn('documents', 'description') ? 'description' : null,
                Schema::hasColumn('documents', 'name') ? 'name' : null,
            ]);

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
