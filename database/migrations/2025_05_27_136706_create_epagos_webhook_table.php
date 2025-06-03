<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('epagos_webhook', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('id_transaccion');
            $table->integer('id_tipo');
            $table->integer('id_organismo');
            $table->string('codigo_externo');
            $table->json('response_content');
            $table->timestamps();
        });
    }

    public function down(): void
    {
    }
};
