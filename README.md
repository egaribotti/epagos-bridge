# Epagos Bridge

![Versión](https://img.shields.io/badge/versión-1.0.8-blue.svg)

Este paquete permite integrar Epagos de forma rápida y sencilla en cualquier proyecto con Laravel.
Incluye una implementación básica de medidas de seguridad y está diseñado para facilitar la generación de solicitudes de
pago, así como la gestión de las respuestas recibidas desde la plataforma.

## 📦 Instalación

Ejecutar composer.

```bash
composer require egaribotti/epagos-bridge
```

Agregar el service provider en el archivo `config/app.php`.

```php
return [
    'providers' => [
        EpagosBridge\EpagosServiceProvider::class,
    ],
];
```

## 🔐 Variables de Entorno

Para que el paquete funcione correctamente, es necesario definir las siguientes variables de entorno en el archivo
`.env` del proyecto:

```env
EPAGOS_WSDL=
EPAGOS_WEBHOOK_TOKEN=
```

## 🗂️ Base de datos

Es necesario crear las siguientes tablas para que el paquete funcione correctamente. A continuación se detalla el modelo
entidad-relación (DER) que define la estructura de la base de datos:

![DER](./epagos-bridge-der.png)

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
Epagos::crearPago($payload);
Epagos::crearOperacionesLote($payload);
Epagos::obtenerMediosPago($credenciales);
Epagos::validarVencimiento($operaciones);
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
];
Epagos::crearPago($payload);
```

**Estructura de la respuesta:**

| Propiedad              | Tipo    | Descripción                       |
|------------------------|---------|-----------------------------------|
| `boleta_id`            | integer | Id de la boleta creada.           |
| `id_transaccion`       | integer | Id de transacción en Epagos.      |
| `referencia_adicional` | string  | En caso de que haya especificado. |
| `monto_final`          | float   | El monto final a pagar.           |
| `url`                  | string  | La URL para ir a pagar.           |

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

## 📄 Licencia

Este proyecto está licenciado bajo la licencia MIT. Ver el archivo [LICENSE](./LICENSE) para más detalles.
