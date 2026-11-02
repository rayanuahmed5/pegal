<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // who decided and when, so a rejected booking can be explained later
            // nullOnDelete: the booking stays even if the finance user is removed
            $table->foreignId('decided_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable()->after('decided_by');

            // the consultant reads this, so finance has to write it in plain words
            $table->string('decision_reason')->nullable()->after('decided_at');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('decided_by');
            $table->dropColumn(['decided_at', 'decision_reason']);
        });
    }
};
