<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The fields a team records about a client beyond the four the clients table holds.
     * Definitions live here so adding one is a row rather than a migration; the clients
     * table is deliberately untouched.
     */
    public function up(): void
    {
        Schema::create('client_attributes', function (Blueprint $table) {
            $table->id();
            // The machine name: the CSV column, and stable across environments.
            $table->string('key', 64)->unique();
            // Unique because the import matches a file heading against this label.
            $table->string('name')->unique();
            $table->string('type', 32);
            $table->string('hint', 500)->nullable();
            // A select's choices, or a repeater's recursive sub-field tree.
            $table->json('options')->nullable();
            $table->json('fields')->nullable();
            $table->boolean('is_required')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_attributes');
    }
};
