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
            $table->string('email')->nullable();
            $table->string('currency')->nullable();
            $table->decimal('amount',10,2)->nullable();
            $table->string('status')->nullable();
            $table->boolean('livemode')->default(true);
            $table->string('entityType')->nullable();
            $table->string('entityId')->nullable();
            $table->string('providerType')->nullable();
            $table->string('sourceType')->nullable();
            $table->string('subscription_id')->nullable();
            $table->date('create_time')->nullable();

            //Contacts
            /*$table->unsignedBigInteger('lead_id')->nullable();
            $table->foreign('lead_id')->references('id')->on('contacts')->onDelete('cascade'); */

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
