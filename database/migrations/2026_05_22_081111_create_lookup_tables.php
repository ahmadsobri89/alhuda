<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lookup_categories', function (Blueprint $table) {
            $table->id();
            $table->string('group', 50);        // patient, appointment, pharmacy, inventory, billing, referral, user
            $table->string('slug', 80)->unique(); // jantina, kumpulan_darah, negeri, ...
            $table->string('name_ms', 120);
            $table->string('name_en', 120);
            $table->string('description_ms', 255)->nullable();
            $table->string('description_en', 255)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('lookup_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('lookup_categories')->cascadeOnDelete();
            $table->string('code', 80);         // value stored in DB, e.g. 'male', 'A+'
            $table->string('label_ms', 120);
            $table->string('label_en', 120);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false); // cannot be deleted
            $table->timestamps();

            $table->unique(['category_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lookup_values');
        Schema::dropIfExists('lookup_categories');
    }
};
