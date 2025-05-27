<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('epagos_operaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('boleta_id')->nullable()->constrained('epagos_boletas');
            $table->bigInteger('id_transaccion');
            $table->integer('id_organismo');
            $table->string('referencia_adicional')->nullable();
            $table->string('codigo_externo');
            $table->double('monto');
            $table->date('fecha_vencimiento');
            $table->timestamps();
        });
    }

    public function down(): void
    {
    }
};
