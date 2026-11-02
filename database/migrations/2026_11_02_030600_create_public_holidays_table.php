<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // nationwide holidays only for now, state specific ones can come later
        Schema::create('public_holidays', function (Blueprint $table) {
            $table->id();
            $table->date('holiday_on')->unique();
            $table->string('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('public_holidays');
    }
};
