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
        Schema::create('login_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('email');
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->unsignedBigInteger('token_id')->nullable();
            $table->boolean('successful')->default(false);
            $table->timestamp('attempted_at');
            $table->timestamps();

            $table->index(['email', 'ip_address']);
            $table->index(['successful', 'attempted_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('login_attempts');
    }
};
