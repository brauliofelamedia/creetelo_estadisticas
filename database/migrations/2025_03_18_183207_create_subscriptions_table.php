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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('_id')->nullable();
            $table->string('contactId')->nullable();
            $table->string('email')->nullable();
            $table->string('currency')->nullable();
            $table->decimal('amount',10,2)->nullable();
            $table->string('status')->nullable();
            $table->boolean('livemode')->default(true);
            $table->string('entity_type')->nullable();
            $table->string('entity_id')->nullable();
            $table->string('provider_type')->nullable();
            $table->string('source_type')->nullable();
            $table->string('entity_resource_name')->nullable();
            $table->string('subscription_id')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->date('cancelled_at')->nullable();
            $table->date('create_time')->nullable();

            //Contacts
            $table->unsignedBigInteger('contact_id')->nullable();
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
