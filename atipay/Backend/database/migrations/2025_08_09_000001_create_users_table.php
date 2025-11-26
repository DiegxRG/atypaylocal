<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->string('phone_number');
            $table->foreignId('role_id')->constrained('roles')->onDelete('cascade');
            $table->string('password');
            $table->enum('status', ['active', 'inactive'])->default('inactive');
            $table->decimal('atipay_money', 10, 2)->default(0); 
            $table->integer('accumulated_points')->default(0);
            $table->string('reference_code')->unique();
            $table->foreignId('referred_by')->nullable()->constrained('users')->onDelete('set null');
            $table->boolean('reference_code_valid')->default(true); //new

            $table->date('registration_date'); 
            $table->string('registration_time'); 
            $table->timestamp('last_login_at')->nullable(); //new
            //login_inactive  new
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};

