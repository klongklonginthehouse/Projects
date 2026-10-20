<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Module 3 (Encryption and Decryption):
 * Encrypts two existing free-text columns that may hold confidential content:
 *   - resources.description  (notes a contributor writes about their upload)
 *   - reports.reason         (why a student reported a resource)
 *
 * The columns are widened so the longer ciphertext still fits, and any existing
 * plain-text values are converted in place so no data is lost.
 */
return new class extends Migration {
    public function up(): void
    {
        // Ciphertext is longer than plain text, so widen the reason column.
        Schema::table('reports', function (Blueprint $table) {
            $table->text('reason')->change();
        });

        $this->encryptExisting('resources', 'description');
        $this->encryptExisting('reports', 'reason');
    }

    public function down(): void
    {
        $this->decryptExisting('resources', 'description');
        $this->decryptExisting('reports', 'reason');

        Schema::table('reports', function (Blueprint $table) {
            $table->string('reason')->default('copyright')->change();
        });
    }

    private function encryptExisting(string $table, string $column): void
    {
        foreach (DB::table($table)->whereNotNull($column)->get() as $row) {
            if ($this->isEncrypted($row->{$column})) {
                continue;
            }
            DB::table($table)->where('id', $row->id)
                ->update([$column => Crypt::encryptString($row->{$column})]);
        }
    }

    private function decryptExisting(string $table, string $column): void
    {
        foreach (DB::table($table)->whereNotNull($column)->get() as $row) {
            if (! $this->isEncrypted($row->{$column})) {
                continue;
            }
            DB::table($table)->where('id', $row->id)
                ->update([$column => Crypt::decryptString($row->{$column})]);
        }
    }

    private function isEncrypted(?string $value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        try {
            Crypt::decryptString($value);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
};
