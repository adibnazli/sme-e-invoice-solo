<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Temporarily disable MySQL's foreign key security lock
        Schema::disableForeignKeyConstraints();
        
        // 2. Safely drop the old table
        Schema::dropIfExists('customers'); 
        
        // 3. Rebuild the fresh table for our Loyalty System
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->unique();
            $table->string('email')->nullable();
            $table->integer('loyalty_points')->default(0);
            $table->timestamps();
        });

        // 4. Turn security back on
        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('customers');
        Schema::enableForeignKeyConstraints();
    }
};