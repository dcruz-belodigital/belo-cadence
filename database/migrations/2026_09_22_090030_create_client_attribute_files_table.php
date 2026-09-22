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
     * The bytes a client uploaded for a file attribute. The answer itself stays in
     * `client_attribute_values` like every other answer and refers to a row here by its
     * id, which is what lets a file sit anywhere an answer can — including inside a
     * repeating row, at any depth.
     *
     * A row here is the authority on who a file belongs to. The download route reads the
     * client off it rather than off the answer, so a tampered answer cannot point at
     * somebody else's file.
     *
     * Both keys cascade, for the same reason the values table's do: a file belongs to the
     * client record rather than to its history. Clients are only ever archived, so in
     * practice the cascade that fires is the attribute's.
     */
    public function up(): void
    {
        Schema::create('client_attribute_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_attribute_id')->constrained()->cascadeOnDelete();
            // Who uploaded it, kept for the audit trail rather than for any decision.
            $table->foreignId('uploaded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('disk', 32);
            // Unique so two rows can never share bytes: deleting one would empty the other.
            $table->string('path')->unique();
            $table->string('original_name');
            $table->string('mime_type', 191);
            $table->unsignedBigInteger('size');
            $table->timestamps();

            // Clearing an answer looks for what this client still holds for one attribute.
            $table->index(['client_id', 'client_attribute_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_attribute_files');
    }
};
