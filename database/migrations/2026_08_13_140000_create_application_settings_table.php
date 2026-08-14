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
     * Application settings live in a single row with explicit, typed columns rather
     * than one opaque JSON document.
     */
    public function up(): void
    {
        Schema::create('application_settings', function (Blueprint $table) {
            $table->id();
            $table->string('application_name');
            $table->string('default_locale');
            $table->string('default_timezone');
            $table->string('default_theme');
            $table->string('client_email_sender_name');
            $table->string('client_email_sender_email');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('application_settings');
    }
};
