<?php

/**
 * Product page - inline pricing table - horizontal
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
                <tr>

                    <?php foreach ($table_data as $row): ?>
                        <td>
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
                    <?php endforeach; ?>

                    <td class="last_cell"></td>
                </tr>
                <tr>

                    <?php foreach ($table_data as $row): ?>
                        <td>
                            <span class="amount">
                                <?php echo $row['display_price']; ?>
                            </span>
                        </td>
                    <?php endforeach; ?>

                    <td class="last_cell"></td>
                </tr>
            </tbody>
        </table>

    </div>
</div>
