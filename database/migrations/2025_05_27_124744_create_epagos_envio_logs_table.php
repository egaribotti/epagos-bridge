<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('epagos_envio_logs', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('id_transaccion')->nullable();
            $table->integer('id_organismo');
            $table->string('codigo_externo')->nullable();
            $table->string('token');
            $table->string('id_resp');
            $table->string('respuesta');
            $table->string('url')->nullable();
            $table->string('codigo_barras')->nullable();
            $table->longText('pdf')->nullable();
            $table->longText('request_content');
            $table->longText('response_content');
            $table->timestamps();
        });
    }

    public function down(): void
    {
    }
};
