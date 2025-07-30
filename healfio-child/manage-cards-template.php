<?php
defined( 'ABSPATH' ) || exit;

wc_get_template( 'myaccount/navigation.php' ); // show sidebar nav
echo 'Currently on: ' . ( is_wc_endpoint_url('manage-cards') ? 'Manage Cards Page' : 'Other Page' );
?>
<div class="woocommerce-MyAccount-content">
    <h3>Manage Cards</h3>
    <button id="toggleAddCardForm" style="margin-bottom: 10px;">Add New Card</button>
    <div id="addCardFormContainer" style="display: none; border: 1px solid #ccc; padding: 15px; margin-top: 10px;">
    
        <h3>Add New Card</h3>
        <form method="POST" id="addCardForm" action="">
            <?php wp_nonce_field("moneris_save_card", "moneris_card_nonce"); ?>
            
            <div>
                <label for="first_name">First Name</label>
                <input type="text" name="first_name" id="first_name" required>
            </div>
            <div>
                <label for="last_name">Last Name</label>
                <input type="text" name="last_name" id="last_name" required>
            </div>
            <div>
                <label for="street_name">Street Name</label>
                <input type="text" name="street_name" id="street_name" required>
            </div>
            <div>
                <label for="street_number">Street Number</label>
                <input type="text" name="street_number" id="street_number" required>
            </div>
            <div>
                <label for="postal_code">Postal Code</label>
                <input type="text" name="postal_code" id="postal_code" required>
            </div>

            <div style="margin-top:15px;">
                <label>Credit Card Details</label>
                <iframe id="monerisFrame" src="https://esqa.moneris.com/HPPtoken/index.php?id=ht37S4JVAQ3VT7S&pmmsg=true&css_body=background:white;&css_textbox=border-width:2px;margin-top:5px;border-radius:5px;&display_labels=1&css_label_pan=float:left;width:25%;font-size:1.15em;&css_label_exp=float:left;width:25%;font-size:1.15em;&css_label_cvd=float:left;width:25%;font-size:1.15em;&css_textbox_pan=width:140px;&enable_exp=1&css_textbox_exp=width:40px;&enable_cvd=1&css_textbox_cvd=width:40px&enable_exp_formatting=1&enable_cc_formatting=1" frameborder="0" width="300px" height="200px"></iframe>
            </div>
            <div>
                <input type="hidden" id="moneris_data_key" name="moneris_data_key">
                <button type="button" onclick="doMonerisSubmit();" name="submit_card">Save Card</button>
                
                <!-- <button type="button" onclick="doMonerisSubmit();"><?php esc_html_e("Submit Card Details", "woocommerce"); ?></button> -->

            </div>
        </form>
    </div>

    <script>
        // Trigger Moneris tokenization
        function doMonerisSubmit() {
            var monFrameRef = document.getElementById("monerisFrame").contentWindow;
            monFrameRef.postMessage("tokenize", "https://esqa.moneris.com/HPPtoken/index.php");
            return false;
        }

        // Handle the tokenization response from Moneris
        var respMsg = function (e) {
            var respData = JSON.parse(e.data); // Moneris returns a JSON response
            console.log("Resp Data: ", respData);
            if (respData.responseCode[0] === "001" && respData.dataKey) {
                document.getElementById("moneris_data_key").value = respData.dataKey;
                document.getElementById("addCardForm").submit();
            } else {
                alert("Failed to tokenize credit card. Please try again.");
            }
        };

        window.onload = function () {
            if (window.addEventListener) {
                window.addEventListener("message", respMsg, false);
            } else if (window.attachEvent) {
                window.attachEvent("onmessage", respMsg);
            }
        };

        document.addEventListener("DOMContentLoaded", function () {
            const toggleButton = document.getElementById("toggleAddCardForm");
            const addCardFormContainer = document.getElementById("addCardFormContainer");

            toggleButton.addEventListener("click", function () {
                if (addCardFormContainer.style.display === "none") {
                    addCardFormContainer.style.display = "block";
                } else {
                    addCardFormContainer.style.display = "none";
                }
            });
        });
    </script>
</div>
