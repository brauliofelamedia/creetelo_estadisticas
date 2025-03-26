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
            $table->string('lead_id')->nullable();
            $table->string('phoneLabel')->nullable();
            $table->string('country')->nullable();
            $table->string('address')->nullable();
            $table->string('source')->nullable();
            $table->string('type')->nullable();
            $table->string('locationId');
            $table->string('website')->nullable();
            $table->boolean('dnd')->default(false);
            $table->string('state')->nullable();
            $table->string('businessName')->nullable();
            $table->json('customFields')->nullable();
            $table->json('tags')->nullable();
            $table->timestamp('dateAdded')->nullable();
            $table->json('additionalEmails')->nullable();
            $table->string('phone')->nullable();
            $table->string('companyName')->nullable();
            $table->json('additionalPhones')->nullable();
            $table->timestamp('dateUpdated')->nullable();
            $table->string('city')->nullable();
            $table->date('dateOfBirth')->nullable();
            $table->string('firstNameLowerCase')->nullable();
            $table->string('lastNameLowerCase')->nullable();
            $table->string('email')->nullable();
            $table->string('assignedTo')->nullable();
            $table->json('followers')->nullable();
            $table->boolean('validEmail')->default(false);
            $table->string('postalCode')->nullable();
            $table->string('businessId')->nullable();
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
