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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('currency')->nullable();
            $table->decimal('amount',15,2)->nullable();
            $table->string('status')->nullable();
            $table->string('livemode')->default(false);
            $table->string('entity_type')->nullable();
            $table->string('entity_source_type')->nullable();
            $table->string('entity_id')->nullable();
            $table->string('subscription_id')->nullable();
            $table->string('charge_id')->nullable();
            $table->string('source_type')->nullable();
            $table->string('state')->nullable();
            $table->string('summary');
            $table->string('entitySourceName');
            $table->date('create_time');

            $table->unsignedBigInteger('contact_id');
            $table->foreign('contact_id')->references('id')->on('contacts');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
