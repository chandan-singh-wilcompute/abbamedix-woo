<?php
/**
 * Loop Price
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/loop/price.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see         https://woocommerce.com/document/template-structure/
 * @package     WooCommerce\Templates
 * @version     1.6.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $product;
$currency_symbol = get_woocommerce_currency_symbol();
?>


<?php
if ($price_html = $product->get_price_html()) {
    // Create a new DOMDocument object
    libxml_use_internal_errors(true); // Suppress warnings
    $dom = new DOMDocument();
    $dom->loadHTML($price_html);
    libxml_clear_errors(); // Clear any collected errors
    $price_elements = $dom->getElementsByTagName('span');
    foreach ($price_elements as $element) {
        if ($element->getAttribute('class') === 'woocommerce-Price-currencySymbol') {
            $price_node = $element->nextSibling;
            $price = $price_node->nodeValue;
            break;
        }
    }
}
?>
<?php
$package_size_value = $product->get_attribute('package-size');
if (!empty($package_size_value)) {
    // Check if the string contains '|'
    if (strpos($package_size_value, '|') !== false) {
        // If '|' is found, split the string using '|'
        $package_size = explode('|', $package_size_value);
        // Assign the first part to $package_size[0]
        $package_size_value = $package_size[0];
    } elseif (strpos($package_size_value, ',') !== false) {
        // If ',' is found, split the string using ','
        $package_size = explode(',', $package_size_value);
        // Assign the second part to $package_size[1]
        $package_size_value = $package_size[1];
    }
}
?>


<?php if ( $price_html = $product->get_price_html() ) : ?>

    <span class="price prodcard-price">
		<?php 
		    echo $currency_symbol . $price; 
		if (isset($package_size_value) && !empty($package_size_value)) {
            echo '/' . esc_html($package_size_value);
		}
		?>
	</span>

<?php endif; ?>

<?php
echo '<div class="cardFooter">
        <div class="addtoCart">
            <label>Add to cart</label>
            <div class="productQuantity">
                <button type="button" class="btn minusQuntity">−</button>
                <input type="number" class="quantity" value="1" min="1">
                <button type="button" class="btn addQuntity">+</button>
            </div>
            </div>
            <a href="#!">More Info</a>
      </div>';
?>