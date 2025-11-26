<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('atipay_recharges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('full_names');
            $table->decimal('amount', 10, 2);
            $table->foreignId('user_payment_method_id')->constrained('user_payment_methods')->onDelete('cascade');
            $table->string('proof_image_path');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null'); 
            $table->decimal('atipays_granted', 10, 2)->default(0);

            $table->date('request_date');  
            $table->string('request_time'); 
            $table->date('processed_date')->nullable(); 
            $table->string('processed_time')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('atipay_recharges');
    }
};
