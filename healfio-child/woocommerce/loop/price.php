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
    $price_html = "";
    if ( $product->is_type( 'variable' ) ) {
        $available_variations = $product->get_available_variations();
        $price_per_gram_list = [];

        foreach ( $available_variations as $variation ) {
            $variation_obj = new WC_Product_Variation( $variation['variation_id'] );
            $variation_price = $variation_obj->get_price();
            $package_size = $variation_obj->get_attribute( 'package-sizes' );

            // Extract numeric value from package size (e.g., "3.5 g" → 3.5)

            if (strpos($package_size, 'g') !== false) {
                echo "Contains g";
            }

            preg_match('/[\d.]+/', $package_size, $matches);
            $grams = isset($matches[0]) ? floatval($matches[0]) : 0;

            if ( $grams > 0 ) {
                $price_per_gram = $variation_price / $grams;
                $price_per_gram_list[] = $price_per_gram;
            }
        }

        if ( ! empty( $price_per_gram_list ) ) {
            $min_price = min( $price_per_gram_list );
            $max_price = max( $price_per_gram_list );

            $price_html = ( $min_price !== $max_price ) ? number_format($min_price, 2) . ' /gr – ' . number_format($max_price, 2) . ' /gr' : number_format($min_price, 2) . ' /gr';
        } 

    } else {
        $regular_price = $product->get_price();
        $package_size = $product->get_attribute( 'package-sizes' );

        preg_match('/[\d.]+/', $package_size, $matches);
        $grams = isset($matches[0]) ? floatval($matches[0]) : 0;

        if ( $grams > 0 ) {
            $price_per_gram = $regular_price / $grams;
            $price_html = $price_per_gram . ' /gr';
        }

    }
    if ($price_html != "") {
        $price_html = $currency_symbol . $price_html;
    }
    ?>
    <div class="price prodcard-price" data-base-price="<?php echo $price_html; ?>">

        <div class="productQuantity">
            <button type="button" class="btn qty-minus" onclick="decreaseQty(this)">−</button>
            <input type="number" class="quantity" value="1" min="1" onchange="updateTotal(this)">
            <button type="button" class="btn qty-plus" onclick="increaseQty(this)">+</button>
        </div>

        <span class="price-html">
            <?php echo $price_html; ?>
        </span>
    </div>
<?php
$link = apply_filters( 'woocommerce_loop_product_link', get_the_permalink(), $product );
?>

<?php 
   
    // echo '<div class="cardFooter">';
    // echo '  <div class="addtoCart">
    //             <label>Select Size</label>
    //             <div class="productQuantity">
    //                 <button type="button" class="btn minusQuantity">−</button>
    //                 <input type="number" class="quantity" value="1" min="1">
    //                 <button type="button" class="btn addQuantity">+</button>
    //             </div>
    //         </div>';
    // echo '  <a href="' . esc_url($link) . '">More Info</a>';
    // echo '</div>';
?>

