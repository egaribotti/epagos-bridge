# Epagos Bridge

![Versión](https://img.shields.io/badge/versión-1.1.2-green.svg)

Este paquete permite integrar Epagos de forma rápida y sencilla en cualquier proyecto con Laravel.
Incluye una implementación básica de medidas de seguridad y está diseñado para facilitar la generación de solicitudes de
pago, así como la gestión de las respuestas recibidas desde la plataforma.

## 📦 Instalación

Clonar en tu proyecto.

```bash
mkdir lib
cd lib
git clone https://github.com/egaribotti/epagos-bridge.git
```

Registrar PSR-4 en el composer.json

```
{
    "autoload": {
        "psr-4": {
            "EpagosBridge\\": "lib/epagos-bridge/src"
        }
    }
}
```

Agregar el service provider en el archivo `config/app.php`.

```php
return [
    'providers' => [
        EpagosBridge\EpagosServiceProvider::class,
    ],
];
```

Ejecute el comando para crear las tablas necesarias.

```
php artisan migrate
php artisan epagos:install
```

## 🔐 Configuración

Para que el paquete funcione correctamente se tiene que configurar los siguientes valores en la tabla `epagos_config`:

- `wsdl`: Es la URL que conecta con la API de Epagos. Está especificada en la documentación. [Ver documentación](https://www.epagos.com/templates/desarrolladores/referencia.php?v=2.5)
- `fuera_servicio`: Se utiliza en caso de que necesites frenar la integración con Epagos.
- `secret_key`: **IMPORTANTE**. Tanto para utilizar la API de Epagos Bridge como para aceptar los pagos que se informan por webhook, es necesario configurar un secret. **No compartas esta key**.
- `on_queue`: Para manejar la verificación de los pagos en cola se pueden configurar las queues de Laravel. Si se deja en null, los pagos se verificarán de forma síncrona (en el momento).
- `minutos_espera`: Minutos de espera para volver a intentar sincronizar el estado de las boletas pendientes.
- `limite`: Límite de boletas que se sincronizan.
- `pdf`: Si al momento de crear el pago configuras la generación de los comprobantes en PDF, se debe omitir en el guardado para evitar duplicidad. **Este JSON no se cambia** a menos que Epagos modifique su XML de respuesta.

**Pattern (Regex) última version de Epagos**: `/<pdf[^>]*>(.*?)<\\/pdf>/is`

## 🛠️ Modo de uso

Para mayor organización de las credenciales de Epagos, se recomienda enviar únicamente el `id_organismo` en la propiedad
`credenciales`. En caso de que el `id_usuario` varíe, puede incluirse también. A continuación, dejo un ejemplo de los
dos casos:

```php
$payload = [
    'credenciales' => [
        'id_organismo' => 0,
    ],
];

$payload = [
    'credenciales' => [
        'id_organismo' => 0,
        'id_usuario' => 0,
        'password' => null,
        'hash' => null
    ],
];
```

Estos son los métodos estaticos actualmente disponibles:

```php
Epagos::obtenerMediosPago($credenciales);
Epagos::obtenerFormasPago();
Epagos::obtenerPago($idTransaccion);
Epagos::crearPago($payload, $concepto);
Epagos::crearOperacionesLote($payload);
Epagos::obtenerComprobantePdf($idTransaccion); // Devuelve un base64 o null
```

## 📚 Métodos

A continuación se detallan los métodos disponibles en el paquete, junto con su descripción, parámetros esperados y
ejemplos de uso.

- Ejemplo de payload enviado al método `crearPago`:

```php
use EpagosBridge\Epagos;
use Carbon\Carbon;

$payload = [
    'credenciales' => [
        'id_organismo' => 0,
        'id_usuario' => 0,
        'password' => null,
        'hash' => null
    ],
    'convenio' => null,
    'items' => [[
        'id_item' => 0,
        'desc_item' => null,
        'monto_item' => 20,
        'cantidad_item' => 5,
    ]],
    'nombre_pagador' => null,
    'apellido_pagador' => null,
    'email_pagador' => null,
    'cuit_pagador' => null,
    'identificador_externo_2' => null,
    'identificador_externo_3' => null,
    'identificador_externo_4' => json_encode([]),
    'referencia_adicional' => null,
    'fecha_vencimiento' => Carbon::now()->addDay()->toDateString(),
    'operaciones_lote' => [],
    'id_fp' => 4,
    'pdf' => false // Se tiene que configurar el pattern
];

$concepto = 'ejemplo_pago'; // Es nullable

Epagos::crearPago($payload, $concepto);
```

**Estructura de la respuesta:**

| Propiedad              | Tipo    | Descripción                                      |
|------------------------|---------|--------------------------------------------------|
| `boleta_id`            | integer | Id de la boleta creada.                          |
| `id_transaccion`       | integer | Id de transacción en Epagos.                     |
| `referencia_adicional` | string  | En caso de que haya especificado.                |
| `monto_final`          | float   | El monto final a pagar.                          |
| `url`                  | string  | La URL para ir a pagar.                          |

- Ejemplo de payload enviado al método `crearOperacionesLote`:

```php
$itemLote = [
    'convenio' => null,
    'items' => [[
        'id_item' => 0,
        'desc_item' => null,
        'monto_item' => 20,
        'cantidad_item' => 5,
    ]],
    'nombre_pagador' => null,
    'apellido_pagador' => null,
    'email_pagador' => null,
    'cuit_pagador' => null,
    'identificador_externo_2' => null,
    'identificador_externo_3' => null,
    'identificador_externo_4' => null,
    'referencia_adicional' => null,
    'fecha_vencimiento' => null,
    'id_fp' => 4,
];

$lote[] = $itemLote; // El lote puede contener un máximo de 50 items 

$payload = [
    'lote' => $lote,
    'credenciales' => [
        'id_organismo' => 0,
        'id_usuario' => 0,
        'password' => null,
        'hash' => null
    ],
];
Epagos::crearOperacionesLote($payload);
```

**Estructura de la respuesta:**

| Propiedad          | Tipo  | Descripción                                                                                                                                 |
|--------------------|-------|---------------------------------------------------------------------------------------------------------------------------------------------|
| `operaciones_lote` | array | Listado de los ID de transacción generados por Epagos. En caso de haber especificado `referencia_adicional`, se creará un array asociativo. |
| `monto_lote`       | float | La suma total de los montos de cada lote item.                                                                                              |

## ⚙️ Jobs y Eventos

Este paquete utiliza el sistema de Jobs y Eventos de Laravel para manejar la lógica relacionada con los pagos.

🛠️ `EpagosBridge\Jobs\VerificarPago`

Se trata de un job que se encarga de verificar el estado de un pago (por ejemplo, en la respuesta del webhook). Este job
puede despachar diferentes eventos según el resultado de la verificación.

**Eventos posibles**:

- ✅ `PagoAcreditado`: Se dispara cuando el pago fue acreditado.
- ❌ `PagoRechazado`: Se dispara cuando el pago fue rechazado ó vencido.
- 🔄 `PagoDevuelto`: Se dispara cuando el pago fue devuelto al pagador.

Al crear el listener del evento tenes disponible:
- `$this->idTransaccion` // integer
- `$this->concepto` // null|string
- `$this->boleta` // EpagosBridge\Models\Boleta

## ⚙️ Comandos

Además, se recomienda registrar el siguiente comando cada **3 minutos o más** para la verificación de los pagos en caso de que el webhook falle o no esté configurado.

```
php artisan epagos:sincronizar-pagos
php artisan epagos:limpiar-logs // Para reducir el peso de la tabla
```

## 📄 Licencia

Este proyecto está licenciado bajo la licencia MIT. Ver el archivo [LICENSE](./LICENSE) para más detalles.
