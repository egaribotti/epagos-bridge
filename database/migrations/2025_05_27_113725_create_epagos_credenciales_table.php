<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('epagos_credenciales', function (Blueprint $table) {
            $table->id();
            $table->integer('id_organismo');
            $table->integer('id_usuario');
            $table->string('password');
            $table->string('hash');
            $table->timestamps();
        });
    }

    public function down(): void
    {
    }
};
