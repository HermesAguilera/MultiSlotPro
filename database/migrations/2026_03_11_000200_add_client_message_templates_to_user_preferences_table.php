<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_preferences', function (Blueprint $table) {
            $table->text('delivery_message_template')->nullable()->after('report_sales_settings');
            $table->text('expiry_message_template')->nullable()->after('delivery_message_template');
            $table->text('expiry_today_message_template')->nullable()->after('expiry_message_template');
        });
    }

    public function down(): void
    {
        Schema::table('user_preferences', function (Blueprint $table) {
            $table->dropColumn([
                'delivery_message_template',
                'expiry_message_template',
                'expiry_today_message_template',
            ]);
        });
    }
};
