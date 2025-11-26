<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('withdrawals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); 
            $table->enum('method', ['yape', 'plin', 'transferencia_bancaria', 'transferencia_electronica']);
            $table->string('holder');
            $table->string('phone_number')->nullable();
            $table->string('account_number')->nullable();
            $table->decimal('amount', 10, 2);     
            $table->decimal('commission', 10, 2); 
            $table->decimal('net_amount', 10, 2); 
            $table->enum('status', ['earring', 'approved', 'rejected'])->default('earring');
            $table->date('date');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withdrawals');
    }
};
