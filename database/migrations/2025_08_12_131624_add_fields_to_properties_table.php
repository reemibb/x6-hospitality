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
        Schema::table('properties', function (Blueprint $table) {
            $table->string('type')->after('description');
            $table->decimal('price_per_night', 8, 2)->after('type');
            $table->integer('max_guests')->after('price_per_night');
            $table->integer('bedrooms')->after('max_guests');
            $table->decimal('bathrooms', 3, 1)->after('bedrooms');
            $table->json('amenities')->nullable()->after('bathrooms');
            $table->json('images')->nullable()->after('photos');
            $table->enum('status', ['active', 'inactive', 'maintenance'])->default('active')->after('images');
            $table->boolean('featured')->default(false)->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn([
                'type', 'price_per_night', 'max_guests', 'bedrooms', 
                'bathrooms', 'amenities', 'images', 'status', 'featured'
            ]);
        });
    }
};
