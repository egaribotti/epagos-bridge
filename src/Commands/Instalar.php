<?php

namespace EpagosBridge\Commands;

use EpagosBridge\Models\Config;
use EpagosBridge\Models\FormaPago;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class Instalar extends Command
{
    protected $signature = 'epagos:install';

    protected $description = 'Instalar.';

    public function handle(): void
    {
        if (Config::count() === 0) {
            Config::insert([
                ['clave' => 'wsdl', 'valor' => null],
                ['clave' => 'on_queue', 'valor' => null],
                ['clave' => 'fuera_servicio', 'valor' => 0],
                ['clave' => 'secret_key', 'valor' => Str::uuid()],
                ['clave' => 'minutos_espera', 'valor' => 3],
                ['clave' => 'limite', 'valor' => 100],
                ['clave' => 'pdf', 'valor' => json_encode(['pattern' => null])],
            ]);
        }

        if (FormaPago::count() === 0) {
            FormaPago::insert([
                ['id_fp' => 1, 'descripcion' => 'VISA', 'modalidad' => 'Tarjeta de crédito - Online'],
                ['id_fp' => 2, 'descripcion' => 'American Express (AMEX)', 'modalidad' => 'Tarjeta de crédito – Online'],
                ['id_fp' => 3, 'descripcion' => 'Mastercard', 'modalidad' => 'Tarjeta de crédito – Online'],
                ['id_fp' => 9, 'descripcion' => 'ArgenCard', 'modalidad' => 'Tarjeta de crédito – Online'],
                ['id_fp' => 10, 'descripcion' => 'Cabal Crédito', 'modalidad' => 'Tarjeta de crédito – Online'],
                ['id_fp' => 11, 'descripcion' => 'Diners Club', 'modalidad' => 'Tarjeta de crédito – Online'],
                ['id_fp' => 14, 'descripcion' => 'VISA Débito', 'modalidad' => 'Tarjeta de débito - Online'],
                ['id_fp' => 15, 'descripcion' => 'Maestro Débito', 'modalidad' => 'Tarjeta de débito - Online'],
                ['id_fp' => 28, 'descripcion' => 'Cabal Débito', 'modalidad' => 'Tarjeta de débito - Online'],
                ['id_fp' => 41, 'descripcion' => 'Mastercard Débito', 'modalidad' => 'Tarjeta de débito - Online'],
                ['id_fp' => 52, 'descripcion' => 'Habitualista Débito', 'modalidad' => 'Tarjeta de débito - Online'],
                ['id_fp' => 4, 'descripcion' => 'Pago fácil', 'modalidad' => 'Presencial - Efectivo'],
                ['id_fp' => 5, 'descripcion' => 'Rapi pago', 'modalidad' => 'Presencial - Efectivo'],
                ['id_fp' => 8, 'descripcion' => 'Banco Nación', 'modalidad' => 'Presencial - Efectivo'],
                ['id_fp' => 16, 'descripcion' => 'RIPSA Pagos (desactivado)', 'modalidad' => 'Presencial - Efectivo'],
                ['id_fp' => 12, 'descripcion' => 'BanCor - Banco de Córdoba', 'modalidad' => 'Presencial - Efectivo'],
                ['id_fp' => 39, 'descripcion' => 'Banco de Corrientes', 'modalidad' => 'Presencial - Efectivo'],
                ['id_fp' => 50, 'descripcion' => 'Banco Santander', 'modalidad' => 'Presencial - Efectivo'],
                ['id_fp' => 40, 'descripcion' => 'Banco de Neuquén', 'modalidad' => 'Presencial - Efectivo'],
                ['id_fp' => 13, 'descripcion' => 'Provincia NET (desactivado)', 'modalidad' => 'Presencial - Efectivo'],
                ['id_fp' => 18, 'descripcion' => 'Plus pagos (desactivado)', 'modalidad' => 'Presencial - Efectivo'],
                ['id_fp' => 19, 'descripcion' => 'Entre Ríos Servicios (Plus pagos) (desactivado)', 'modalidad' => 'Presencial - Efectivo'],
                ['id_fp' => 20, 'descripcion' => 'Santa Fe Servicios (Plus pagos) (desactivado)', 'modalidad' => 'Presencial - Efectivo'],
                ['id_fp' => 21, 'descripcion' => 'San Juan Servicios (Plus pagos) (desactivado)', 'modalidad' => 'Presencial - Efectivo'],
                ['id_fp' => 22, 'descripcion' => 'Santa Cruz Servicios (Plus pagos) (desactivado)', 'modalidad' => 'Presencial - Efectivo'],
                ['id_fp' => 23, 'descripcion' => 'Corrientes Servicios (Plus pagos) (desactivado)', 'modalidad' => 'Presencial - Efectivo'],
                ['id_fp' => 33, 'descripcion' => 'Chubut Pagos (Provincia NET) (desactivado)', 'modalidad' => 'Presencial - Efectivo'],
                ['id_fp' => 32, 'descripcion' => 'Pronto Pagos (Provincia NET) (desactivado)', 'modalidad' => 'Presencial – Efectivo'],
                ['id_fp' => 31, 'descripcion' => 'Pampa Pagos (Provincia NET) (desactivado)', 'modalidad' => 'Presencial – Efectivo'],
                ['id_fp' => 30, 'descripcion' => 'Formo Pagos (Provincia NET) (desactivado)', 'modalidad' => 'Presencial – Efectivo'],
                ['id_fp' => 29, 'descripcion' => 'Banco Piano (Provincia NET) (desactivado)', 'modalidad' => 'Presencial - Efectivo'],
                ['id_fp' => 25, 'descripcion' => 'E-Cajero', 'modalidad' => 'Presencial - Efectivo'],
                ['id_fp' => 35, 'descripcion' => 'Tarjeta mandataria', 'modalidad' => 'Presencial - Efectivo'],
                ['id_fp' => 36, 'descripcion' => 'Multipago', 'modalidad' => 'Presencial - Efectivo'],
                ['id_fp' => 37, 'descripcion' => 'BICA Ágil (desactivado)', 'modalidad' => 'Presencial - Efectivo'],
                ['id_fp' => 24, 'descripcion' => 'Cobro express', 'modalidad' => 'Presencial - Efectivo'],
                ['id_fp' => 53, 'descripcion' => 'Banco Supervielle', 'modalidad' => 'Presencial – Efectivo'],
                ['id_fp' => 6, 'descripcion' => 'Pago Mis cuentas', 'modalidad' => 'Publicación de deuda'],
                ['id_fp' => 7, 'descripcion' => 'Red Link – Web', 'modalidad' => 'Publicación de deuda'],
                ['id_fp' => 26, 'descripcion' => 'Red Link', 'modalidad' => 'Publicación de deuda'],
                ['id_fp' => 27, 'descripcion' => 'Billetera ePagos', 'modalidad' => 'Billetera online'],
                ['id_fp' => 48, 'descripcion' => 'Billetera MODO', 'modalidad' => 'Billetera online'],
                ['id_fp' => 47, 'descripcion' => 'Billetera MercadoPago', 'modalidad' => 'Billetera online'],
                ['id_fp' => 17, 'descripcion' => 'E-Transferencia', 'modalidad' => 'Transferencia bancaria'],
                ['id_fp' => 38, 'descripcion' => 'Debin', 'modalidad' => 'Transferencia bancaria'],
                ['id_fp' => 42, 'descripcion' => 'Débito directo', 'modalidad' => 'Transferencia bancaria'],
                ['id_fp' => 51, 'descripcion' => 'Débito inmediato', 'modalidad' => 'Transferencia bancaria'],
                ['id_fp' => 44, 'descripcion' => 'Transferencias 3.0', 'modalidad' => 'Transferencia bancaria'],
                ['id_fp' => 43, 'descripcion' => 'IVR', 'modalidad' => 'Cobro telefónico'],
                ['id_fp' => 45, 'descripcion' => 'Billetera Cripto', 'modalidad' => 'Criptomonedas'],
                ['id_fp' => 34, 'descripcion' => 'Combinado', 'modalidad' => 'Presencial – Efectivo y Publicación de deuda'],
            ]);
        }

        $this->info('¡Instalado correctamente!');
    }
}
