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
     * One row per client per attribute. No value means no row, so "unset" has exactly one
     * representation and counting what still uses a definition stays honest.
     *
     * Both foreign keys cascade: a value belongs to the client record rather than to its
     * history, which is why this differs from deliveries.
     */
    public function up(): void
    {
        Schema::create('client_attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_attribute_id')->constrained()->cascadeOnDelete();
            $table->json('value');
            $table->timestamps();

            $table->unique(['client_id', 'client_attribute_id']);
            // The unique index leads with the client, but retiring a definition counts by definition.
            $table->index('client_attribute_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_attribute_values');
    }
};
