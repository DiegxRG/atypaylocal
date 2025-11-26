<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referral_commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); 
            $table->foreignId('referred_user_id')->constrained('users')->onDelete('cascade'); 
            $table->unsignedTinyInteger('level'); 
            $table->decimal('commission_amount', 10, 2); 
            $table->unsignedInteger('points_generated'); 
            $table->enum('source_type', ['purchase', 'investment'])->default('purchase');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_commissions');
    }
};
