<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wb_stn_content">
	
<div class="wb-stn-help-section">
	<h2><?php esc_html_e( 'Help & FAQs', 'wb-sticky-notes' ); ?></h2>

	<div class="wb-stn-accordion">
		<!-- Question 1 -->
		<div class="wb-stn-accordion-item">
			<button class="wb-stn-accordion-button">
				<?php esc_html_e( 'How can I create a new sticky note?', 'wb-sticky-notes' ); ?>
			</button>
			<div class="wb-stn-accordion-content">
				<p><?php esc_html_e( 'Go to any page in the WordPress admin dashboard. From the top admin bar, click Sticky Notes → Create New Note.', 'wb-sticky-notes' ); ?></p>
				<p><?php esc_html_e( 'Note: If you enabled the setting to allow notes only on specific admin screens, this option will be available only on those screens.', 'wb-sticky-notes' ); ?></p>
			</div>
		</div>

		<!-- Question 2 -->
		<div class="wb-stn-accordion-item">
			<button class="wb-stn-accordion-button">
				<?php esc_html_e( 'Can I hide or minimize the notes?', 'wb-sticky-notes' ); ?>
			</button>
			<div class="wb-stn-accordion-content">
				<p><?php esc_html_e( 'Yes. You can globally hide or show all notes using the toggle under the Sticky Notes menu in the admin toolbar.', 'wb-sticky-notes' ); ?></p>
			</div>
		</div>

		<!-- Question 3 -->
		<div class="wb-stn-accordion-item">
			<button class="wb-stn-accordion-button">
				<?php esc_html_e( 'Are sticky notes resizable and movable?', 'wb-sticky-notes' ); ?>
			</button>
			<div class="wb-stn-accordion-content">
				<p><?php esc_html_e( 'Yes. You can drag notes by their header to reposition them, and you can resize them as needed.', 'wb-sticky-notes' ); ?></p>
			</div>
		</div>

		<!-- Question: 4 -->
		<div class="wb-stn-accordion-item">
			<button class="wb-stn-accordion-button">
				<?php esc_html_e( 'Can I change the color and style of a note?', 'wb-sticky-notes' ); ?>
			</button>
			<div class="wb-stn-accordion-content">
				<p><?php esc_html_e( 'Yes. Each note has its own customization menu in the top-left corner, where you can change the theme and font.', 'wb-sticky-notes' ); ?></p>
			</div>
		</div>

		<!-- Question: 5 -->
		<div class="wb-stn-accordion-item">
			<button class="wb-stn-accordion-button">
				<?php esc_html_e( 'Can I disable sticky notes on specific admin screens?', 'wb-sticky-notes' ); ?>
			</button>
			<div class="wb-stn-accordion-content">
				<p><?php esc_html_e( 'Yes. From the plugin settings, you can select the admin screens where you want to hide notes. If a screen is not listed, use the wb_stn_hide_on_these_pages filter to hide it programmatically.', 'wb-sticky-notes' ); ?></p>
			</div>
		</div>

	</div>
</div>

</div>

<!-- Accordion Styles -->
<style>
	.wb-stn-accordion-button {
		background: #fff;
		border: none;
		width: 100%;
		text-align: left;
		padding: 10px;
		font-size:14px;
		cursor: pointer;
		outline: none;
		transition: background 0.3s;
		position: relative; font-weight:600; color:#2c3338;
	}

	/* Arrow icon */
	.wb-stn-accordion-button::after {
		content: "\25BC"; /* Downward arrow */
		font-size: 14px;
		display: inline-block;
		transition: transform 0.01s ease;
		transform-origin: center;
		position: absolute; right:20px;
	}

	/* Rotated arrow when active */
	.wb-stn-accordion-item.active .wb-stn-accordion-button::after {
		transform: rotate(180deg);
	}

	.wb-stn-accordion-button:hover {
		background: #e0e0e0;
	}

	.wb-stn-accordion-content {
		display: none;
		padding: 10px;
		border-top: none;
		background: #fff;
	}

	.wb-stn-accordion-item {
		border:1px solid #e2e4e7;
	}
	.wb-stn-accordion-item:not(:last-child){ border-bottom:0px solid transparent; }

	.wb-stn-accordion-item.active .wb-stn-accordion-content {
		display: block;
	}
</style>

<!-- Accordion Script -->
<script>
document.addEventListener("DOMContentLoaded", function () {
	var accButtons = document.querySelectorAll(".wb-stn-accordion-button");

	accButtons.forEach(function (btn) {
		btn.addEventListener("click", function () {
			var parent = this.parentElement;
			var content = parent.querySelector(".wb-stn-accordion-content");

			// Close all other accordions
			document.querySelectorAll(".wb-stn-accordion-item").forEach(function (item) {
				if (item !== parent) {
					item.classList.remove("active");
					item.querySelector(".wb-stn-accordion-content").style.display = "none";
				}
			});

			// Toggle the clicked accordion
			parent.classList.toggle("active");
			content.style.display = parent.classList.contains("active") ? "block" : "none";
		});
	});
});
</script>