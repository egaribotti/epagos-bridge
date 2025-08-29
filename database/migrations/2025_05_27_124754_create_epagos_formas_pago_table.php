<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('epagos_formas_pago', function (Blueprint $table) {
            $table->id();
            $table->integer('id_fp');
            $table->string('descripcion');
            $table->string('modalidad');
        });
    }

    public function down(): void
    {
    }
};
