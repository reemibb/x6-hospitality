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
        Schema::table('reviews', function (Blueprint $table) {
            $table->string('status')->default('published')->after('comment'); 
            $table->text('response')->nullable()->after('status'); 
            $table->timestamp('responded_at')->nullable()->after('response');
            $table->integer('helpful_votes')->default(0)->after('responded_at');
            $table->boolean('verified')->default(false)->after('helpful_votes'); 
            $table->timestamp('updated_at')->nullable()->after('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn(['status', 'response', 'responded_at', 'helpful_votes', 'verified', 'updated_at']);
        });
    }
};
