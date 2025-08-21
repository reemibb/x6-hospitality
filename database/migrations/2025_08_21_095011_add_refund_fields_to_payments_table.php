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
        Schema::table('payments', function (Blueprint $table) {
            $table->decimal('refunded_amount', 10, 2)->nullable()->after('amount');
            $table->text('refund_reason')->nullable()->after('transaction_id');
            $table->timestamp('refunded_at')->nullable()->after('paid_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['refunded_amount', 'refund_reason', 'refunded_at']);
        });
    }
};
