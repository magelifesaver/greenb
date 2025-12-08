=== Action Logs add-on for ATUM ===

Contributors: stockmanagementlabs, salvamb, japiera, agimeno82, dorquium
Tags: atum action logs, action logs, logs
Requires at least: 5.9
Tested up to: 6.8.2
Requires PHP: 7.4
WC requires at least: 5.0
WC tested up to: 10.1.1
Stable tag: 1.4.8.1
License: ©2025 Stock Management Labs™

== Description ==

Keeping track of any changes happening in your shop has never been easier. The Action Logs add-on supports all ATUM premium add-ons and all WooCommerce actions.

== Changelog ==

---

`1.4.8.1`

*2025-08-27*

**Changes**

* Updated JS dependencies.

---

`1.4.8`

*2025-06-18*

**Features**

* Added SKU search to logs.

**Fixes**

* Fixed error when logging the export from default templates.
* Avoid "_load_textdomain_just_in_time" warnings.
* Avoid log order created twice.

---

`1.4.7`

*2025-03-12*

**Changes**

* Postpone the "load_plugin_textdomain" call until init.
* Updated JS dependencies.

**Fixes**

* Fixed errors when logging linked BOM costs.
* Fixed extra cost name param.

---

`1.4.6`

*2025-01-27*

**Changes**

* Removed unneeded SCSS variable override.
* Updated minimum PHP version to 7.4.

**Fixes**

* Prevent getting null rows.

---

`1.4.5.2`

*2024-11-13*

**Fixes**

* Remove wrong text domain.
* Fixed warnings when building logs cache key.

---

`1.4.5.1`

*2024-11-12*

**Fixes**

* Fixed modals library conflict.

---

`1.4.5`

*2024-11-07*

**Changes**

* Updated eslint config.
* Updated dev dependencies.
* Disabled platform check on composer.
* Replaced modals library.

**Fixes**

* Fixed deprecation warnings from Sass compiler.

---

`1.4.4`

*2024-09-05*

**Features**

* Added eslint config.

**Changes**

* Updated JS dependencies.

**Fixes**

* Fixed query error when searching by username.

---

`1.4.3`

*2024-05-28*

**Features**

* Log WC orders when processed through the Store API.

**Changes**

* Refactoring.
* Check if variable is an array before processing it.

**Fixes**

* Fixed check order before saving.
* Fixed display status slug if not found in PO statuses.
* Fixed logs when edit multiple inventories at same time from Stock Central.

---

`1.4.2`

*2024-04-03*

**Features**

* Added new "requires plugins" clause.
* Improved ATUM CLI tools registration.

**Changes**

* Avoid errors when product does not exist in POs logs.

**Fixes**

* Fixed error when product item doesn't exist.
* Fixed jQuery deprecations.
* Fixed warning when logging new coupon creation.

---

`1.4.1`

*2024-03-01*

**Features**

* Added logs for Produce Items.

**Changes**

* Refactoring.
* Text change.
* Display formatted product name in logs.
* Check for previous stock levels before adjusting order items values.

---

`1.4.0`

*2024-01-29*

**Features**

* Added pagination args for logs results.

**Changes**

* Updated sweetAlert2 + modal styling improvements.
* Updated dependencies.
* Refactoring.
* Updated jQuery types.

**Fixes**

* Fixed logs remover tool.

---

`1.3.9.1`

*2023-11-27*

**Features**

* Allow having 2 ATUM list tables on the same page.

**Changes**

* Refactoring.

**Fixes**

* Prevent getting the PO status from WP_Product object.

---

`1.3.8`

*2023-11-02*

**Features**

* Added low_stock_threshold_by_inventory property log.
* Upgraded to webpack 5.
* Modernized gulpfile.

**Changes**

* Required node 18.
* Adjusted gulpfile config for webpack 5.
* Refactoring.
* Adjusted Action Logs menu item order.

---

`1.3.7`

*2023-08-01*

**Features**

* Added the upgrade class to record the current Action Logs version on the db.

---

`1.3.6`

*2023-06-26*

**Features**

* Added log for Stock Takes settings.
* Added icons to ATUM Icon Font.
* Added old & new stock to delivery items log.
* Added log for Pick & Pack status change.

**Changes**

* Uninstall hook refactoring.
* Refactoring.
* Delay the addons integrations until all of them have been loaded.
* Text changes.
* Adapted the logs list row actions to the new version.
* Adjusted logs for returning POs.

**Fixes**

* Fixed PO settings log slug.
* Prevent error when a non-variable product fires the save variation method.
* Fixed log for product stock levels when updating an order status manually.
* Avoid logging duplicated or fake new items in orders.

---

`1.3.5`

*2023-03-29*

**Features**

* Added Stock Takes and Pick & Pack integrations.
* Log when creating new Stock Take.
* Logs when trashing/untrashing/deleting Stock Takes.
* Logs when reconciling Stock Takes.
* Log when creating new Picking List.
* Logs when trashing/untrashing/deleting Picking List.
* Logs when exporting Picking List to PDF.
* Log when packing items.
* Log when completing order from picking list.
* Log when completing picking list.
* Added hook to be able to prevent uninstalling tasks.
* Logs for stock levels changes after reconcile Stock Take.

**Fixes**

* Fixed support variations in log for pack items.

---

`1.3.4`

*2023-03-16*

**Features**

* Added deactivation tasks.
* Added support for ATUM trials.

**Changes**

* Make sure a product exists before saving it.
* Change save variations hook priority.
* PO PRO's Delivery & Invoice attribute refactoring.
* Moved HPOS compatibility checker.
* Refactoring.

**Fixes**

* Fixed log for variations stock changes.
* Fixed check previous stock levels for products in an order.
* Fixed hook name.

---

`1.3.3`

*2022-12-20*

**Changes**

* Refactory ListTable array in scripts.
* Updated composer files.
* Updated minimum WC version.

---

`1.3.2`

*2022-11-09*

**Features**

* Add full compatibility with the new WooCommerce's HPOS tables.

**Changes**

* Updated JS dependencies and require node 16.
* Changed menu order.
* Refactoring.
* Clean assets dir before compiling.

**Fixes**

* Avoid generating multiple logs when adding a new order item in a new order.

---

`1.3.1`

*2022-09-07*

**Features**

* Avoid to log assignment to product for unregistered attributes without values.

**Changes**

* Refactoring.
* Updated minimum version requirements.

**Fixes**

* Fixed function name.
* Fixed warning when creating new product attributes.
* Fixed warnings when adding attributes without values to products.

---

`1.3.0`

*2022-06-28*

**Features**

* Added log when changing MI stock levels.

**Changes**

* Refactoring.

**Fixes**

* Fixed log for MI stock levels.
* Removed wrong class from source dropdown.

---

`1.2.9`

*2022-05-09*

**Features**

* Allow filtering the logs' list by the "System" user.

**Changes**

* Changed low stock name to restock status everywhere (for more clarity).
* Refactoring.
* Changed filter name.

**Fixes**

* Fixed no results in logs when sorting by date/user.

---

`1.2.8`

*2022-04-04*

**Changes**

* Changed outdated links to documentation.

**Fixes**

* Avoid warnings creating inventory with single location.

---

`1.2.7`

*2022-03-11*

**Features**

* Register additional data in PO PRO email log.

**Changes**

* Updated minimum versions.
* Refactoring.

**Fixes**

* Fixed the entries per page custom values not being saved on Logs List.
* Fixed action menu popovers.
* Fixed open log data script.

---

`1.2.6`

*2022-01-26*

**Features**

* Get default values for PO PRO fields at integration class.
* Collect fields when logging PO creation.
* Added log for PO approval.
* Added logs for inventory creation from modal, and order item inventory added to PO.
* Retrieve source request when logging inventory creation.

**Changes**

* Changed hook for logging AtumOrders status changes.
* Avoid logging status changed from autodraft.
* Always remove addon transient when uninstalling.
* Delete add-ons list transient when uninstalling.

**Fixes**

* Type fix.
* Fixed multiple logs for PO status change.
* Fixed log for ATUM orders status changes.

---

`1.2.5`

*2021-12-07*

**Features**

* Added compatibility with disabled List Table columns.
* Log Atum Orders status changes.

**Fixes**

* Fixed fake update log in PO delivery_date.
* Fixed warning when saving product data metaboxes.

---

`1.2.4`

*2021-11-26*

**Features**

* Added logs when editing PO PRO invoice items.
* Added log when cloning a PO in PO PRO.

**Changes**

* Ajax nonce names unification.
* Added missing option for new multi-checkbox field.
* Refactoring: renamed PO Premium to PO PRO.
* Provide jQuery with Webpack config to avoid conflicts with 3rd party plugins.
* Refactoring.
* Increased priority for the PO PRO meta box.
* Refactoring for the items per page in Logs List Table.

**Fixes**

* Fixed popovers CSS warning.
* Fixed undefined variable.
* Fixed edited PO PRO delivery log.
* Fixed deprecated user attribute.
* Fixed log when deleting PO PRO invoice items.

---

`1.2.3`

*2021-11-02*

**Features**

* Log inventory changes from Stock Central.
* Added log for merged POs.

**Changes**

* Refactoring.
* Avoid using custom text entries when saving entry in database.

**Fixes**

* Fixed wrong text domains.
* Fixed log changes for PO delivery items.
* Fixed error when editing delivery item inventory.
* Avoid error when logging delivery item inventories.

---

`1.2.2`

*2021-10-07*

**Features**

* Added log for PO PRO's invoice item removal.
* Hide trashed logs at addons request.
* Added more PO PRO's files entries.
* Log changes for every field in PO PRO.
* Open the unread tab when clicking the badge.
* Added support to log tool_sync_real_stock with parameters.

**Changes**

* Refactoring.
* Load the PO PRO Logs meta box entirely from Action Logs.

**Fixes**

* Fixed bulk actions button not visible.
* Unread badge CSS fix.
* Fixed wrong text domains.

---

`1.2.1`

*2021-09-17*

**Features**

* Added action to avoid display columns id in Log Registry.
* Log PO requisitioner and status changes (PO PRO compatibility).
* Log currency changes on POs (PO PRO compatibility).
* Added param entry type with separate words without quotes.
* Show values and remove fields quotes in PO internal logs (PO PRO compatibility).
* Added logs for PO deliveries created and removed (PO PRO compatibility).
* Added log for adding delivery items to stock (PO PRO compatibility).
* Added logs when editing/removing delivery items (PO PRO compatibility).
* Added log when adding files to POs (PO PRO compatibility).
* Added logs when creating/removing/editing PO invoices (PO PRO compatibility).
* Added support for Action Logs tools when using the new ATUM's WP-CLI commands.
* Log the BOM stock quantity when added to a new order.

**Changes**

* Log Registry extends from AtumListPage.
* Set per_page after calling parent construct.
* Use global constant instead of string.
* Refactoring.
* Regenerated the composer's autoload.

**Fixes**

* Fixed wrong doc params.
* Fixed wrong logs when no changes were made in a PO.
* Fixed unchanged dates logs.
* Fixed untracked emails log.
* Fixed wrong text domains.

---

`1.2.0`

*2021-08-17*

**Features**

* Added integration with Purchase Orders PRO.
* Added logs for New Suppliers fields.

**Changes**

* Updated ATUM icon font.

**Fixes**

* Fixed item inventories logged though MI is inactive.
* Fixed PHP warning when creating PO.
* Fixed PHP warning when saving an EP template.

---

`1.1.9`

*2021-07-16*

**Features**

* Added log for the clear ATUM transients tool.
* Collect inventory info on BOMs logs.

**Changes**

* Log BOM order items quantities separately at Orders, ILs and POs.
* Changed hook name.

**Fixes**

* Fixed duplicated lines when searching in Log Registry.
* Fixed check setting on first save (clean installation).

---

`1.1.8`

*2021-06-21*

**Features**

* Added MI product fields to action logs.
* Allow disabling the Action Logs API when the ATUM API module is off.
* Log for show_out_of_stock_inventories property changes on products.

**Changes**

* Added missing request object on preview data before saving product.
* Changed hook name.

**Fixes**

* Fixed warning when log removed wc order from inventory log.

---

`1.1.7`

*2021-05-28*

**Changes**

* Updated JS dependencies.
* Ensure the order item exist before log the remove action.
* Prevent log when adding a non product line order item to an order.

**Fixes**

* Fixed error when adding a wrong product to a WC order.
* Fixed error when logging product order's item updates at PO/IL and receiving a non-product item.

---

`1.1.6`

*2021-04-30*

**Features**

* Added full RTL languages compatibility.

**Changes**

* Updated dependencies + use WebPack 4.
* Changes to dark mode colours.

**Fixes**

* Fixed warning when receiving empty values.
* Ensure a post exists before saving an order.

---

`1.1.5`

*2021-03-12*

**Changes**

* Hook name change.

**Fixes**

* Prevent undefined index if no taxes were created.
* Ensure it’s being used the correct order table for inventory items.
* Fixed refund field name.
* Fixed order post_type.
* Fixed enable/disable variation products.
* Fixed methods by checking product.
* Fixed API exception when adding order item.

---

`1.1.4`

*2021-02-19*

**Changes**

* Refactoring.
* Updated supported WC version.
* Recompiled assets.

---

`1.1.3`

*2021-01-29*

**Features**

* Use the new Menu Actions component for Logs list actions.

**Changes**

* Upgraded popovers and tooltips to Bootstrap 5.

**Fixes**

* Fixed empty template data warning.

---

`1.1.2`

*2021-01-05*

**Changes**

* Unify how the existence of products is checked.
* Refactoring.

**Fixes**

* Fixed unvalid format date to use new relative date helper.
* Fixed all the jQuery deprecations until version 3.5.

---

`1.1.1`

*2020-12-16*

**Features**

* Do not load the ATUM Order items when not needed to improve performance.

**Changes**

* Use the new relative_date helper.
* Use the new AtumAdminNotices component when showing notices.

---

`1.1.0`

*2020-11-27*

**Changes**

* Updated SweetAlert2 dependency.
* Refactoring.

**Fixes**

* CSS fixes.
* Check valid order type.

---

`1.0.9`

*2020-11-13*

**Features**

* Log inventory changes from API request.
* Added ATUM order items' hook.
* Added methods for logging add/remove notes in ATUM orders.
* Log for added new item to ATUM order from API request.
* Logs for Suppliers from API requests.
* Tools execution log through API request.
* Log add line item from API request.
* Log updated order line items from API request.
* Log start import template in Export PRO.
* Log products and BOMs stock levels changes.

**Changes**

* Recursive BOM stock levels check.
* Set min node version to 14 and added jquery as webpack external.
* Added support for ES2017 to tsconfig.json.

**Fixes**

* Fixed undeclared variable notice.
* Prevent same logs from being fired at same time.
* Fixed duplicated logs.
* Fixed Export PRO entity log data saving.
* Fixed order items in PL's log method.

---

`1.0.8`

*2020-10-27*

**Features**

* Log stock levels' changes on adding product to manual order.
* Added logs for ATUM/WC API Requests.
* Log product data and WC orders from API requests.
* Log variations changes from API requests.
* Inventories' API logs.

**Changes**

* Show product name in entry description when adding product item to order.
* Show log dates in WP Timezone.
* Save log dates as GMT.
* Action icons always visible in logs list.
* Refactoring.

**Fixes**

* Prevent duplicate order creation log.
* Fixed missing attribute label at variable product.
* Prevent missing params on modified entries.
* Fixed relative dates' timezone.
* Fixed index name in logs remover tool.

---

`1.0.7`

*2020-10-08*

**Features**

* Create logs when saving a product through the "Quick Edit" feature.

**Changes**

* Prevent warning when product had no previous locations.

**Fixes**

* Fixed wrong variable name.
* Fixed popover arrow CSS.
* Fixed datepicker not showing translated weekdays according to the users' locale.

---

`1.0.6`

*2020-09-23*

**Changes**

* Logs datetimezone set at default server config.

**Fixes**

* Fixed issues with Multi-Inventory.
* Fixed Export PRO template name that can be absent.

---

`1.0.5`

*2020-08-27*

**Fixes**

* Fixed add-on name for API key activations.
* Fixed add-on internal name.

---

`1.0.4`

*2020-07-30*

**Fixes**

* Fixed readable entries on ATUM Export PRO's logs.

---

`1.0.3`

*2020-07-10*

**Changes**

* Updated JS dependencies.

**Fixes**

* Avoid PHP warning if setting title is not set.

---

`1.0.2`

*2020-06-19*

**Features**

* Log deleting meta from order items at WC Orders/POs/ILs.
* Added extra fields for product reviews log.

**Changes**

* Prevent WP_Error if template_id is not returned by Export PRO.
* Log variation create in two steps.
* Log stock levels only if the order status has changed.

**Fixes**

* Fixed "mi_write_off_inventory" hook params.
* Fixed wrong data when using BOMs in orders.
* Fixed old_stock & new_stock values on logging stock levels update.
* Logs remover tool if checkbox is checked.
* Fixed order totals calc log.

---

`1.0.1`

*2020-05-29*

**Features**

* Added names for every ID fields in log data.
* Log BOM changes within variation products.
* Create log when removing note from ATUM Orders.
* Log adding/removing product attribute values.
* Log changes for "menu_order" and "enable_reviews" in products.
* Log for template download.
* Check active ATUM modules before loading hooks.
* Log for the customer provided note in orders.
* Log for product reviews.
* Logs for removing meta from order items in Orders, POs & ILs.
* Log for adding single variation.
* Handle additional terms by typing '+' when searching for logs.

**Changes**

* Removed "atum/purchase_orders/can_reduce_order_stock" filter.
* Removed "atum/settings/defaults" filter.
* Normalizing hook names.
* Replaced "wp_ajax" hook to action hooks for SC set locations.
* Replaced "wc_ajax" hook to action hook in SC bulk actions.
* Replaced "wp_ajax" hook to action hook in SC/MC export_data.
* Joined ajax hooks into action hook to log ATUM Orders status changes.
* Rebuilt log for adding Atum Order Product Item.
* Replaced "wp_ajax" hook to action hook when note added in Atum Orders action hooks for logs when adding shipping cost/fees.
* Replaced "wp_ajax" hook to action hook to log when tax is added to an ATUM Order.
* Replaced "wp_ajax" hook to action hook when saving order items.
* Replaced "wp_ajax" hooks to action hooks for log when removing order items.
* Replaced "wp_ajax" hook to action hook for purchase price action log.
* Replaced "wp_ajax" hook to action hook when note added in ATUM Orders.
* Replaced "wp_ajax" hooks to action hooks on tools execution logs.
* Added "check_ajax_referer" to remaining "wp_ajax" hooks.
* Replaced "wp_ajax" hooks to action hooks for log remove inventory and write-off actions.
* Changed hooks for ATUM Export PRO's ajax calls.
* Refactoring.

**Fixes**

* Fixed log on product save checking if locations are empty.
* Prevent accessing non existing order items.
* Fixed empty row classes.
* Fixed hook name.
* Fixed "inventory_id" reference.
* Fixed "get_post_type" when calling WC Orders.
* Fixed order email notification logs.
* Fixed logs for template export.
* Fixed log for variations removal.
* Fixed run export log.

---

`1.0.0`

*2020-05-08*

**Features**

* The first public release of ATUM Action Logs add-on.