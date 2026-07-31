<?php
/**
 * Plugin Name: Servientrega Custom Shipping Rules
 * Description: Define costo fijo de envío y reglas de envío gratis para Servientrega WooCommerce
 * Version: 1.0.1
 * Author: Saúl Morales Pacheco
 * Author URI: https://saulmoralespa.com
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Requires at least: 6.0
 * Tested up to: 6.9
 * Requires PHP: 8.2
 * Requires Plugins: woocommerce,shipping-servientrega-woocommerce
 * WC requires at least: 4.0
 * WC tested up to: 10.4
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('SERVIENTREGA_CUSTOM_RULES_VERSION')) {
    define('SERVIENTREGA_CUSTOM_RULES_VERSION', '1.0.1');
}

if (!defined('SERVIENTREGA_CUSTOM_RULES_PATH')) {
    define('SERVIENTREGA_CUSTOM_RULES_PATH', plugin_dir_path(__FILE__));
}

/**
 * Inicializa el plugin después de que todos los plugins estén cargados
 */
add_action('plugins_loaded', 'servientrega_custom_rules_init');
add_action(
    'before_woocommerce_init',
    function () {
        if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__ );
        }
    }
);

function servientrega_custom_rules_init(): void
{
    if (!servientrega_custom_rules_check_dependencies()) {
        return;
    }

    // Registrar tab en el sistema de Servientrega
    add_filter('servientrega_shipping_tabs', 'servientrega_custom_rules_add_tab_slug');
    add_filter('servientrega_shipping_tabs_labels', 'servientrega_custom_rules_add_tab_label');
    add_filter('servientrega_shipping_tab_file', 'servientrega_custom_rules_tab_file', 10, 2);

    // Hook principal: cortocircuitar la llamada API de Servientrega
    add_filter('servientrega_shipping_pre_calculate_cost', 'servientrega_custom_rules_calculate', 10, 5);
}

/**
 * Verifica que las dependencias estén activas
 */
function servientrega_custom_rules_check_dependencies(): bool
{
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', function () {
            echo '<div class="error notice"><p>';
            echo esc_html__('Servientrega Custom Shipping Rules requiere WooCommerce activo.', 'servientrega-custom-rules');
            echo '</p></div>';
        });
        return false;
    }

    if (!class_exists('Shipping_Servientrega_WC')) {
        add_action('admin_notices', function () {
            echo '<div class="error notice"><p>';
            echo esc_html__('Servientrega Custom Shipping Rules requiere el plugin Shipping Servientrega WooCommerce activo.', 'servientrega-custom-rules');
            echo '</p></div>';
        });
        return false;
    }

    return true;
}

/**
 * Agrega el slug de la tab al array de tabs
 */
function servientrega_custom_rules_add_tab_slug(array $tabs): array
{
    $tabs[] = 'custom_rules';
    return $tabs;
}

/**
 * Agrega el label de la tab
 */
function servientrega_custom_rules_add_tab_label(array $tabs): array
{
    $tabs['custom_rules'] = __('Reglas personalizadas', 'servientrega-custom-rules');
    return $tabs;
}

/**
 * Especifica la ruta del archivo de contenido de la tab
 */
function servientrega_custom_rules_tab_file(string $path, string $tab): string
{
    if ($tab === 'custom_rules') {
        return SERVIENTREGA_CUSTOM_RULES_PATH . 'includes/admin/custom_rules.php';
    }
    return $path;
}

/**
 * Verifica si la fecha actual está dentro del rango de envío gratis.
 * Fechas vacías no restringen el rango.
 */
function servientrega_custom_rules_is_free_shipping_date_active(string $start_date, string $end_date): bool
{
    $today = wp_date('Y-m-d');

    if ($start_date !== '' && $today < $start_date) {
        return false;
    }

    if ($end_date !== '' && $today > $end_date) {
        return false;
    }

    return true;
}

/**
 * Lógica principal: determina si aplicar costo fijo o envío gratis
 *
 * @param mixed $pre_response Respuesta previa (null por defecto)
 * @param array $params Parámetros de la liquidación
 * @param array $data_products Datos de productos (peso, dimensiones, etc.)
 * @param string $origin Código DANE de origen
 * @param string $destine Código DANE de destino
 * @return object|null Objeto con ValorTotal o null para continuar con la API
 */
function servientrega_custom_rules_calculate($pre_response, array $params, array $data_products, string $origin, string $destine)
{
    $wc_settings = get_option('woocommerce_servientrega_shipping_settings');

    $enable_free_shipping    = ($wc_settings['servientrega_custom_enable_free_shipping'] ?? 'no') === 'yes';
    $free_shipping_threshold = (float) ($wc_settings['servientrega_custom_free_shipping_threshold'] ?? 0);
    $free_shipping_start_date = $wc_settings['servientrega_custom_free_shipping_start_date'] ?? '';
    $free_shipping_end_date   = $wc_settings['servientrega_custom_free_shipping_end_date'] ?? '';
    $enable_fixed_cost       = ($wc_settings['servientrega_custom_enable_fixed_cost'] ?? 'no') === 'yes';
    $fixed_cost_value        = (float) ($wc_settings['servientrega_custom_fixed_cost_value'] ?? 0);

    // Obtener subtotal del carrito
    $cart_subtotal = 0;
    if (WC()->cart) {
        $cart_subtotal = (float) WC()->cart->get_subtotal();
    }

    // Regla 1: Envío gratis (tiene prioridad)
    $is_free_shipping_date_active = servientrega_custom_rules_is_free_shipping_date_active($free_shipping_start_date, $free_shipping_end_date);
    if ($enable_free_shipping && $is_free_shipping_date_active && $free_shipping_threshold > 0 && $cart_subtotal >= $free_shipping_threshold) {
        return (object) ['ValorTotal' => 0];
    }

    // Regla 2: Costo fijo
    if ($enable_fixed_cost && $fixed_cost_value > 0) {
        return (object) ['ValorTotal' => $fixed_cost_value];
    }

    // No aplicar reglas: dejar que la API de Servientrega calcule
    return null;
}
