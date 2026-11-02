<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // one row = one consultant, one day, one order
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();

            // restrict on purpose: booked hours should never vanish because
            // someone deleted an order or a user
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();

            $table->date('worked_on');

            // we only keep hours per day, no start and end time (less data, fewer clicks)
            $table->decimal('hours', 4, 2);

            $table->string('description');

            // submitted, approved or rejected
            $table->string('status', 20)->default('submitted');

            $table->timestamps();

            // the budget sums read by order and status
            $table->index(['order_id', 'status']);
            // the daily hour limit reads by person and day
            $table->index(['user_id', 'worked_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
