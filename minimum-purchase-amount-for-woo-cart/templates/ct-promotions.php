<?php
	wp_enqueue_style('cttalks-promotions', CT_MPAC_DIR_URL . 'assets/css/ct-promotions.css', false, CT_MPAC_VERSION);
  $links = array(
	array('title' => 'Min/Max Quantities', 'link'=>'https://automattic.pxf.io/1rY3vD', 'description'=>'This neat little extension allows you to define minimum/maximum thresholds and multiple/group amounts per product (including variations) to restrict the quantities of items which can be purchased.'),
	array('title' => 'User, Role, Group Based Pricing', 'link'=>'https://automattic.pxf.io/WqeLMJ', 'description'=>'The WISDM Customer Specific Pricing plugin makes it easy for you to set specialized prices in bulk, create flat rate or percentage discount rules for multiple products or categories, and create cart discounts.'),
	array('title' => 'Role Based Pricing', 'link'=>'https://automattic.pxf.io/EK6jMW', 'description'=>'WooCommerce Role Based Pricing extension empowers you to set your product prices based on user roles and individual customers. You can discount or markup prices by fixed or percentage amount.'),
	array('title' => 'WooCommerce Subscriptions', 'link'=>'https://automattic.pxf.io/baBeMg', 'description'=>'With WooCommerce Subscriptions, you can create and manage products with recurring payments — payments that will give you residual revenue you can track and count on.'),
	array('title' => 'Dynamic Pricing', 'link'=>'https://automattic.pxf.io/R5rXMX', 'description'=>'Configure bulk discounts for each product in your store by creating a table of quantities and discount amounts. Choose from fixed price adjustments, percentage adjustments or set a fixed price for the product. Optionally choose roles the pricing rule should be applied for.'),
	array('title' => 'WooCommerce Products Compare', 'link'=>'automattic.pxf.io/6e9XAV', 'description'=>'When trying to decide between many similar products, online shoppers want to compare prices and other features side-by-side to find the product that’s right for them.'),
  );
	?>
<div class="ct-promotions-flex-container">
<?php
foreach ($links as $item) {
	?>
	  <div class="col">
	<a href="<?php echo esc_url($item['link']); ?>" >
		  <div class="col-title">
		  <h3><?php echo esc_html_e($item['title']); ?></h3>
		  </div>
		  <div class="col-excerpt">
			<p><?php echo esc_html_e($item['description']); ?></p>
		  </div>
		  <div class="col-link-button">
			<a href="<?php echo esc_url($item['link']); ?>" target="_blank">
			  <button class="button button-ct-promo">Know More</button>
			</a>
	  </div>
	</a>
	  </div>
	<?php
}
?>
</div>
