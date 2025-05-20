<?php
defined('ABSPATH') || exit;

$saved_methods = WC()->payment_gateways->get_available_payment_gateways();

if ($saved_methods) : ?>
    <table class="shop_table shop_table_responsive my_account_saved_methods">
        <thead>
            <tr>
                <th><?php esc_html_e('Method', 'woocommerce'); ?></th>
                <th><?php esc_html_e('Actions', 'woocommerce'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($saved_methods as $method) : ?>
                <tr>
                    <td><?php echo esc_html($method->get_title()); ?></td>
                    <td>
                        <a href="#" class="button"><?php esc_html_e('Delete', 'woocommerce'); ?></a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else : ?>
    <p><?php esc_html_e('No saved payment methods found.', 'woocommerce'); ?></p>
<?php endif; ?>
