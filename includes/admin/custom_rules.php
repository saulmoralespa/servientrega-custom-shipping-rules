<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Valida que una fecha tenga formato Y-m-d y sea válida.
 * Retorna la fecha si es válida, o cadena vacía en caso contrario.
 */
function servientrega_custom_rules_validate_date(string $date): string
{
    if ($date === '') {
        return '';
    }

    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return '';
    }

    $validated = date('Y-m-d', $timestamp);
    return $validated === $date ? $validated : '';
}

$wc_settings = get_option('woocommerce_servientrega_shipping_settings');
$success_message = '';

if (isset($_POST['save'])) {
    if (!wp_verify_nonce($_POST['_wpnonce'], 'woocommerce-settings')) {
        return '';
    }

    $wc_settings['servientrega_custom_enable_fixed_cost'] = isset($_POST['servientrega_custom_enable_fixed_cost']) ? 'yes' : 'no';
    $wc_settings['servientrega_custom_fixed_cost_value'] = sanitize_text_field($_POST['servientrega_custom_fixed_cost_value'] ?? '');
    $wc_settings['servientrega_custom_enable_sale_rule'] = isset($_POST['servientrega_custom_enable_sale_rule']) ? 'yes' : 'no';
    $wc_settings['servientrega_custom_enable_free_shipping'] = isset($_POST['servientrega_custom_enable_free_shipping']) ? 'yes' : 'no';
    $wc_settings['servientrega_custom_free_shipping_threshold'] = sanitize_text_field($_POST['servientrega_custom_free_shipping_threshold'] ?? '');

    $start_date = sanitize_text_field($_POST['servientrega_custom_free_shipping_start_date'] ?? '');
    $end_date = sanitize_text_field($_POST['servientrega_custom_free_shipping_end_date'] ?? '');
    $wc_settings['servientrega_custom_free_shipping_start_date'] = servientrega_custom_rules_validate_date($start_date);
    $wc_settings['servientrega_custom_free_shipping_end_date'] = servientrega_custom_rules_validate_date($end_date);

    update_option('woocommerce_servientrega_shipping_settings', $wc_settings);

    $success_message = '<div class="notice notice-success"><p>' . esc_html__('Configuración guardada.', 'servientrega-custom-rules') . '</p></div>';
}

$enable_fixed_cost = $wc_settings['servientrega_custom_enable_fixed_cost'] ?? 'no';
$fixed_cost_value = $wc_settings['servientrega_custom_fixed_cost_value'] ?? '';
$enable_sale_rule = $wc_settings['servientrega_custom_enable_sale_rule'] ?? 'no';
$enable_free_shipping = $wc_settings['servientrega_custom_enable_free_shipping'] ?? 'no';
$free_shipping_threshold = $wc_settings['servientrega_custom_free_shipping_threshold'] ?? '';
$free_shipping_start_date = $wc_settings['servientrega_custom_free_shipping_start_date'] ?? '';
$free_shipping_end_date = $wc_settings['servientrega_custom_free_shipping_end_date'] ?? '';

$checked_fixed_cost = checked($enable_fixed_cost, 'yes', false);
$checked_sale_rule = checked($enable_sale_rule, 'yes', false);
$checked_free_shipping = checked($enable_free_shipping, 'yes', false);

$label_title = esc_html__('Reglas personalizadas de envío', 'servientrega-custom-rules');
$label_description = esc_html__('Configure un costo fijo de envío o envío gratis según el monto del carrito. Estas reglas evitan la llamada a la API de Servientrega.', 'servientrega-custom-rules');
$label_enable_fixed = esc_html__('Habilitar costo fijo', 'servientrega-custom-rules');
$label_fixed_desc = esc_html__('Usar un costo fijo en lugar de la cotización de Servientrega', 'servientrega-custom-rules');
$label_fixed_value = esc_html__('Valor del costo fijo (COP)', 'servientrega-custom-rules');
$label_enable_sale = esc_html__('Habilitar regla según productos en oferta', 'servientrega-custom-rules');
$label_sale_desc = esc_html__('Si todos los productos del carrito están en oferta, usar la tarifa de Servientrega; de lo contrario, aplicar el costo fijo.', 'servientrega-custom-rules');
$label_enable_free = esc_html__('Habilitar envío gratis', 'servientrega-custom-rules');
$label_free_desc = esc_html__('Ofrecer envío gratis cuando el subtotal alcance el monto mínimo', 'servientrega-custom-rules');
$label_free_threshold = esc_html__('Monto mínimo para envío gratis (COP)', 'servientrega-custom-rules');
$label_free_start_date = esc_html__('Fecha de inicio (opcional)', 'servientrega-custom-rules');
$label_free_end_date = esc_html__('Fecha de fin (opcional)', 'servientrega-custom-rules');
$label_free_date_desc = esc_html__('Rango de fechas en el que el envío gratis está activo. Deje vacío para que esté siempre activo.', 'servientrega-custom-rules');
$label_save = esc_attr__('Guardar cambios', 'servientrega-custom-rules');

$htmlCustomRules = <<<HTML
$success_message
<h3>$label_title</h3>
<p>$label_description</p>

<table class="form-table">
    <tr>
        <th scope="row">$label_enable_fixed</th>
        <td>
            <input type="checkbox" name="servientrega_custom_enable_fixed_cost" value="yes" $checked_fixed_cost>
            <span class="description">$label_fixed_desc</span>
        </td>
    </tr>
    <tr>
        <th scope="row">$label_fixed_value</th>
        <td>
            <input type="number" name="servientrega_custom_fixed_cost_value" value="$fixed_cost_value" min="0" step="100" style="width: 150px;">
        </td>
    </tr>
    <tr>
        <th scope="row">$label_enable_sale</th>
        <td>
            <input type="checkbox" name="servientrega_custom_enable_sale_rule" value="yes" $checked_sale_rule>
            <span class="description">$label_sale_desc</span>
        </td>
    </tr>
    <tr>
        <th scope="row">$label_enable_free</th>
        <td>
            <input type="checkbox" name="servientrega_custom_enable_free_shipping" value="yes" $checked_free_shipping>
            <span class="description">$label_free_desc</span>
        </td>
    </tr>
    <tr>
        <th scope="row">$label_free_threshold</th>
        <td>
            <input type="number" name="servientrega_custom_free_shipping_threshold" value="$free_shipping_threshold" min="0" step="1000" style="width: 150px;">
        </td>
    </tr>
    <tr>
        <th scope="row">$label_free_start_date</th>
        <td>
            <input type="date" name="servientrega_custom_free_shipping_start_date" value="$free_shipping_start_date">
            <br><span class="description">$label_free_date_desc</span>
        </td>
    </tr>
    <tr>
        <th scope="row">$label_free_end_date</th>
        <td>
            <input type="date" name="servientrega_custom_free_shipping_end_date" value="$free_shipping_end_date">
        </td>
    </tr>
</table>
HTML;

return $htmlCustomRules;
