/**
 * File: orders.js
 *
 * Handles:
 *   - “Use Address on File” (shipping)
 *   - Initial recalculation of totals
 *
 * Depends on:
 *   - jQuery
 *   - recalculateEverything()
 */

jQuery(function($){


  // ──────────────────────────────────────────────────
  // On page load, if there is a product table, trigger recalc
  // ──────────────────────────────────────────────────
  if ( $('#aaa-product-table').length > 0 ) {
    // Delay just a bit so that all other handlers have bound
    setTimeout(function(){
      if ( typeof recalculateEverything === 'function' ) {
        recalculateEverything();
      }
    }, 150);
  }
});
