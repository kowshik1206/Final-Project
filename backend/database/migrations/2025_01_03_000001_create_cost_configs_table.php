<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCostConfigsTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('cost_configs')) {
            Schema::create('cost_configs', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->json('value')->nullable();
                $table->date('effective_from')->nullable();
                $table->date('effective_to')->nullable();
                $table->boolean('active')->default(true)->index();
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('cost_configs');
    }
}
