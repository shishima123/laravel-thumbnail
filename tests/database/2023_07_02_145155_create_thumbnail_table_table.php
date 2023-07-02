<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create(config('thumbnail.table_name'), function (Blueprint $table)
        {
            $table->bigIncrements('id');
            $table->text('name');
            $table->text('original_name');
            $table->text('path');
            $table->integer('thumbnailable_id');
            $table->string('thumbnailable_type', 100);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists(config('thumbnail.table_name'));
    }
};
