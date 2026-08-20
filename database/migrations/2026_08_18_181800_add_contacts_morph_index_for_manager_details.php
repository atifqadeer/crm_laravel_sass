<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Speeds up getModuleContacts() lookups:
 * WHERE contactable_type = ? AND contactable_id = ? ORDER BY id DESC
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            if (!$this->indexExists('contacts', 'idx_contacts_type_id_id')) {
                $table->index(
                    ['contactable_type', 'contactable_id', 'id'],
                    'idx_contacts_type_id_id'
                );
            }
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            try {
                $table->dropIndex('idx_contacts_type_id_id');
            } catch (\Throwable $e) {
                // already gone
            }
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        return count(DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName])) > 0;
    }
};
