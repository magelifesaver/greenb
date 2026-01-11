<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<h1><?php esc_html_e( 'CartLink Generator', 'cartlink-generator' ); ?></h1>
<div class="wrap wrapnput">
    <input type="text" id="product-search" placeholder="<?php esc_html_e( 'Search for a product to add...', 'cartlink-generator' ); ?>">
</div>
<div class="clear clearfix"></div>
<div class="wrapautogen"></div>
<div id="selected-products" style="display:none"></div>

<br>
<!-- Add fixed subtotal field -->
<p  id="expiredays"><label style="display: none;margin-left: 10px; margin-top: 20px; margin-bottom: 10px; ">Custom Subtotal Discount (if there is): 
    <input type="text" class="pregenerate" id="fixed-subtotal" style="width: 50px; max-width: 100%;">
</label>
</p>

<p id="customdiscount"><label style="display: none;margin-left: 10px; margin-top: 20px; margin-bottom: 10px; ">Expire Days: <input type="number" class="pregenerate" id="expire-days"  value="1" placeholder="Default 1" 
    style="width: 50px; max-width: 100%;">
</label></p>

<div class="pregenerate" style="margin-top: 20px; margin-left: 10px; display: none;">
<button id="generate-link" class="pregenerate button button-primary" >
    <?php esc_html_e( 'Generate Link', 'cartlink-generator' ); ?>
</button>
</div>

<div class="autogenresponse"></div>


<div style="margin-top:100px">Any questions or suggestions? <a href="https://guaven.com/contact/solution-request/" target="_blank">Contact us</a>.</div>
