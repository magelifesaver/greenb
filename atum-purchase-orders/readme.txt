=== Purchase Orders PRO add-on for ATUM ===

Contributors: stockmanagementlabs, salvamb, japiera, agimeno82, dorquium
Tags: purchase orders pro
Requires at least: 5.9
Tested up to: 6.8.2
Requires PHP: 7.4
WC requires at least: 5.0
WC tested up to: 10.2.2
Stable tag: 1.2.8
License: ©2025 Stock Management Labs™

== Description ==

Take advanced control over purchasing and never worry about the difficulties that come with ordering stock, forgetting on items, missing the correct stock levels or simply losing overview of your business.

== Changelog ==

---

`1.2.8`

*2025-10-14*

**Features**

* Added barcode tracking support for Barcodes PRO.

**Changes**

* Adjusted logo sizes on PDF templates.
* Load the full logo images on the email templates.

**Fixes**

* Fixed typo.
* Fixed blurry logos on email previews.
* Fixed deprecated $.trim usage.
* Fixed PHP 8.4 compatibility.
* Fixed MI batch tracking in the POs list.
* Fixed caching issue when preparing POs list query.

---

`1.2.7`

*2025-08-28*

**Features**

* Allow some HTML tags on titles when exporting POs to PDF.

**Changes**

* Remove ATUM twigg templates for language dropdowns.
* Updated JS dependencies.

---

`1.2.6`

*2025-06-18*

**Features**

* Add PO note when the stock is changed from a delivery.
* Add order notes every time an inventory is added to stock.

**Changes**

* Refactoring.

**Fixes**

* Avoid "_load_textdomain_just_in_time" warnings.
* CSS fix.

---

`1.2.5`

*2025-03-12*

**Features**

* Added fitter to allow bypass supplier check.
* Add the customer shipping address to POs when converting from a WC order.
* Added new setting to choose whether to set the customer address to POs when setting the WC Order.

**Changes**

* Postpone the "load_plugin_textdomain" call until init.
* Updated JS dependencies.
* Refactoring.
* Try to prevent pcre.backtrack_limit errors.

**Fixes**

* Fixed "reply-to" header when sendint out emails to suppliers.
* Fixed capabilities.

---

`1.2.4`

*2025-01-27*

**Features**

* Standardize a common uploads folder for all ATUM plugins.
* Add the display additional fields options field to the Ajax "add_fee" function.
* Return the PO PRO extended metadata on API GET requests.
* Added hook after the item meta data modal apply clicked.
* Add PO Items to the global window.atum variable.
* Add products searching to default search in the PO Pro list table.

**Changes**

* Updated minimum PHP version to 7.4.
* Refactoring.

**Fixes**

* Fixed ATUM Orders post types "_load_textdomain_just_in_time" notice.
* Fixed typo.
* Fixed SQL query when searching.
* Fixed SQL query for metas when searching.

---

`1.2.3.2`

*2024-11-12*

* Fixed modals library conflict.

---

`1.2.3.1`

*2024-11-07*

**Changes**

* Replaced modals library.

---

`1.2.3`

*2024-10-23*

**Features**

* Manage PO Extended attributes in REST API.
* Allow filtering the emaill cc and bcc addresses.

**Changes**

* Updated dev dependencies.
* Refactoring.
* Disabled platform check on composer.

**Fixes**

* Fixed missing empty PO properties in API response.
* Fixed deprecated warnings from SASS compiler.

---

`1.2.2`

*2024-09-05*

**Features**

* Added PurchaseOrder API Extender to allow PO PRO statuses in REST API.
* Added eslint config.
* Added filtering by "ID" and "_number" to the default list table search.

**Changes**

* Updated JS dependencies.
* Do not allow to add the same product twice to a PO.

**Fixes**

* Prevent using date created field as if the value were in UTC.

---

`1.2.1`

*2024-05-28*

**Features**

* Allow decimals on the PO tax rate field.
* Allow decimals on the PO discount field.
* Bypass some items when adding sales order items to a PO (according to settings).
* Include disabled variations in PO search.

**Changes**

* Ask the user whether to also clone deliveries and invoices when cloning a PO.
* Renamed "atum_partially_receiving" post status.
* Refactoring.
* Disable the "Add Items" button in the "Add to PO" modal until a checkbox is checked.
* Do not restrict the product by supplier if the restriction is disabled from settings.
* Always return a number for the inventory stock when using the "Add to PO" filters.
* Do not show up products with no supplier assigned on the "Add to PO" modal if the option to show them is disabled.
* Prevent adding supplier's data to POs (free version).
* Restrict the supplier's default discount and tax rate up to 2 decimals.
* Restrict the supplier's discount and tax rate up to 2 decimals when changing them through the PO UI.

**Fixes**

* Fixed "Add to PO" in the Order list screen not working when HPOS was active.
* Fixed import comments meta key not being saved correctly.
* Fixed validation message positioning on PO modals.
* Fixed PHP 8.2 compatibility.
* Do not show orphan variations in the "Add to PO" modal.
* Avoid adding inventories to the "Add to PO" modal when Mi is disabled for their products.
* Fixed send PO to trash from list table.

---

`1.2.0`

*2024-04-03*

**Features**

* Added new "requires plugins" clause.

**Fixes**

* Fixed errors in deliveries and invoices when PO item has a product that doesn't exist.
* Fixed PO functionalities when a product doesn't exist.
* Fixed jQuery deprecations.

---

`1.1.9`

*2024-03-01*

**Changes**

* Ensure the product exists before checking if it has MI.
* Refactoring.
* Use the new AtumStockDecimals class.

**Fixes**

* Fixed: It could take prev iteration product and item when adding a new order item.
* Fixed wrong ?? operator behaviour.
* Fixed wrong PO id.

---

`1.1.8`

*2024-01-29*

**Features**

* Added create PO from Orders to HPOS list table.
* Include external meta when merging orders.

**Changes**

* Updated sweetAlert2 + modal styling improvements.
* Updated dependencies.
* Set min attribute in qty inputs to avoid division by 0 exception.
* Refactoring.
* Set bulk actions as executed.
* Removed global low stock clause from query if it is not set.
* Updated jQuery types.

**Fixes**

* Fixed function not found error.
* Fixed possible error when reading a meta field.
* Fixed missing "Sent" PO status in bulk actions.

---

`1.1.7`

*2023-11-27*

**Features**

* Allow having 2 ATUM list tables on the same page.

**Changes**

* Refactoring.
* Change returning/returned column title when appropriate.
* Update returned ratio cell when changing quantity value in returning POs.

**Fixes**

* Fixed inventory quantity input class.
* Fixed wrong items quantities when creating a new returning PO.
* Fixed inventory quantity for returning PO.
* Fixed wrong ordered delivery items count.
* Fixed stock issues when updating status in Returning POs and cancelling POs with refunded items.

---

`1.1.6`

*2023-11-02*

**Features**

* Added PL hook to be able to alter the stock quantity before editing it.
* Added filter to the BOM tree template.
* Added filter to the BOM MI tree template.
* Upgraded to webpack 5.
* Modernized gulpfile.

**Changes**

* Refactoring.
* Required node 18.
* Adjusted gulpfile config to webpack 5.

**Fixes**

* Decrease stock for variation products in returning POs.
* Make sure the calculations with possible decimal numbers are done right.
* Fix popover left arrow styles.
* Fixed supplier's code not being saved.

---

`1.1.5`

*2023-09-06*

**Features**

* Show the "add supplier items" feature also when products with no supplier are present.
* Update PO lang when linking a supplier.

**Fixes**

* Fixed counter in new PO number.
* Fixed wrong purchase price in "set purchase price" modal.
* Fixed typo.

---

`1.1.4.1`

*2023-08-24*

**Features**

* WPML: Update PO lang when updating the suplier.

**Fixes**

* Fixed PO sequential counters are skipping number.
* Fixed inventory is is shown in the info modal instead of the qty when setting the purchase price.

---

`1.1.4`

*2023-08-01*

**Features**

* Added new hooks to PO PDF templates.

**Changes**

* Moved WPML dropdown + disable it when the PO is not editable.
* Refactoring.

**Fixes**

* Avoid type errors on array_sum function.
* Fixed issue when using the get_atum_order_model helper on some sites.
* Fixed returnig POs + MI compatibility.

---

`1.1.3`

*2023-06-29*

**Features**

* Performance improvement: use cache if a PO was already loaded previously.

**Changes**

* Delete the PO PRO addon status transient when upgrading.
* Refactoring.

**Fixes**

* Fixed custom PO numbers not being added when creating POs from bulk actions.

---

`1.1.2`

*2023-06-26*

**Features**

* Added icons to ATUM Icon Font.
* Added new hook to the PO top bar.
* Allow multiple help guide markers on the same page.
* Added action after PO Purcharser's info.
* Added new WPML integration class.
* Added language dropdown to POs (full WPML compatibility).
* Only search products in the current PO language.
* Show products PO's language translations if possible.
* Added SQL clauses filters to the add_supplier_items function.
* Add SQL clauses filters to the MI integration for the "add suppliers to PO".
* Added Returning POs feature.
* Added a list of returning POs to the original PO.
* Allow creating returning POs in bulk.
* Added conditional clauses to the PO list row actions.
* Added conditional logic to PDF templates for Returning POs.
* Added the returned qty column to returning POs.

**Changes**

* Uninstall hook refactoring.
* Refactoring.
* Delay the addons integrations until all of them have been loaded.
* Adapted help guides to the new version.
* Improved sticky header bar animation.
* Display invoice/delivery date as local time.
* Include PO item using ATUM load_view function.
* Discount the right number of items when a PO is marked as returned.
* Do not show the clone button on returning POs.
* Focus the first modal input when opening them.
* Calculate totals after creating a returning PO.
* Restrict the returning PO creation when all the items have been already delivered.
* Added lang to SQL sentences and PO in AddToPO class.

**Fixes**

* Fixed discounts when adding multi-price inventories to the PO.
* CSS fixes.
* Fixed PO files links when are not images.
* Fix wrongly-constructed SQL query.
* Fixed returning PO creation from the row actions menu.
* Fixed closed state for the PO email modal's auto help guide.
* Fixed pending delivery items counter badge color.
* Avoid PHP notice when an array is empty.
* Fixed minimum version notice message.
* Enqueue the WP editor scripts when have not been enqueued elsewhere.
* Avoid errors if for some reason the TinyMCE script has not been loaded.
* Fixed returned and delivered items functions for MI.
* Fixed the stock available on PO item products with MI enabled.

---

`1.1.1`

*2023-03-31*

**Fixes**

* Fixed html content in PDF terms and description.
* Fixed wrong number of parameters on a function call.
* CSS fix.

---

`1.1.0`

*2023-03-29*

**Features**

* Added hook to be able to prevent uninstalling tasks.

**Changes**

* Added shipping info from settings to PO PDF export.
* Display WC address info in PDF if Atum addresses are empty.
* Apply description setting to display or hide the notes section in the PDF templates.

**Fixes**

* Fixed PO search without PO number metadata.
* Display PO id if PO number doesn't exist (PO Free case).

---

`1.0.9`

*2023-03-16*

**Features**

* Added deactivation tasks.

**Changes**

* Router refactoring.
* Delivery & Invoice PO attribute refactoring.
* Moved HPOS compatibility checker.
* Force displaying country in PO ship-to info.
* Prevent errors when displaying removed products.
* Added support for ATUM trials.
* Refactoring.

**Fixes**

* Fixed suplier triggers error if it hasn't a name.
* Fixed is_completed variable not set when displaying the BOM tree.
* Fixed wrap large names in PDF templates.
* Fixed hook name.
* Fixed error when displaying a removed product.
* Fixed wrong number of parameters.

---

`1.0.8`

*2022-12-20*

**Features**

* Exclude adding to PO and IL variation products if they are disabled.

**Changes**

* Removed unused class prop.
* Refactory ListTable array in scripts.
* Updated composer files.
* Updated minimum WC version.
* Refactoring.

**Fixes**

* Fixed method calling namespace.
* Fixed "add to invoice" button status when a checkbox is checked.

---

`1.0.7`

*2022-11-09*

**Features**

* Add full compatibility with the new WooCommerce's HPOS tables.

**Changes**

* Updated JS dependencies and require node 16.
* Clean assets dir before compiling.
* Refactoring.

---

`1.0.6`

*2022-09-07*

**Features**

* Added backorders column to Add-to-PO modal.
* Allow updating the purchase price for all the inventories at once from the ATUM tool.

**Changes**

* Refactoring.
* Updated minimum version requirements.
* When getting the low stock supplier products, also check the WC low stock threshold value.

**Fixes**

* Fixed inventory item quantity input value for negative stock.
* Fixed unstriped slashes in textarea fields and delivery terms in description fields.
* Fixed array_key_exists() error.
* Fixed add products with low stock having in account the global WC low stock setting.
* Fixed missing main inventories when added lowstock/outofstock/restock products from a supplier to PO items.

---

`1.0.5`

*2022-06-28*

**Features**

* Added the ATUM label to all the location term fields.
* Added thumbnails to the PO PDF.
* Allow enabling/disabling the ability to add products with no supplier assigned to POs with supplier assigned.
* Calculate gross profit value applying PO Pro settings for purchase price.
* Added supplier items to PO by out-of-stock/low-stock/restock-status.
* Added parameters to the after removing item hook.
* Allow settings default values to description and terms when settings/changing the PO supplier.

**Changes**

* Do not show expired inventories when displaying inventories to add.
* Cast values before using them for calculations.
* Attach display extra fields setting values in item template.
* Add delivery calcs to inventories inboud stock.
* Removed the old 'add all the out of stock items' button.
* Recalculate inbound stock when adding/removing delivery items.
* Updated messages and tooltips in add supplier items component.
* Activate tooltips in the add-supplier-items panel.
* Hide the suppliers panel in PO items modals when there aren't suppliers.
* Get rid of manage stock warning icons for MI-enabled product items.
* Refactoring.

**Fixes**

* Fixed PO free repeated product items not being included when calculating the inbound stock.
* Fixed columns number in fee/shipping lines.
* Fixed thumbnail column width in PDF.
* Fixed icons in PO PDF.
* Fixed PO columns not being displayed/hidden correctly when the PO sttings aren't saved yet.
* Fixed error when writing a Delivery Location name with spaces.
* Fixed incorrect inventory inbound stock when more than one delivery set.
* Fixed wrong calc inbound stock when creating deliveries.
* Fixed gross profit values for products with MI + multi-price.
* Prevent orphan variations when adding items to a PO.
* Fixed quantity value for new allowing backorders items in Add-To-PO modal.
* Missing inventories when adding oost/low-stock/restock items to PO.
* Prevent adding orphan inventories to PO items modals.
* Minor CSS fixes.

---

`1.0.4`

*2022-05-09*

**Features**

* Added new tool to Add/Deduct taxes to/from purchase prices to all the products at once.
* Added new option to hide/display PO items columns.
* Added the totals row to POs list.
* Format the POs list totals as price.
* Added a date range filter to the POs list table.
* New "Added to stock" column in POs list table.
* Added preview functionality to the POs List Table.

**Changes**

* Show taxes per item in the PO PDFs.
* CSS adjustments.
* Check if items were added to stock in status change script.
* Restore added to stock items when the PO is returned.
* Refactoring.

**Fixes**

* Fixed wrong text domain.
* Fixed numeric supplier ID in scripts.
* Fixed removed variables.
* Fixed script that checks whether delivery items and inventories were added to stock.
* Fixed unavailable classes when removing the addon.

---

`1.0.3`

*2022-04-22*

**Features**

* Added thumbnails to the Add to PO modal items.
* Auto-open the PO email modal guide when opened for the first time.
* Added a new field to PO's screen options to be able to switch the status flow restriction at any time.
* Save the PO's status flow restriction when changed and use this value when saved.
* When the supplier is changed on a PO, and there are items belonging to a disctinct supplier, ask whether to get rid of them.
* Save the closed state for the advanced options panel on each PO.
* Allow searching products by using multiple search terms at once.
* Optimize calculate_delivery_items_qtys cache's name.
* Store exchange rate in POs + Allow changing rate at any time.
* Improve loading time when adding inventories to delivery items.
* Show/Hide the exchange rate field depending on the actual PO currency set.
* Improve loading time when readin MI Panel atts.
* Add tooltips to exchange rate fields' currencies.
* Added new filter to be able to edit the last week sales args.
* Auto-select qty inputs' content when focusing them.
* Added PO PRO uninstall method.
* Remove order items from deliveries and invoices when uninstalling and the "delete data" option is enabled".
* Added the "address 2" field to PO's purchaser info details.
* Added the "address 2" to supplier address in PDFs.

**Changes**

* CSS adjustments.
* Show the right messages when removing a PO item if there are related items that should be also removed.
* Cast values befor calculating item discounts.
* Refactoring.
* Chain the supplier data filling and validate supplier items modals.
* Get delivery inventory items qtys' refactoring.
* Cache delivery's calculate_delivery_items_qtys results.
* Added the decimals number data attribute to all the edit popover buttons + preset the decimals to 0 by default.
* Strip trailing zeros from qty values.
* Do confirm that the next PO number to be used is unique before assigning it to any PO.
* Modified inbound list table with deliveried qtys.
* Modified product's inbound stock query to include deliveries.
* Only show the allowed statuses according to the flow on the menu actions from POs list.
* Changed inbound product query.

**Fixes**

* Fixed PO numbers on the Inbound Stock list.
* Fixed scroll-to-top issue when opening edit popovers.
* Fixed inventories being assigned to POs automatically when the option was previously enabled.
* Fixed status dropdown's save button not working just after switching the status flow restriction.
* Removed console logs.
* Fixed JSON search products.
* Fixed currency not replaced correctly after changing it more than once.
* Fixed edit popovers not spreading values after changing.
* Fixed slashed apostrophes on emails.
* Fixed item qtys not being considered when adding items if system taxes were disabled from settings.
* Fixed ordering in POs List Table.
* Fixed next PO number indicator on Settings when there is no pattern set yet.
* Fixed wrong datatables name.
* Fixed wrong meta tables used to calculate inbound stock.
* Fixed thumbnail not displayed when adding new items through the AddToPO search box.
* Fixed items being added through the AddToPo modal not added to the created PO(s).
* Fixed change detection modal not displaying when clearing the dicount field.
* Fixed totals not being recalculated after creating a new inventory and adding it to the PO.

---

`1.0.2`

*2022-03-16*

**Features**

* Added from name to PO email.

**Changes**

* Disable the Add Invoice button when there are no more items to invoice.
* Refactoring.

**Fixes**

* Fixed inbound stock list table results, showing items already received.
* Do not show the help tip for the Add Delivey button when the status isn't due.
* Fixed PO status flow on the dropdown after changing the PO status programmatically.
* Fixed taxes editing on MI items with multi-price.
* Fixed add-on name.

---

`1.0.1`

*2022-03-11*

**Features**

* Added filter to allow removing email address.
* Added a supplier products restriction option to settings. So this feature can be enabled/disabled.
* Added autogenerate PO number button.
* Allow editing PO numbers manually or auto-generating them.
* Added new option to PO settings to be able to set whether the PP are with taxes inclusive or exclusive.
* Added a currency converter to the Add Item modal when the PO currency is distinct from thr site currency.
* Added buttons to POs list table to be able to print and clone POs directly.
* Added button to be able to add all items to one delivery or invoice at once.

**Changes**

* If there are still no items on the PO and the currency is changed, change at least the totals boxes.
* Auto-save the PO after seleting any PDF template and printing it.
* Refactoring.
* Make sure a product still exists before checking if it has MI enabled.
* Added the min attribute to cost fields on the AddItemModal.
* Restrict the allowed numbers on AddItemModal inputs.
* Don’t show the PO comments actions row if there are less than 2 notes.
* Show a message when the last notification is marked as read.
* Add the supplier's VAT number after their address.

**Fixes**

* Use the store details as company details on the PO PDFs.
* Fixed status change failing from bulk actions when the status flow was disabled.
* Bring the selected PDF template to front when clicking over it.
* Fixed PO number previewer not being added after saving the PO settings.
* Fixed escape function.
* Fixed the entries per page custom values not being saved on POs list.
* Fixed swal JS error in SC when using the AddToPO functionality.
* Fixed text color when searching for MI products that were already added to the PO.

---

`1.0.0`

*2022-02-23*

**Features**

* The first public release of ATUM Purchase Orders PRO add-on. Check the add-on page for more info: https://stockmanagementlabs.com/addons/atum-purchase-orders-pro/