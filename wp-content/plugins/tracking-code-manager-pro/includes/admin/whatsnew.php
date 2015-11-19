<?php
function tcmp_ui_whats_new() {
    global $tcmp;
    $tcmp->Options->setShowWhatsNew(FALSE);
    ?>
    <style>
        .tcmp-grid {
            margin-left: auto;
            margin-right: auto;
            border-spacing: 10px;
        }
        .tcmp-grid td {
            text-align: center;
        }
        .tcmp-headline {
            font-size:40px;
            font-weight:bold;
            text-align:center;
        }
    </style>

    <p class="tcmp-headline">Introducing Ecommerce Conversion Tracking</p>
    <table border="0" class="tcmp-grid">
        <tr>
            <td><img src="<?php echo TCMP_PLUGIN_ASSETS ?>landing/edd.png" /></td>
            <td><img src="<?php echo TCMP_PLUGIN_ASSETS ?>landing/woocommerce.png" /></td>
            <td><img src="<?php echo TCMP_PLUGIN_ASSETS ?>landing/wp-ecommerce.png" /></td>
        </tr>
    </table>
    <div style="clear:both; height:30px;"></div>

    <div style="text-align:center; width:auto;">
        <img src="<?php echo TCMP_PLUGIN_ASSETS ?>landing/mockup.png" />
    </div>
    <div style="clear:both; height:30px;"></div>

    <table border="0" class="tcmp-grid">
        <tr>
            <td><iframe width="350" height="210" src="https://www.youtube.com/embed/jgmmMlerFRg"></iframe></td>
            <td><iframe width="350" height="210" src="https://www.youtube.com/embed/TDgoefbdtSI"></iframe></td>
            <td><iframe width="350" height="210" src="https://www.youtube.com/embed/vBjDeb4Ej-I"></iframe></td>
        </tr>
        <tr>
            <td>Track Conversion in Easy Digital Download</td>
            <td>Track Conversion in Woocommerce</td>
            <td>Track Conversion in WP eCommerce</td>
        </tr>
    </table>
    <div style="clear:both"></div>

    <hr/>

    <p class="tcmp-headline">You have UNLIMITED tracking codes<br/>with Tracking Code Manager PRO</p>
    <table border="0" class="tcmp-grid">
        <tr>
            <td style="text-align:left;">
                Tracking Code Manager PRO let you:
                <ul style="list-style-type: disc;">
                    <li>Have unlimited tracking codes</li>
                    <li>Put tracking codes in categories, tags and custom post types</li>
                    <li>Have unlimited combinations and unlimited exclusions</li>
                    <li>Include a tracking code only in latest posts (outstanding for retargeting)</li>
                </ul>
            </td>
            <td><img src="<?php echo TCMP_PLUGIN_ASSETS ?>landing/screenshot-latest.png" style="border:1px dashed red;" /></td>
        </tr>
    </table>

    <table border="0" class="tcmp-grid">
        <tr>
            <td>
                <form method="get" action="<?php echo TCMP_PAGE_MANAGER?>">
                    <input type="hidden" name="page" value="<?php echo TCMP_PLUGIN_NAME?>" />
                    <input type="submit" class="button-primary" value="CONTINUE USING PREMIUM VERSION ››" />
                </form>
            </td>
        </tr>
    </table>
<?php }