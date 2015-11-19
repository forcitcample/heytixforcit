<?php

/**
 * Product page - inline pricing table - vertical
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

?>

<div class="rp_wcdpd_product_page">
    <div class="rp_wcdpd_product_page_title"><?php echo $this->opt['localization']['quantity_discounts']; ?></div>
    <div class="rp_wcdpd_pricing_table">

        <table>
            <tbody>

                <?php foreach ($table_data as $row): ?>

                    <tr>
                        <td class="rp_wcdpd_longer_cell">
                            <span class="quantity">
                                <?php
                                    if ($row['min'] == $row['max']) {
                                        echo $row['min'];
                                    }
                                    else if ($row['max'] == 2147483647) {
                                        echo $row['min'] . '+';
                                    }
                                    else {
                                        echo $row['min'] . '-' . $row['max'];
                                    }
                                ?>
                            </span>
                        </td>
                        <td class="rp_wcdpd_longer_cell">
                            <span class="amount">
                                <?php echo $row['display_price']; ?>
                            </span>
                        </td>
                        <td class="last_cell"></td>
                    </tr>

                <?php endforeach; ?>

            </tbody>
        </table>

    </div>
</div>
