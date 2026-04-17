# Servientrega Custom Shipping Rules

Plugin complementario para [Shipping Servientrega WooCommerce](https://shop.saulmoralespa.com/producto/plugin-shipping-servientrega-woocommerce/) que permite definir reglas de envío personalizadas sin consultar la API de Servientrega, reduciendo latencia y consumo de cuota.

## Características

- **Costo fijo de envío** — Aplica un valor fijo en COP en lugar de cotizar con la API de Servientrega.
- **Envío gratis por monto mínimo** — Ofrece envío gratuito cuando el subtotal del carrito alcanza un umbral configurado. Esta regla tiene prioridad sobre el costo fijo.

---

## Requisitos

| Requisito | Versión mínima |
|---|---|
| PHP | 8.2+ |
| WordPress | 6.0+ |
| WooCommerce | 4.0+ |
| Shipping Servientrega WooCommerce | Cualquier versión activa |

---

## Instalación

1. Descarga o clona este repositorio en la carpeta `wp-content/plugins/` de tu WordPress.
2. Asegúrate de que el plugin **Shipping Servientrega WooCommerce** esté instalado y activo.
3. Ve a **WordPress Admin → Plugins** y activa **Servientrega Custom Shipping Rules**.

> Si falta alguna dependencia (WooCommerce o Shipping Servientrega WooCommerce), el plugin mostrará un aviso en el administrador y no se inicializará.

---

## Uso

### Acceder a la configuración

1. Ve a **WooCommerce → Ajustes → Envío**.
2. Haz clic en el método **Servientrega**.
3. Selecciona la pestaña **Reglas personalizadas**.

### Costo fijo de envío

| Campo | Descripción |
|---|---|
| Habilitar costo fijo | Activa la regla de costo fijo. |
| Valor del costo fijo (COP) | Monto que se cobrará como envío. Acepta múltiplos de 100. |

Cuando esta regla está activa, **no se llama a la API de Servientrega**. El costo ingresado se aplica a todos los pedidos sin importar el destino ni el peso.

### Envío gratis por monto mínimo

| Campo | Descripción |
|---|---|
| Habilitar envío gratis | Activa la regla de envío gratis. |
| Monto mínimo para envío gratis (COP) | Subtotal del carrito a partir del cual el envío es $0. |

> **Prioridad:** Si ambas reglas están activas y el subtotal cumple el umbral, el envío gratis tiene precedencia sobre el costo fijo.

### Comportamiento por regla

| Condición | Resultado |
|---|---|
| Envío gratis activo y subtotal ≥ umbral | Envío = $0, **sin llamada a la API** |
| Costo fijo activo | Envío = valor fijo, **sin llamada a la API** |
| Ninguna regla activa | Se consulta la API de Servientrega normalmente |

### Guardar cambios

Haz clic en el botón **Guardar cambios** al final del formulario para aplicar la configuración.

---

## Autor

**Saúl Morales Pacheco** — [saulmoralespa.com](https://saulmoralespa.com)
