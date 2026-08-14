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
     * These entries are offered when a client is created. Applying them copies the
     * configuration onto the client; later changes here never touch existing schedules.
     */
    public function up(): void
    {
        Schema::create('default_client_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('template');
            $table->string('frequency');
            $table->boolean('is_enabled_by_default')->default(true);
            $table->timestamps();

            $table->unique(['template', 'frequency']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('default_client_notifications');
    }
};
