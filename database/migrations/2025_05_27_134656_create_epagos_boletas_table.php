<?php

use EpagosBridge\Enums\EstadoPago;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('epagos_boletas', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('id_transaccion');
            $table->integer('id_organismo');
            $table->enum('estado', [EstadoPago::PENDIENTE, EstadoPago::ACREDITADO, EstadoPago::CANCELADO, EstadoPago::RECHAZADO, EstadoPago::DEVUELTO, EstadoPago::VENCIDO,]);
            $table->integer('id_fp')->nullable();
            $table->double('monto_final');
            $table->string('url_recibo')->nullable();
            $table->string('concepto')->nullable();
            $table->timestamp('fecha_pago')->nullable();
            $table->timestamp('fecha_acreditacion')->nullable();
            $table->timestamp('fecha_novedad')->nullable();
            $table->timestamp('fecha_verificacion')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
    }
};
