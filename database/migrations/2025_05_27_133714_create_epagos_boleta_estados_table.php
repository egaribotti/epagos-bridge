<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('epagos_boleta_estados', function (Blueprint $table) {
            $table->id();
            $table->string('descripcion');
        });
    }

    public function down(): void
    {
    }
};
