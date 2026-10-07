<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('priority', 20)->default('Medium')->after('category');
            $table->boolean('is_recurring')->default(false)->after('priority');
            $table->string('recurrence_type', 20)->nullable()->after('is_recurring');
            $table->integer('recurrence_count')->nullable()->default(1)->after('recurrence_type');
            $table->integer('reminder_minutes')->nullable()->after('recurrence_count');
            $table->boolean('reminder_sent')->default(false)->after('reminder_minutes');
            $table->foreignId('recurrence_parent_id')->nullable()->constrained('events')->onDelete('cascade')->after('reminder_sent');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropForeign(['recurrence_parent_id']);
            $table->dropColumn([
                'priority',
                'is_recurring',
                'recurrence_type',
                'recurrence_count',
                'reminder_minutes',
                'reminder_sent',
                'recurrence_parent_id'
            ]);
        });
    }
};
