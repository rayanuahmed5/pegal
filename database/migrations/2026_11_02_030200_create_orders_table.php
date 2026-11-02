<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // a purchase order, the thing every booked hour is charged against
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('number', 20)->unique();
            $table->string('title');

            // don't let a supplier disappear while it still has orders
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();

            $table->string('cost_center', 30);
            $table->string('project_element', 30)->nullable();

            // ordered budget in person-days, 1 PD = 8 hours
            $table->decimal('budget_pd', 8, 2);

            $table->date('starts_on');
            $table->date('ends_on');

            // how many weeks purchasing needs to place a follow-up order
            $table->unsignedTinyInteger('reorder_lead_weeks')->default(4);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
