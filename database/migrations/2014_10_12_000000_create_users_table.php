<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('user_type');
            $table->string('work_phone')->nullable();
            $table->string('mobile_phone')->nullable();
            $table->string('company_id')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('address')->default('Melbourne');
            $table->string('language_code')->default('en');
            
            $table->string('profile')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
        DB::table('users')->insert([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => 'admin@gmail.com',
            'user_type' => 'Admin',
            'work_phone' => '123-456-7890',
            'mobile_phone' => '123-456-7891',
            'company_id' => null,
            'email_verified_at' => now(),
            'password' => Hash::make('12345678'),
            'address' => '123 Admin Street, Admin City',
            'language_code' => 'en',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('users')->insert([
            'first_name' => 'First',
            'last_name' => 'Manager',
            'email' => 'manager@example.com',
            'user_type' => 'Property Manager',
            'work_phone' => '123-456-7892',
            'mobile_phone' => '123-456-7893',
            'company_id' => '1',
            'email_verified_at' => now(),
            'password' => Hash::make('12345678'),
            'address' => '456 Manager Street, Manager City',
            'language_code' => 'en',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
   

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
