<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('configs', function (Blueprint $table) {
            $table->id(); // Changed from uuid to standard auto-incrementing ID
            $table->string('site_name');
            $table->string('primary_color')->default('#E60000');
            $table->string('secondary_color')->default('#000000');
            $table->string('logo_light')->nullable()->default('config/light.png');
            $table->string('logo_dark')->nullable()->default('config/dark.png');
            $table->string('favicon')->nullable()->default('config/favicon.png');

            //Extra token GHL
            $table->text('code')->nullable();
            $table->text('company_id')->nullable();
            $table->text('location_id')->nullable();
            $table->text('refresh_token')->nullable();
            $table->text('access_token')->nullable();

            //Tags import
            $table->string('tags');

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('configs');
    }
};
