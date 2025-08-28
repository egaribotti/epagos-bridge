<?php

namespace EpagosBridge\Commands;

use EpagosBridge\Models\BoletaEstado;
use EpagosBridge\Models\Config;
use EpagosBridge\Models\FormaPago;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class Instalar extends Command
{
    protected $signature = 'epagos:instalar';

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

        if (BoletaEstado::count() === 0) {
            BoletaEstado::insert([
                ['id' => 1, 'descripcion' => 'pendiente'],
                ['id' => 2, 'descripcion' => 'acreditado'],
                ['id' => 3, 'descripcion' => 'rechazado'],
                ['id' => 4, 'descripcion' => 'vencido'],
                ['id' => 5, 'descripcion' => 'devuelto'],
            ]);
        }

        if (FormaPago::count() === 0) {
            FormaPago::insert([
                ['id' => 1, 'descripcion' => 'VISA', 'modalidad' => 'Tarjeta de crédito - Online'],
                ['id' => 2, 'descripcion' => 'American Express (AMEX)', 'modalidad' => 'Tarjeta de crédito – Online'],
                ['id' => 3, 'descripcion' => 'Mastercard', 'modalidad' => 'Tarjeta de crédito – Online'],
                ['id' => 9, 'descripcion' => 'ArgenCard', 'modalidad' => 'Tarjeta de crédito – Online'],
                ['id' => 10, 'descripcion' => 'Cabal Crédito', 'modalidad' => 'Tarjeta de crédito – Online'],
                ['id' => 11, 'descripcion' => 'Diners Club', 'modalidad' => 'Tarjeta de crédito – Online'],
                ['id' => 14, 'descripcion' => 'VISA Débito', 'modalidad' => 'Tarjeta de débito - Online'],
                ['id' => 15, 'descripcion' => 'Maestro Débito', 'modalidad' => 'Tarjeta de débito - Online'],
                ['id' => 28, 'descripcion' => 'Cabal Débito', 'modalidad' => 'Tarjeta de débito - Online'],
                ['id' => 41, 'descripcion' => 'Mastercard Débito', 'modalidad' => 'Tarjeta de débito - Online'],
                ['id' => 52, 'descripcion' => 'Habitualista Débito', 'modalidad' => 'Tarjeta de débito - Online'],
                ['id' => 4, 'descripcion' => 'Pago fácil', 'modalidad' => 'Presencial - Efectivo'],
                ['id' => 5, 'descripcion' => 'Rapi pago', 'modalidad' => 'Presencial - Efectivo'],
                ['id' => 8, 'descripcion' => 'Banco Nación', 'modalidad' => 'Presencial - Efectivo'],
                ['id' => 16, 'descripcion' => 'RIPSA Pagos (desactivado)', 'modalidad' => 'Presencial - Efectivo'],
                ['id' => 12, 'descripcion' => 'BanCor - Banco de Córdoba', 'modalidad' => 'Presencial - Efectivo'],
                ['id' => 39, 'descripcion' => 'Banco de Corrientes', 'modalidad' => 'Presencial - Efectivo'],
                ['id' => 50, 'descripcion' => 'Banco Santander', 'modalidad' => 'Presencial - Efectivo'],
                ['id' => 40, 'descripcion' => 'Banco de Neuquén', 'modalidad' => 'Presencial - Efectivo'],
                ['id' => 13, 'descripcion' => 'Provincia NET (desactivado)', 'modalidad' => 'Presencial - Efectivo'],
                ['id' => 18, 'descripcion' => 'Plus pagos (desactivado)', 'modalidad' => 'Presencial - Efectivo'],
                ['id' => 19, 'descripcion' => 'Entre Ríos Servicios (Plus pagos) (desactivado)', 'modalidad' => 'Presencial - Efectivo'],
                ['id' => 20, 'descripcion' => 'Santa Fe Servicios (Plus pagos) (desactivado)', 'modalidad' => 'Presencial - Efectivo'],
                ['id' => 21, 'descripcion' => 'San Juan Servicios (Plus pagos) (desactivado)', 'modalidad' => 'Presencial - Efectivo'],
                ['id' => 22, 'descripcion' => 'Santa Cruz Servicios (Plus pagos) (desactivado)', 'modalidad' => 'Presencial - Efectivo'],
                ['id' => 23, 'descripcion' => 'Corrientes Servicios (Plus pagos) (desactivado)', 'modalidad' => 'Presencial - Efectivo'],
                ['id' => 33, 'descripcion' => 'Chubut Pagos (Provincia NET) (desactivado)', 'modalidad' => 'Presencial - Efectivo'],
                ['id' => 32, 'descripcion' => 'Pronto Pagos (Provincia NET) (desactivado)', 'modalidad' => 'Presencial – Efectivo'],
                ['id' => 31, 'descripcion' => 'Pampa Pagos (Provincia NET) (desactivado)', 'modalidad' => 'Presencial – Efectivo'],
                ['id' => 30, 'descripcion' => 'Formo Pagos (Provincia NET) (desactivado)', 'modalidad' => 'Presencial – Efectivo'],
                ['id' => 29, 'descripcion' => 'Banco Piano (Provincia NET) (desactivado)', 'modalidad' => 'Presencial - Efectivo'],
                ['id' => 25, 'descripcion' => 'E-Cajero', 'modalidad' => 'Presencial - Efectivo'],
                ['id' => 35, 'descripcion' => 'Tarjeta mandataria', 'modalidad' => 'Presencial - Efectivo'],
                ['id' => 36, 'descripcion' => 'Multipago', 'modalidad' => 'Presencial - Efectivo'],
                ['id' => 37, 'descripcion' => 'BICA Ágil (desactivado)', 'modalidad' => 'Presencial - Efectivo'],
                ['id' => 24, 'descripcion' => 'Cobro express', 'modalidad' => 'Presencial - Efectivo'],
                ['id' => 53, 'descripcion' => 'Banco Supervielle', 'modalidad' => 'Presencial – Efectivo'],
                ['id' => 6, 'descripcion' => 'Pago Mis cuentas', 'modalidad' => 'Publicación de deuda'],
                ['id' => 7, 'descripcion' => 'Red Link – Web', 'modalidad' => 'Publicación de deuda'],
                ['id' => 26, 'descripcion' => 'Red Link', 'modalidad' => 'Publicación de deuda'],
                ['id' => 27, 'descripcion' => 'Billetera ePagos', 'modalidad' => 'Billetera online'],
                ['id' => 48, 'descripcion' => 'Billetera MODO', 'modalidad' => 'Billetera online'],
                ['id' => 47, 'descripcion' => 'Billetera MercadoPago', 'modalidad' => 'Billetera online'],
                ['id' => 17, 'descripcion' => 'E-Transferencia', 'modalidad' => 'Transferencia bancaria'],
                ['id' => 38, 'descripcion' => 'Debin', 'modalidad' => 'Transferencia bancaria'],
                ['id' => 42, 'descripcion' => 'Débito directo', 'modalidad' => 'Transferencia bancaria'],
                ['id' => 51, 'descripcion' => 'Débito inmediato', 'modalidad' => 'Transferencia bancaria'],
                ['id' => 44, 'descripcion' => 'Transferencias 3.0', 'modalidad' => 'Transferencia bancaria'],
                ['id' => 43, 'descripcion' => 'IVR', 'modalidad' => 'Cobro telefónico'],
                ['id' => 45, 'descripcion' => 'Billetera Cripto', 'modalidad' => 'Criptomonedas'],
                ['id' => 34, 'descripcion' => 'Combinado', 'modalidad' => 'Presencial – Efectivo y Publicación de deuda'],
            ]);
        }

        $this->info('¡Instalado correctamente!');
    }
}
