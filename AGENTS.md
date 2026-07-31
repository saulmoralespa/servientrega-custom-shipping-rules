# Servientrega Custom Shipping Rules – Guía para agentes de IA

## Descripción general

Mini plugin de WordPress / WooCommerce que extiende **Shipping Servientrega WooCommerce** para permitir:

1. **Costo fijo de envío** — Ignorar la cotización de la API de Servientrega y usar un valor definido por el administrador.
2. **Envío gratis por monto mínimo** — Cuando el subtotal del carrito alcanza un umbral configurado, el envío es $0.

Ambas opciones **evitan la llamada a la API SOAP** de Servientrega, ahorrando latencia (~500ms) y consumo de cuota de API.

## Requisitos

| Requisito | Valor |
|-----------|-------|
| PHP | 8.2+ |
| WordPress | 6.0+ |
| WooCommerce | 4.0+ |
| Plugin obligatorio | `shipping-servientrega-woocommerce` |

## Arquitectura

```
servientrega-custom-shipping-rules/
├── servientrega-custom-shipping-rules.php   → Archivo principal: header, dependencias, registro de tabs, lógica del filtro
├── includes/
│   └── admin/
│       └── custom_rules.php                 → Contenido de la pestaña de configuración
└── AGENTS.md                                → Este archivo
```

## Configuración almacenada

Los ajustes se almacenan dentro de la opción `woocommerce_servientrega_shipping_settings` del plugin principal:

| Clave | Tipo | Descripción |
|-------|------|-------------|
| `servientrega_custom_enable_fixed_cost` | `yes`/`no` | Habilitar costo fijo |
| `servientrega_custom_fixed_cost_value` | `float` | Valor del costo fijo en COP |
| `servientrega_custom_enable_free_shipping` | `yes`/`no` | Habilitar envío gratis |
| `servientrega_custom_free_shipping_threshold` | `float` | Monto mínimo para envío gratis en COP |
| `servientrega_custom_free_shipping_start_date` | `string` (`Y-m-d`) | Fecha de inicio del rango de envío gratis (vacío = sin límite inicial) |
| `servientrega_custom_free_shipping_end_date` | `string` (`Y-m-d`) | Fecha de fin del rango de envío gratis (vacío = sin límite final) |

## Página de configuración

**Ubicación:** WooCommerce → Ajustes → Envío → Servientrega → Pestaña "Reglas personalizadas"

Se registra mediante los filtros del sistema de tabs de Servientrega:
- `servientrega_shipping_tabs` — agrega el slug de la tab
- `servientrega_shipping_tabs_labels` — agrega el label visible
- `servientrega_shipping_tab_file` — especifica la ruta del archivo de contenido

## Hooks utilizados

### `servientrega_shipping_pre_calculate_cost`

Filtro de cortocircuito del plugin principal. Se dispara **antes** de la llamada `Shipping_Servientrega_WC::liquidation()`.

**Parámetros:**
- `$pre_response` (mixed) — `null` por defecto
- `$params` (array) — Parámetros de la liquidación (IdProducto, Piezas, etc.)
- `$data_products` (array) — Datos del paquete (weight, dimensions, total_valorization)
- `$origin` (string) — Código DANE de la ciudad de origen
- `$destine` (string) — Código DANE de la ciudad de destino

**Retorno:**
- `null` → Continúa con la llamada API normal de Servientrega
- `object` con `->ValorTotal` → Usa ese valor como costo de envío, sin llamar a la API

### Filtros de tabs (para registro en admin)

- `servientrega_shipping_tabs` — Array de slugs de tabs
- `servientrega_shipping_tabs_labels` — Array asociativo slug → label
- `servientrega_shipping_tab_file` — Ruta del archivo PHP para la tab

## Lógica de prioridad

```
1. SI envío gratis habilitado Y fecha actual dentro del rango (si hay fechas configuradas) Y subtotal >= threshold
   → Retornar ValorTotal = 0

2. SI costo fijo habilitado Y valor > 0
   → Retornar ValorTotal = fixed_cost_value

3. SI ninguna regla aplica
   → Retornar null (API de Servientrega calcula)
```

**Nota:** El envío gratis siempre tiene prioridad sobre el costo fijo.

**Rango de fechas:** Si `servientrega_custom_free_shipping_start_date` y/o `servientrega_custom_free_shipping_end_date` están vacíos, ese límite no aplica. La comparación es inclusiva y usa la zona horaria de WordPress (`wp_date('Y-m-d')`).

## Funciones principales

| Función | Propósito |
|---------|-----------|
| `servientrega_custom_rules_init()` | Bootstrap del plugin |
| `servientrega_custom_rules_check_dependencies()` | Verifica WooCommerce y Servientrega activos |
| `servientrega_custom_rules_add_tab_slug()` | Registra slug de tab |
| `servientrega_custom_rules_add_tab_label()` | Registra label de tab |
| `servientrega_custom_rules_tab_file()` | Retorna ruta del archivo de la tab |
| `servientrega_custom_rules_is_free_shipping_date_active()` | Verifica si la fecha actual está dentro del rango configurado |
| `servientrega_custom_rules_validate_date()` | Valida formato `Y-m-d` al guardar (en `includes/admin/custom_rules.php`) |
| `servientrega_custom_rules_calculate()` | Lógica del filtro de cortocircuito |

## Convenciones de código

- Prefijo de funciones: `servientrega_custom_rules_`
- Prefijo de opciones: `servientrega_custom_`
- Constantes: `SERVIENTREGA_CUSTOM_RULES_VERSION`, `SERVIENTREGA_CUSTOM_RULES_PATH`
- PHP 8.2+ con tipos en firmas de función
- Sin clases, arquitectura funcional simple

## Notas para modificaciones

- Para agregar nuevas reglas, extender la función `servientrega_custom_rules_calculate()`.
- La prioridad del filtro es 10; otros plugins pueden engancharse con prioridad mayor para sobreescribir.
- El subtotal se obtiene de `WC()->cart->get_subtotal()` (sin impuestos ni descuentos de cupón).
- Si se necesita considerar cupones, usar `WC()->cart->get_cart_contents_total()` en su lugar.
- La configuración se guarda en la misma opción del plugin principal para mantener coherencia.
