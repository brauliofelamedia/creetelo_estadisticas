<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->string('contact_id')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('country')->nullable();
            $table->string('address')->nullable();
            $table->string('source')->nullable();
            $table->string('type')->nullable();
            $table->string('location_id')->nullable();
            $table->string('website')->nullable();
            $table->boolean('dnd')->default(false)->nullable();
            $table->string('state')->nullable();
            $table->string('business_name')->nullable();
            $table->json('custom_fields')->nullable();
            $table->json('tags')->nullable();
            $table->timestamp('date_added')->nullable();
            $table->json('additional_emails')->nullable();
            $table->string('company_name')->nullable();
            $table->json('additional_phones')->nullable();
            $table->timestamp('date_update')->nullable();
            $table->string('city')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('assigned_to')->nullable();
            $table->json('followers')->nullable();
            $table->boolean('valid_email')->default(false);
            $table->string('postal_code')->nullable();
            $table->string('business_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
