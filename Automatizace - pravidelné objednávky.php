/**
 * Plugin Name: WooCommerce Custom Subscriptions
 * Plugin URI: https://github.com/adamvarmuza/custom-subscriptions
 * Description: Přidává předplatné jako vlastnost produktu.
 * Version: 1.0.0
 * Author: Adam Varmuža
 * Author URI: https://www.suseneprazene.cz
 */


if (!defined('ABSPATH')) exit;

// Přidání polí do produktu
add_action('woocommerce_product_options_general_product_data', function () {
    woocommerce_wp_checkbox([
        'id' => '_enable_custom_subscription',
        'label' => __('Povolit předplatné', 'custom-subscription')
    ]);
});

add_action('woocommerce_process_product_meta', function ($post_id) {
    $enable = isset($_POST['_enable_custom_subscription']) ? 'yes' : 'no';
    update_post_meta($post_id, '_enable_custom_subscription', $enable);
});

// Přidání polí do stránky produktu (front-end)
add_action('woocommerce_before_add_to_cart_button', function () {
    global $product;
    if (get_post_meta($product->get_id(), '_enable_custom_subscription', true) !== 'yes') return;

    echo '<div class="custom-subscription-options">
        <label for="subscription_interval">Interval platby:</label>
        <select name="subscription_interval" required>
            <option value="monthly">Měsíčně</option>
            <option value="quarterly">Čtvrtletně</option>
            <option value="yearly">Ročně</option>
        </select>

        <label for="subscription_length">Délka předplatného:</label>
        <select name="subscription_length" required>';
    for ($i = 1; $i <= 12; $i++) {
        echo '<option value="' . $i . '">' . $i . ' měsíců</option>';
    }
    echo '</select>

        <label><input type="checkbox" name="subscription_autorenew" value="1"> Automaticky prodloužit</label>
    </div>';
});

// Přidání do košíku
add_filter('woocommerce_add_cart_item_data', function ($cart_item_data, $product_id) {
    if (isset($_POST['subscription_interval'])) {
        $cart_item_data['subscription_interval'] = wc_clean($_POST['subscription_interval']);
        $cart_item_data['subscription_length'] = intval($_POST['subscription_length']);
        $cart_item_data['subscription_autorenew'] = isset($_POST['subscription_autorenew']);
    }
    return $cart_item_data;
}, 10, 2);

// Zobrazení v košíku
add_filter('woocommerce_get_item_data', function ($item_data, $cart_item) {
    if (isset($cart_item['subscription_interval'])) {
        $item_data[] = ['key' => 'Interval platby', 'value' => $cart_item['subscription_interval']];
        $item_data[] = ['key' => 'Délka předplatného', 'value' => $cart_item['subscription_length'] . ' měsíců'];
        $item_data[] = ['key' => 'Automatické prodloužení', 'value' => $cart_item['subscription_autorenew'] ? 'Ano' : 'Ne'];
    }
    return $item_data;
}, 10, 2);

// Uložení do objednávky
add_action('woocommerce_checkout_create_order_line_item', function ($item, $cart_item_key, $values, $order) {
    if (isset($values['subscription_interval'])) {
        $item->add_meta_data('Interval platby', $values['subscription_interval']);
        $item->add_meta_data('Délka předplatného', $values['subscription_length'] . ' měsíců');
        $item->add_meta_data('Automatické prodloužení', $values['subscription_autorenew'] ? 'Ano' : 'Ne');
    }
});

// Možné napojení na WP Swings Subscriptions – zde by probíhalo vytvoření skutečného subscription objektu
// (např. pomocí wc_create_order() + hooks pluginu), to přidáme dle zpětné vazby.
