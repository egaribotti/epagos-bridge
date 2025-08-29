<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('epagos_pagos_adicionales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('boleta_id')->constrained('epagos_boletas');
            $table->bigInteger('id_transaccion');
            $table->bigInteger('id_pago');
            $table->integer('id_organismo');
            $table->string('forma_pago');
            $table->double('monto');
            $table->timestamp('fecha_pago');
            $table->timestamp('fecha_novedad');
            $table->timestamps();
        });
    }

    public function down(): void
    {
    }
};
