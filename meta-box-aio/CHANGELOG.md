### 3.3.5 - 2025-12-04

- Fix text limiter not working if a field has prepend value
- Fix `sprintf` not imported, causing "Something is wrong" error

### 3.3.4 - 2025-11-24

- Fix the `use` statement with non-compound name has no effect

### 3.3.3 - 2025-11-24

- Add filters for reordering post types and taxonomies, allowing built-in or other post types/taxonomies can be reordered. See [docs for usage](https://docs.metabox.io/reorder-posts-terms/).
- Change icon for required/cloneable badge to avoid UX confusion
- Change tooltip for field label to top to always show the ID
- Hide date's `save_format` & `timestamp` settings when in a group to avoid confustion as they're not effective when inside groups
- Fix cannot add rules for Advanced location rules
- Fix cursor jumping when editing tooltip content
- Fix typing issue with Vietnamese in the field label in the field settings panel
- Fix incorrect location when importing field groups for settings pages
- Fix parsing JSON notation
- Fix deprecation message for `datetime` field
- Fix cannot toggle integration extensions on the Extensions page.

## 3.3.2 - 2025-11-14

- Update pt_BR translation
- Fix required text field preventing saving
- Fix no selected settings page causes the field group editor error

## 3.3.1 - 2025-11-07

- Add prefix to field IDs when checking for duplication
- Add notice for relationship's field settings to let users know they are applied to the field on the other side of the relationship
- Show field group settings when the location is set for a settings page
- Make option to default hide a meta box works for settings pages
- Fix return type `static` not supported in PHP < 8.0
- WPML integration: fix error when filtering value for helper functions when no fields are found.

## 3.3.0 - 2025-11-05

**Highlights:**

- Allow models to add more features: `author`, `published_date`, `modified_date` via the `supports` parameter. [Learn more about it](https://docs.metabox.io/extensions/mb-custom-table/#usage). This version also simplifies the API for creating custom tables for models. You don't need to pass the last parameter for auto increament anymore. The plugin will auto handle that for you. This makes the code to create custom tables consistent for both normal posts/terms/users and models.
- Add width settings for admin columns, which allows you to define custom width for a column. You can set the width in the builder, or set via parameter `width`, like: `'width' => '10%'`.
- Add a new block for adding a view in the block editor
- Add auto login for register form. To enable it, set `'auto_login'="true"` to the register shortcode.
- Add toggle status column settings for quickly toggle Published/Draft status for custom post types.

**Other changes:**

- Fix bug with `meta_box_sanitize_cb` when importing custom post types from ACF
- WPML integration: filter helper functions to get the translated IDs for `post` field
- Fix cloning `post` field not clearing the value
- Remove IDs for custom HTML, divider, heading fields in the structure panel, and for custom HTML field in the preview area
- Fix parsing prefix for tabs
- Fix required URL & email fields preventing saving
- Fix cannot save field group again if saving it without title and ID
- Local JSON: fix custom table name not containing prefix
- Do not show default user forms & fields in the admin

## 3.2.0 - 2025-10-07

**Highlights:**

Add new feature for taxonomies: allow to reorder terms.

**Other changes:**

- Add pagination for user frontend dashboard
- Support `hide_from_front` option in user profile forms
- Improve compatibility with object cache
- Add missing reCAPTCHA verification in login form
- Add confirm password to validate password in reset password form
- Let users know when deleting a field, its data remains in the DB
- Revamp the API to register a new field type. Please follow [the docs](https://docs.metabox.io/creating-new-field-types/) for details.
- Add an option to disable dragging the pin on the map/osm fields
- Fix parsing/unparsing tabs for settings pages
- Fix empty block render code after reloading the editing page
- Fix cannot edit a field on Safari
- Set default menu position for settings page after all menus
- Fix menu icon (dashicons) not working for settings pages
- Remove "text_domain" from generated labels for post types
- Fix encoded unicode characters when importing post types
- Fix Admin Columns Pro integration not working when Meta Box free is deactivated

## 3.1.1 - 2025-09-17

Fix named parameter not supported in PHP < 8

## 3.1.0 - 2025-09-16

**Highlights:**

This version introduces 4 tools to cleanup and maintain your database by removing or changing custom fields and custom post types in the database.

Learn more about those tools in [our blog post](https://metabox.io/tools/).

Other changes:

- Ask before leaving without saving changes
- Add an icon to the field label to show if field has `save_value` settings is off
- Use icon for required & cloneable settings for better accessibility
- Add disabled, readonly attributes to date, time fields
- Open field settings panel when add a new field
- Improve behavior when deleting a field by auto seting the next or previous field active
- Allow to change type for custom HTML, divider, heading, hidden fields
- Fix error switching field type to group and tab
- Fix compatible with Gutenberg plugin
- Fix parsing std value for radio when it's 0
- Fix tabs not working with blocks.
- Remove notice for feedback for the new UI
- Don't show upgrade panels for AIO users

## 3.0.1 - 2025-08-21

- Allow to change field type
- Allow click to edit tab label
- Always show sub-fields for cloneable group with "Start with no inputs" enabled
- Set active field to new added field
- Prevent invalid characters when editing field ID
- Fix missing field prefix when auto creating custom tables
- Fix admin menu overlaying the app (caused by ASE)
- Fix cursor jump for ID, FileSize & block code
- Fix missing `object_id` when update custom models from the front end

## 3.0.0 - 2025-08-18

**Highlights:**

This version introduces a rewritten the builder with a new UI/UX. [Learn more](https://metabox.io/mb-builder-5-0-0-official-release/) about the new UI.

**Other changes:**

- Fix Open Street Maps field not showing (sometimes) with conditional logic
- Fix inserting custom map style for settings pages
- Fix hook `mbct_after_delete` is not firing for bulk delete action
- Update block controls for frontend submission & user profile's blocks

## 2.3.1 - 2025-07-18

- Fix error when defining block supports for anchor
- Fix compatibility of the conditional logic with the site editor (full-site editing)

## 2.3.0 - 2025-07-15

**Highlights:**

Rewrite the re-order feature, making works with hierarchical post types. Now you can drag and drop posts to re-order them and even setup parent/child relationships. To re-order posts, now you need to click the "Re-order" link in the header of the screen (near All | Published filters).

**Other changes:**

- Update config storage to reduce size of keys in the DB for frontend submission and user profile modules
- Improve performance for conditional logic in the block editor
- Fix validation for blocks
- Addmin filter for relationships: allow to remove selected choice
- Fix not respecting std value in child field when it is set to 0, false, ''
- Fix term meta not saving when using advanced location rules
- Add revision support for views
- Add German translations

## 2.2.3 - 2025-06-23

- Fix issue translating empty groups with WPML/Polylang
- Add German translation

## 2.2.2 - 2025-06-11

- Add `mbct_{$action}_bulk_action` action for handling bulk actions for custom models
- Fix error for translating relationship texts
- Add German translations for some extensions

## 2.2.1 - 2025-05-21

- Fix datetime field returns null
- Fix single image field not working with Polylang Pro
- Fix `fields_translations` settings still available and grows rapidly event when Polylang is not active
- Fix reveal password not working
- Fix WPML integration with some languages
- Fix error when adding additional CSS class to the Profile block
- Fix reCaptcha not working for user profile forms when password strength is disabled


## 2.2.0 - 2025-05-08

Highlights:

Add integration for Polylang & improve the integration with WPML: allow translating settings pages/relationships/fields' labels and also value. For more details, please see this [blog post](https://metabox.io/wpml-polylang-integrations-improvements/).

Other changes:

- Add button to toggle password (#1630)
- Add gesture handling support for OSM field (#1631)
- Relationship admin filter: add localization for select2 and fix select2 width (#91)
- Datetime & select2: use user's locale instead of site's locale
- Fix conditional logic performance issue with new Builder's Local JSON feature, and improve performance for the block editor.
- Fix cloneable group issue with special characters with Elementor

## 2.1.1 - 2025-04-18

- Fix tab not rendering when using builder.
- Fix block not showing in the Inserter in WordPress 6.8.

## 2.1.0 - 2025-04-01

- New feature: [local JSON](https://metabox.io/local-json/), which allows you to use JSON to define field groups, and eliminate querying database.
- Fix text limiter not working for 2 inputs

## 2.0.3 - 2025-03-14

Redesign the dashboard

## 2.0.2 - 2025-02-25

Fix not showing "Add new" button for cloneable fields.

## 2.0.1 - 2025-02-17

Fix language files.

## 2.0.0 - 2025-02-14

**Highlight:**

This version now bundles Meta Box. You don't need to install Meta Box separately anymore. You can safely remove Meta Box (keeping it won't harm and won't cause any conflicts). This will help you shorten the plugin list and make things simpler.

**Other changes:**

- Add an option `'tab_remember' => true` to set to remember the opening tab in the next page load.
- Fix text limiter outputting twice for wysiwyg
- Fix error when setting parent for custom tables
- Fix not rendering HTML tags in subfield WYSIWYG for Elementor
- Fix output syntax error in Theme Code

## 1.31.1 - 2024-12-16

- Improve search for posts by title for views
- Fix subfield value in a cloneable subgroup is not saved
- Fix backward compatibility with using `post_id`
- Improve performance for tabs

## 1.31.0 - 2024-11-20

**Highlights**:

This version makes for custom models and frontend submission compatible, allowing to submit data for custom models on the front end. For usage, please see [the docs](https://docs.metabox.io/extensions/mb-frontend-submission/).

Other changes:

- If the submitted post from the front end supports the block editor, when viewing/editting the post on the back end, the content is automatically converted to blocks.
- Improve accessibility of the switch control when creating post types
- Add JavaScript filter for group.title (see [our docs](https://docs.metabox.io/extensions/meta-box-group/#changing-group-title-with-javascript) for usage)
- Add group title to the remove confirmation message
- Fix empty labels when changing post type supports
- Fix PHP warning when outputing code for heading/divider fields inside groups in the builder.
- Fix WYSIWYG output raw value without HTML tags if applying text limit.

## 1.30.5 - 2024-11-07

- Reduce number of DB queries when auto creating custom tables
- Fix error exporting post types, taxonomies & field groups
- Fix blank plural and singular labels for post types & taxonomies
- Fix error when searching posts in the admin when set a relationship as a filter
- Fix deprecated notice: automatic conversion of false to array
- Fix cannot update posts when set text limit

## 1.30.4 - 2024-11-01

Fix "something wrong" message when editing fields

## 1.30.3 - 2024-11-01

- Improve code quality with fixes for Plugin Check & PHPCS
- Fix group creating a new group entry upon edit & save blocks.
- Add missing "description" in block keys
- Fix warning when a post type has no labels
- Fix export JSON warning
- Fix radio select not working correctly in groups
- Fix clone empty start applied to subgroup field

## 1.30.2 - 2024-09-07

- Add icon field type for Elementor
- Support getting term meta on the single post for Elementor
- Do not count HTML tags for text limiter
- Update CSS for blocks to match WordPress's selectors
- Fix error when block icon is FontAwesome and no icon is selected
- Fix group and conditional logic issues with `clone_empty_start`
- Fix default value "0" for `range`field type not working
- Add filter for list table class object, which allows developers to use their own list table object:

    `$list_table = apply_filters( 'mbct_list_table_object', $list_table, $args );`


## 1.30.1 - 2024-09-07

- Fix issue with `clone_empty_start`
- Fix edge-case error when adding “add fields” links

## 1.30.0 - 2024-08-20

This version adds support for not showing any inputs for cloneable groups. This feature now is a core feature of Meta Box and works well with all cloneable field types, including groups.

See more details in our [blog post](/clone-empty-start/).

Other changes:

- Show a warning when having duplicated IDs in the builder
- Do not auto generate slug for custom post types & custom taxonomies when it’s manually changed
- Fix menu position for custom post types not working if set after Bricks
- Fix limit of custom taxonomy slug
- Make \[mbv\] shortcode work with ajax
- Fix group names for the generated theme code in the builder

## 1.29.0 - 2024-07-25

This version allows you to make inner blocks use the same markup for both the admin and front end by adding `id`, `class`, or `style` attribute to `<InnerBlocks>` wrapper.

For more details, please see [the docs](https://docs.metabox.io/extensions/mb-blocks/#wrapper-div-issue).

Other changes:

- Fix image advanced and color fields not working for Elementor
- Fix: duplicate fields checkbox got checked in the builder

- Fix showing excerpt in post table lists of custom post types and custom taxonomies
- Fix PHP warning in WordPress 6.6

## 1.28.0 - 2024-07-17

Add an option for relationships, allow you to delete relationship's data (connections) when deleting a relationship in the builder.

Other changes:

- A link to create custom fields after a custom post type or a custom taxonomy was created.

## 1.27.1 - 2024-07-05

### MB Builder 4.8.1 - 2024-07-05

- Fix "Save field value" option is auto turned off when upgrading from an old version
- Fix block error when no fields

### MB Admin Columns 1.7.4 - 2024-07-05

- Fix sorting for custom models

### MB Custom Table 2.1.11 - 2024-07-05

- Default sort custom models by ID desc

## 1.27.0 - 2024-07-02

**Highlights:**

This version allows you to render blocks with views. You can use code or use MB Builder to set/edit views with UI (without leaving the page).

Also, you can sync data from block.json file back to the builder, so the data is always in sync between the UI and code.

For more details, please see this [blog post](https://metabox.io/sync-block-json-and-render-blocks-with-views/).

We also rewrote/improved the UI of the MB Custom Post Types & Custom Taxonomies plugin, powered by block editor components. The new UI has the same layout and interaction as editing posts with the block editor, which will brings you a modern, friendly interface and a great experience.

For more details, please see this [blog post](https://metabox.io/mb-custom-post-types-custom-taxonomies-update-ui-ux-improvements/).

Other changes:

### MB Blocks 1.6.0 - 2024-07-02

- Improve styling of blocks on sidebar
- Fix $ being escaped in the output

### MB Builder 4.8.0 - 2024-07-02

- Add settings for enabling revisions.
- Improve suggestions for key-value options.
- Make relationships object types available only when extensions are active.
- Remove ID for "custom\_html" field
- Fix wrong prefix for address field (when selecting for map/osm) when the field prefix is not set in the settings tab.

### MB Frontend Submission 4.4.4 - 2024-07-02

- Add filter "shortcode\_atts\_mb\_frontend\_form" to modify attributes of the frontend form shortcode

### MB Rest API 2.0.2 – 2024-07-02

- Fix not updating WooCommerce products

## 1.26.3 - 2024-05-16

### MB Blocks 1.5.1 - 2024-05-16

- Fix: rest response for inner blocks in headless mode (#12)
- Fix: add name attribute back
- Add param resolver, so you can use parameter `$attributes`, `$content`, `$block` in render callback/template in any order, or remove them if you don't need them.

### MB Relationships 1.12.2 – 2024-05-16

- Set default field label for clarity and to work with Oxygen builder.

### MB Revision 1.3.8 - 2024-05-16

- Fix: fatal error when using with custom table

### MB User Profile 2.5.6 - 2024-05-16

- Remove check password strength on login page

### MB Views 1.12.8 - 2024-05-16

- Fix map/osm fields not showing when there is a view for posts
- Fix PHP 8.2 deprecated notice

### MB Builder 4.7.3 - 2024-05-16

- Add description for relationships reciprocal settings (#85)
- Fix: use context "normal" instead of "content"
- Fix blocks json notice (#83)

### Meta Box - Facet Integrator 1.1.4 – 2024-05-16

- Fix error when using at the same time with Meta Box AIO.

## 1.26.2 - 2024-04-18

### MB Builder 4.7.2 - 2024-04-18

- Fix cannot click duplicate & delete icons

## 1.26.1 - 2024-04-17

### MB Builder 4.7.1 - 2024-04-17

- Fix: cannot click on main tabs

## 1.26.0 - 2024-04-17

### MB Blocks 1.5.0 - 2024-04-17

**Highlights:**

The plugin now supports creating blocks with block.json, which aligns with the WordPress's recommended method to create blocks. This allows the blocks created with MB Blocks will be compatible with future development of WordPress and the block editor.

With the support of block.json, the plugin now fully supports Full-Site Editing (FSE). So you can use the blocks in FSE's templates.

This update also makes rendering the block easier by automatically preparing the fields' values and put them directly in the `$attributes` parameter.

For details, please see [the documentation](https://docs.metabox.io/extensions/mb-blocks/).

### MB Builder 4.7.0 - 2024-04-17

**Highlights:**

This version supports creating `block.json` for blocks, which is supported in MB Blocks 1.5.0. This also prepares for a future updates to support more settings/edits for blocks.

**Other changes:**

- Add save format to time field
- Only show menu for admin role
- Support to show fields for specific terms (requires MB Include Exclude extension)
- Fix changing field type to the group crashes the UI
- Fix not selecting title & id in the header bar with the mouse

### MB Include Exclude 1.1.1 - 2024-04-13

- Support to show, hide fields on specific terms

### MB User Profile 2.5.5 - 2024-04-08

- Increase the click area of show password icon
- Fix date picker not localized
- Only show menu for admin role

### MB Frontend Submission 4.4.3 - 2024-04-08

- Add new filter `rwmb_frontend_dashboard_status`
- Fix date picker not localized

### MB Custom Post Types & Custom Taxonomies 2.6.4 – 2024-04-11

- Fix admin color scheme
- Fix strings not localized

### MB Relationships 1.12.1 – 2024-04-11

- Fix admin filter compatibility with Visual Composer

### MB Beaver Builder Integration 2.1.2 – 2024-04-02

- Fix image fields not working for author and user

## 1.25.0 - 2024-03-26

### MB Relationships 1.12.0 – 2024-03-25

- Add admin filter in the “edit” posts screen to filter posts by relationships. To enable this feature, please add `'admin_filter' => true` to a side of the relationship settings. Works only for posts.
- Fix `API::each_connected()` not working

### MB Builder 4.6.7 - 2024-03-26

- Add text limiter settings
- Improve style for toggles for relationships & settings page
- Show admin filter for relationships only when the object type is post

### MB Custom Table 2.1.10 - 2024-03-26

- Fix PHP error when adding a new custom model item
- Fix delete single custom model item

### MB Views 1.12.7 - 2024-03-26

- Fix rendering icon field

## 1.24.3 - 2024-03-21

### MB Custom Table 2.1.9 - 2024-03-20

- Improve bulk actions for custom models with ability to register custom action & callback. Support redirect after doing bulk action. See [the docs](https://docs.metabox.io/extensions/mb-custom-table/#bulk-actions-handling) for using.
- Add a `$force` param to `API::get()` method to force getting raw data from the database (true: default) or cache (false).
- Improve performance for bulk deleting models

### Meta Box Text Limier 1.2.0 – 2024-03-14

- Add support for WYSIWYG field
- Update the style

### MB Settings Page 2.1.12 - 2024-03-14

- Auto change field file/image in the Customizer to file\_advanced/image\_advanced

### MB Custom Post Types & Custom Taxonomies 2.6.3 – 2024-03-07

- Improve i18n issues
- Fix null warning notices

### MB Admin Columns 1.7.3 - 2024-03-14

- Fix not passing 'link' param

### MB Views 1.12.6 - 2024-03-14

- Fix not displaying the `text_list` field on the frontend

### MB Builder 4.6.6 - 2024-03-21

- Add option for relationship's `admin_filter`

### MB Include Exclude 1.0.13 - 2024-03-20

- Improve the fix for error when accessing `user-edit.php` without an user ID

## 1.24.2 - 2024-02-02

### MB Revision 1.3.7 - 2024-02-02

- Fix conflict with Elementor

### MB Builder 4.6.5 - 2024-02-02

- Fix not loading styles for relationships and settings pages.

## 1.24.1 - 2024-01-22

### MB Builder 4.6.4 - 2024-01-22

- Add field ID prefix for suggestions of admin column position & address field

### MB Conditional Logic 1.6.21 - 2024-01-22

- Fix not working with decimal number
- Fix: don't set required when the field is visible back

### MB Include Exclude 1.0.12 - 2023-12-04

- Fix error when accessing user-edit.php without an user ID

### MB Elementor Integration 2.1.11 – 2024-01-22

- Fix Uncaught TypeError: `array_column()`

### MB Group 1.3.19 - 2024-01-22

- Support outputing group values in admin columns

### MB Admin Columns 1.7.2 - 2024-01-22

- Add style for groups

### MB User Profile 2.5.4 - 2024-01-22

- Fix password strength not working properly when removing password2 field
- Fix unconfirmed user still can login with wp-login.php

### MB Revision 1.3.6 - 2024-01-23

- Fix revisions not working since WordPress changes its API

## 1.24.0 - 2023-11-28

### MB Builder 4.6.3 - 2023-11-28

Functionality:

- Don't use PHP's eval() in the Theme Code generator
- Validate and sanitize ID to avoid bad forms
- Add support for new [icon field type](https://docs.metabox.io/fields/icon/)
- Fix import field group does not take ID from JSON file
- Fix theme code generated for sub-groups

UI/UX:

- Improve how to select predefined/suggested values in conditional logic, group title, post/term/user query options, etc. Previously use input's datalist, but now use a beautiful dropdown.
- Add button to expand/collapse all fields
- Add arrow button for less confuse how to toggle field settings
- Increase toggle settings area to the whole item header
- Always show a blank option for select to let you unselect an option
- Fix tab icon not updated when adding a new tab

### MB Rest API 2.0.1 – 2022-10-14

- Complete rewrite the plugin for clarity and maintainability
- Add support for settings page, allow you to get and update data for settings pages
- Show errors when updating non-existing fields
- Ensure group value is always an array. Fix PHP warning when group has no values.

### MB Custom Table 2.1.8 - 2023-11-27

- Allow developers to set message class via `$_GET['message-status']` param
- Add "mbct\_add\_data" and "mbct\_update\_data" filters to let developers change data before inserting/updating into the DBAdd filter
- "mbct\_{$this->model->name}\_total\_items" to get total items for models
- Fix PHP notice when accessing non-model pages

### MB User Profile 2.5.3 - 2023-11-16

- Support toggle password button on the login form
- Add filters for submit buttons ("rwmb\_profile\_info\_submit\_button" and "rwmb\_profile\_register\_submit\_button")
- Fix cannot delete Elementor widget (#28)

### MB Relationships 1.11.3 – 2023-11-16

- Add a config constant `MB_RELATIONSHIPS_DELETE_DATA` to delete plugin data when uninstalling (#74)
- Fix getting users not following the order in the admin (#73)

## 1.23.1 - 2023-09-18

### MB Builder 4.6.2 - 2023-09-18

- Improve UI for tabs: add icons (live preview when selecting an icon) & make tabs bold
- Supports field ID prefix in theme code
- Fix import settings page does not take ID from JSON file
- Generated PHP code for relationship: remove post and taxonomy setting when object type is user

### MB Group 1.3.18 - 2023-09-18

- Fix default collapsed not working if there's a subgroup with save\_state = true.

### MB Tabs 1.1.17 - 2023-09-18

- Fix "hide\_from\_front" not working if fields are in a tab

### MB Frontend Submission 4.4.2 - 2023-09-18

- Remove inline CSS on the front end (#46)
- Fix JS error when disable delete button (#44)

### MB User Profile 2.5.2 - 2023-09-18

- Remove inline CSS on the front end (#27)

### MB Relationships 1.11.2 – 2023-09-18

- Fix error when split query is true, occurs with recent update of Object Cache Pro.

## 1.23.0 - 2023-09-06

**Highlight:** New free extension MB Divi Integrator for showing Meta Box's fields in Divi

### MB Builder 4.6.1 - 2023-09-06

- UI tweak: Set icons on the header bar the same size
- UI tweak: Make it possible to remove the whole field name. Previously can't remove "(No label)" text.
- Add target "\_blank" to the "Edit the field group settings" button

### MB Views 1.12.5 - 2023-09-06

- Fix empty \`option\_name\` in settings pages (#21)

### MB Conditional Logic 1.6.20 - 2023-09-06

- Hide column if there is no fields or all fields are hidden #8

## 1.22.0 - 2023-08-16

**Highlight:**

This version has a big UI/UX improvement for MB Builder  which shows group's subfields when collapsing for a better overview of the field group's hierarchy and easier to reorder fields.

You can also toggle subfields and insert subfields to a specific group (without adding them to the end of the list and reorder later).

This version also has live edit for the field name and field ID on the header bar. Just click on them to edit and press Enter/Esc or click outside to finish editing.

See more details on our [blog post](https://metabox.io/meta-box-builder-ux-ui-update-visually-stunning-hierarchy-quick-add-quick-edit/) and [video](https://youtu.be/klTBsRAKCkM).

### MB Builder 4.6.0 - 2023-08-16

- Improve UI/UX: show group's subfields when collapsing with toggle subfields & insert subfields buttons
- Improve UI/UX: live edit field's name & id on the header bar
- Remove conditional logic for tab field
- Hide theme code if using custom block

### MB User Profile 2.5.0 - 2023-08-16

- Add form widgets for Elementor, Bricks, and Oxygen

### MB Custom Table 2.1.7 & MB REST API 1.5.1 - 2023-08-16

- Fix cannot update user meta in a custom table via REST API

### MB Frontend Submission 4.4.1 - 2023-08-16

- Add new filter "mbfs\_dashboard\_add\_new\_button" for Dashboard's "Add New" button

## 1.21.4 - 2023-07-18

### MB ACF Migration 1.1.0 – 2023-07-05

- Migrate post types and taxonomies

### MB Custom Post Types & Custom Taxonomies 2.5.5 – 2023-07-18

- Fix error when Meta Box is not available

### Meta Box – Elementor Integrator 2.1.10 – 2023-07-18

- Fix not working with cloneable group

### MB Builder 4.5.2 - 2023-07-18

- Fix auto id in the header bar when changing field name (#49)

### MB Tabs 1.1.16 - 2023-07-18

- Fix issue with tooltip left

## 1.21.3 - 2023-06-27

### MB Views 1.12.4 - 2023-06-27

- Hotfix for rendering shortcodes in views

### MB User Profile 2.4.1 - 2023-06-27

- Improve compatibility with WordPress's "profile\_update" action

### MB Geolocation 1.3.5 - 2023-06-27

- Fix "bind\_if\_empty" not working

### MB Elementor Integration 2.1.9 – 2023-06-27

- Display post title instead of post ID of post field in a group
- Fix error for groups

## 1.21.2 - 2023-06-23

### MB Admin Columns 1.7.1 - 2023-06-05

- Allow to sort text fields as numeric by setting "sort" = "numeric" (instead of "true")

### MB Builder 4.5.1 - 2023-06-23

- UI update: change checkbox style to toggle
- Fix error changing subfield type from text to checkbox
- Add safe-check for malformed data (fields with no or incorrect type)

### MB Views 1.12.3 - 2023-06-23

- Fix PHP notice error with the video field
- Fix line breaks in CSS getting wrapped in paragraphs
- Set default location to Shortcode

### MB Elementor Integration 2.1.8 – 2023-06-08

- Fix PHP notice for missing check on `mine_type`

### MB FacetWP Integration 1.1.3 – 2023-06-17

- Fix undefined when index select option field

## 1.21.1 - 2023-06-05

- Hotfix for the error of missing files during deployment in 1.21.0

## 1.21.0 - 2023-06-05

### MB User Profile 2.4.0 - 2023-06-05

- Add Gutenberg blocks for registration, login and edit profile forms

### MB Frontend Submission 4.4.0 - 2023-06-05

- Improve dashboard, adding "id" and "post\_type" parameter to shortcode, Gutenberg/Bricks/Elementor blocks.
- Passing the form config to "rwmb\_frontend\_submit\_button" filter instead of the post ID

### MB Builder 4.5.0 - 2023-06-05

- Update the UI & add ID columns
- Add settings for "min\_clone"

### MB Settings Page 2.1.11 - 2023-06-05

- Fix cannot set a settings page as a submenu of a custom model top-level menu

### MB AFC Migration 1.0.3 – 2023-05-25

- Fix `time_picker` field in ACF not migrated to `time` in Meta Box

## 1.20.3 - 2023-05-10

### MB Conditional Logic 1.6.19 - 2023-05-10

- Improve performance

### MB Custom Table 2.1.6 - 2023-05-10

- Add filter "mbct\_{$model}\_prepare\_items" for prepare items SQL
- Fix get only updated field value after running the API::update() and API::get()
- Fix not working with REST API
- Fix not working with revision

### MB Frontend Submission 4.3.1 - 2023-05-10

- Update title and category for blocks
- Fix dashboard not displaying trash icon

### MB Relationships 1.11.1 – 2023-05-10

- Fix draft items not being returned consistently

## 1.20.2 - 2023-04-04

### Highlights

- Now you can drag and drop subfields in/out groups in the builder
- Add "link" option for admin columns, which allows you to link the output (especially images) with post edit link (value "edit") or view post link (value "view").

### MB Builder 4.4.3 - 2023-04-04

- Fix support for drag and drop subfields in/out groups not working
- Fix redundant field prefix in validation
- Fix not showing theme code if users disable syntax highlighting

### MB ACF Migration 1.0.2 – 2023-04-03

- Fix “save\_field” option is disabled

### MB Admin Columns 1.7.0 - 2023-04-03

- Add "link" option for columns, which allows you to link the output (especially images) with post edit link (value "edit") or view post link (value "view").

### MB Custom Table 2.1.5 - 2023-04-04

- Fix `rwmb_set_meta()` not saving data

### MB Elementor Integration 2.1.7 – 2023-04-03

- Fix container background image
- Fix not working with image advanced on the settings page
- Fix PHP warnings

### MB Toolset Migration 1.0.2 – 2023-04-03

- Fix “save\_field” param is not set to true

### MB Beaver Builder Integration 2.1.1 – 2023-04-03

- Fix getting author ID

### MB FacetWP Integration 1.1.2 – 2023-04-03

- Support filtering the maps (Google Maps, Open Street Maps)

## 1.20.1 - 2023-03-24

- Fix missing files for Gutenberg blocks of Frontend Submission


## 1.20.0 - 2023-03-23

### MB Frontend Submission 4.3.0 - 2023-03-23

- Add Gutenberg blocks for submission form and user dashboard
- Add Bricks elements for form submission and user dashboard
- Add Elementor widgets for form submission and user dashboard

### MB User Profile 2.2.2 - 2023-03-23

- Add toggle password button

### MB Beaver Builder Integration 2.1.0 – 2023-03-23

- Add support for condition “Is Not Set”
- Add support for conditional shortcodes
- Add support for shortcode attributes for taxonomies
- Support showing image fields with BB shortcode
- Make all image fields work with photo and gallery modules (#3)
- Fix single image alt text and PHP warning “trying to access array”

### MB Settings Page 2.1.10 - 2023-03-23

- Add hook `mb_settings_page_after_title` for developers to output anything after the page title

### MB Views 1.12.2 - 2023-03-23

- Fix rendering shortcode when view is draft
- Fix parsing file info when using custom upload dir

### MB Conditional Logic 1.6.18 - 2023-03-23

- Fix custom callback not working
- Fix not working if the field is visible but meta box is hidden

### MB Builder 4.4.2 - 2023-03-23

- Fix block category not displaying saved value
- Fix duplicated suggested field IDs

### MB Custom Post Type 2.5.3 – 2023-03-23

- Fix URL when installing with Composer

### MB Template 1.2.3 - 2023-03-23

- Fix URL when installing with Composer

### MB FacetWP Integration 1.1.1 – 2023-03-21

- Fix compatibility with the latest version of FacetWP

## 1.19.1 - 2023-03-08

### MB Builder 4.4.1 - 2023-03-08

- Do not generate code for fields with no content like tab, heading and divider.

### MB Revision 1.3.4 - 2023-03-08

- Fix unicode characters displayed in unicode code

## 1.19.0 - 2023-03-08

### MB Builder 4.4.0 - 2023-03-08

- Introducing Theme Code: auto generate code to display fields. See [blog post](https://metabox.io/theme-code/).

### MB Custom Post Type 2.5.2 – 2023-02-27

- Enqueue Font Awesome only when a post type use it (#55)

### MB Settings Page 2.1.9 - 2023-03-08

- Add support for Font Awesome when registering settings page with code (#6)

### MB Views 1.12.1 - 2023-03-08

- Fix missing import views

### MB Group 1.3.17 - 2023-02-08

- Fix incorrect group title with radio/checkbox/switch

### MB Tabs 1.1.15 - 2023-03-08

- Auto enqueue Font Awesome when it's used

## 1.18.1 - 2023-02-01

### MB User Profile 2.2.1 - 2023-02-01

- Fix error sending confirmation email

## 1.18.0 - 2023-02-01

### MB Views 1.12.0 - 2023-01-31

- Use Mocaco editor with fullscreen & word wrap toggle
- Fix import on Windows

### MB User Profile 2.2.0 - 2023-02-01

- Create a settings page (Meta Box > User Profile) for both email confirmation & force password change.
- Add shortcode attrs "label\_title" for the form title. Default empty.
- Localize default form title.

### MB Builder 4.3.1 - 2023-01-31

- Increase the size of the generated code. Make it readonly.
- Make the sidebar sticky
- Fix "save\_field" param not saving correctly

### MB Custom Post Type 2.5.1 – 2023-01-31

- Fix CPT export wrong file name
- Update description for FontAwesome icon select

## 1.17.0 - 2023-01-17

### MB Frontend Submission 4.2.0 - 2023-01-11

- Add `allow_scroll` attribute to prevent scrolling after submitting with ajax
- Add action hooks for frontend actions
- Fix remote validation not working
- Fix deprecated notice when viewing a form the first time
- Fix action `rwmb_frontend_after_display_confirmation` not working with ajax

### MB Builder 4.3.0 - 2023-01-16

- Add support for Font Awesome in admin menu
- Add icon label and allow to search icons by label
- Fix import on Windows
- Use local tooltip JS library

### MB Tabs 1.1.14 - 2023-01-16

- Fix issue with tooltip left

### MB Tooltip 1.1.7 - 2023-01-16

- Use local tooltip JS library

### MB Custom Post Type 2.5.0 – 2023-01-13

- Add support for Font Awesome in admin menu
- Add icon label and allow to search icons by label
- Fix import on Windows

## 1.16.10 - 2023-01-03-

### MB Template 1.2.2 - 2023-01-03

- Fix parsing template files

### MB Custom Post Types & Custom Taxonomies 2.4.0 - 2022-12-24

- Add import export feature for post types and taxonomies

### MB Views 1.11.5 - 2022-12-28

- Add import export feature

### MB Builder 4.2.0 - 2023-01-03

- Add import export for relationships and settings pages

### MB Beaver Builder Integration 2.0.1

- Fix access array offset on value

### Meta Box - Facet Integrator 1.1.0 - 2023-01-03

- Add support for relationships

## 1.16.9 - 2022-12-23

### MB Builder 4.1.18 - 2022-12-23

- Set default post type if empty
- Remove empty post types when parsing

### MB Toolset Migration 1.0.1 – 2022-12-22

- Fix not migrating post references

### MB Elementor Integration 2.1.6 – 2022-12-20

- Fix PHP errors showing image(s)

### MB Relationships 1.11.0 – 2022-12-22

- Add REST API support. See [docs](https://docs.metabox.io/extensions/mb-relationships/#rest-api) for usage. Props @macbookandrew.

### MB Template 1.2.1 - 2022-12-23

- Fix error if syntax is invalid
- Add notifications for settings page and site-wide when the syntax is error
- Use CodeMirror

## 1.16.8 - 2022-12-13

### MB Custom Post Type

- Fix compatibility with the latest version of Meta Box

## 1.16.7 - 2022-12-05

### MB Blocks 1.4.1 - 2022-12-05

- Add "version" param to improve caching for assets
- Add "mb\_{ $block\_id }\_settings" filter when registering blocks

### MB Custom Table 2.1.4 - 2022-12-05

- Fix PHP notice for wp\_cache\_get in WP 6.1
- Fix term meta is not deleted after deleting term
- Fix not saving for custom blocks with custom post types

### MB Elementor Integration 2.1.5 - 2022-12-05

- Fix showing single image

### MB Frontend Submission 4.1.2 - 2022-12-05

- Allow to add new post after submitting
- Add type="submit" to buttons
- Fix not submitting forms if open pages in many tabs

### MB Tooltip 1.1.6 - 2022-12-05

- Fix tooltip overflow the screen when using MB Blocks and show fields on the side

### MB User Profile 2.1.0 - 2022-12-05

- Add new feature: force changing password for the first login
- Allow to overwrite confirmation email and password reset email by creating "mb-user-profile" folder in the theme and copy the files from plugin's "templates" folder to that theme's folder.
- Add type="submit" to buttons
- Support appending user role instead of one role with new attribute "append\_role" ("true"/"false")
- Add validate and before/after process hooks for login form (similar to edit profile form) to let developers validate/process extra fields if needed

### MB Views 1.11.4 - 2022-12-05

- Fix conflicts with Breakdance and other plugins that use Twig
- Fix custom map not rendering if there are more shortcodes on a page
- Fix PHP8 deprecated notice

### MB Builder 4.1.17 - 2022-12-05

- Fix conflicts with Breakdance and other plugins that use Twig

### MB Group 1.3.16 - 2022-12-05

- Fix 'Remove' element in the title bar is too long

## 1.16.6 - 2022-11-11

### MB Admin Columns 1.6.3 - 2022-11-11

- Fix wrong label localization for taxonomy dropdown filter

### MB Custom Post Types 2.3.4 - 2022-11-11

- Fix meta\_box\_cb settings not working properly

### MB Elementor Integration 2.1.4 - 2022-11-11

- Fix advanced features (before, after and fallback content) not displaying when loading the skin in the post template
- Fix fatal error when Meta Box is not active

### MB Settings Page 2.1.8 - 2022-11-11

- Add filter for page title and remove esc\_html

### MB Builder 4.1.16 - 2022-11-11

- Hide option\_name under Advanced settings to reduce confusion for users when creating settings pages
- Fix ID not autogenerated when changing label
- Update tippy
- Add autocomplete for key-value options such as post's query args, wysiwyg editor settings, etc.

### MB Group 1.3.15 - 2022-11-11

- Fix "remove" link hidden when the group title is long

### MB FacetWP Integration 1.0.4 - 2022-10-22

- Fix PHP warnings

## 1.16.5 - 2022-09-20

### MB Custom Table 2.1.3 - 2022-09-20

- Fix not saving data properly

### MB Elementor Integration 2.1.3 - 2022-09-20

- Fix error with count() function
- Fix displaying subfield URL in a group
- Fix post title field duplicated

### MB Frontend Submission 4.1.1 - 2022-09-20

- Fix invalid request error when submitting twice with Ajax

### MB Builder 4.1.15 - 2022-09-20

- Fix callback function of checkbox list is not generated to PHP code

## 1.16.4 - 2022-08-11

### MB Builder 4.1.14 - 2022-08-11

- Add controls for hide\_from\_front and hide\_from\_rest to control field visibility on the front end and in REST responses

### MB Geolocation 1.3.4 - 2022-08-11

- Fix not populating address values without a map field

### MB Admin Columns 1.6.2 - 2022-08-11

- Fix sorting not working with taxonomy field
- Fix search for custom models when WHERE condition is already available

### MB Custom Post Types 2.3.3 - 2022-07-27

- Improve post type and taxonomy label capitalization

### MB Elementor Integration 2.1.2 - 2022-08-11

- Fix count() error

### MB Frontend Submission 4.1.0 - 2022-08-11

- Do not show fields with hide\_from\_front = true

### MB REST API 1.5.0 - 2022-07-29

- Do not show fields with hide\_from\_rest = true
- Ignore MB User Profile fields

### MB Settings Page 2.1.7 - 2022-08-11

- Fix meta box not showing for settings page under Media

### MB Views 1.11.3 - 2022-08-11

- Fix rendering maps with the default style

## 1.16.3 - 2022-07-14

### MB Admin Columns 1.6.1

- Fix conflicts when users hook to "where" and "order" filters for custom models
- Remove FILTER\_SANITIZE\_STRING to compatible with PHP 8

### MB Custom Table 2.1.2

- Fix calling hooks (like mbct\_after\_update) multiple times if there are more than one meta box that use the same custom table
- Fix missing $object\_id for mbct\_after\_add hook
- Add CSS classes to the body for model pages
- Add more params for row\_actions hook
- Add bulk\_actions filter

### MB Elementor Integration 2.1.1

- Fix not working with settings page

### MB Settings Page 2.1.6

- Fix field wysiwyg not respecting textarea\_rows
- Fix settings page can't access the submenu

### MB User Profile 2.0.3

- Fix built-in meta box id not working properly in shortcodes

### MB Views 1.11.2

- Fix Google Maps and Open Street Maps JS loads on the front end when not used

### MB Columns 1.2.15

- Handle when columns atts is not array

### MB Tabs 1.1.13

- Fix field wysiwyg not respecting textarea\_rows

## 1.16.2 - 2022-06-23

### MB Elementor Integration

- Add support for cloneable groups

### MB Views

- Fix Meta Box fields overwrite user fields, which make them empty

## 1.16.1 - 2022-05-26

- Fix wrong text for installing Meta Box
- Fix missing MB Toolset Migration in the extension list

## 1.16.0 - 2022-05-17

- Added MB Toolset Migration

### MB Custom Post Type

- Fix `meta_box_cb` parameter not working property for tags and categories

### MB Frontend Submission

- Add more hooks for the dashboard
- Avoid passing the field group IDs assigned to users

### MB Rest API

- Fix not working with `_filter`.

### MB Settings Page

- Fix not accessing sub-settings pages (sub-menu) when creating with MB Builder

### MB User Profile

- Avoid passing field group IDs assigned to posts or terms

### MB Builder

- Fix query args in relationships not working
- Fix generate code for settings page missing the ID

### MB Geolocation

- Fix OSM still loading google maps scripts
- Add support for opening\_hours, phone, website, formatted\_phone\_number, international\_phone\_number, rating, user\_ratings\_total

### MB Group

- Fix incorrect the label for subfield
- Fix Collapsible group field toggle arrow is upside down on load

### MB Tabs

- Fix validating fields in inactive tabs

## 1.15.8 - 2022-04-01

### MB Views

- Auto parse fields value for custom post/term/user queries, so you can use post.field\_id in your custom queries without using the old mb.rwmb\_meta or mb.get\_post\_meta.
- Fix notice for OSM field

### MB Builder

- Fix "no link" for relationship admin columns not working
- Remove non-recommended HTML5 field types (fallback to text)
- Make the inserter one-column

### MB Frontend Submission

- Fix showing form after success ajax submit
- Allow for filtering the title links using new mbfs\_dashboard\_post\_title filter
- Fix resubmit failed after validation

## 1.15.7 - 2022-03-17

### MB Elementor Integration

- Fix color not working for style tab

### MB Relationships

- Shortcode: support removing the link to items with `link="false"`

### MB Conditional Logic

- Export `runConditionalLogic` function to the global `rwmb` object to use in other scripts
- Fix not working in cloneable groups due to arrow function

### MB Tabs

- Fix not working with blocks

## 1.15.6 - 2022-02-16

### MB Frontend Submission

- Don't use session to store form config and errors. Compatible with WordPress and other cache plugins.
- Remove FILTER\_SANITIZE\_STRING as deprecated in PHP 8.1
- Fix deprecated notice for parameter with a default value in PHP 8

### MB User Profile

- Don't use session to store form config and errors to ensure compatibility with WordPress and cache plugins
- Change param for reset password to avoid potential conflict
- Remove FILTER\_SANITIZE\_STRING as deprecated in PHP 8.1
- Fix color field not rendering
- Fix not showing confirmation message when resetting password

### MB Custom Table

- Add confirmation before delete a model
- Add hooks before/after adding/creating/updating/deleting models
- Add hooks for custom model table list
- Add hooks to the submit box - custom model
- Fix bug custom model pagination

### MB Beaver Builder Integration

- Breaking change: change the way to select color field. If you select a color field in a previous version, please re-select it.
- Fix single\_image field not showing in shortcode (insert mode)

### MB Builder

- Add suggestions for common attributes for the Custom HTML5 attributes, which help define basic validation rules easier

### MB Group

- Remove `esc_meta` method as Meta Box doesn't use it anymore

## 1.15.5 - 2022-01-21

### MB Views

- Add support to render views with external Twig files

### MB Conditional Logic

- Fix blank page in Oxygen
- Fix conditional logic not running due to window.load event not firing
- Fix not show/hide meta box in the front end
- Improve code using ES6

### MB User Profile

- Fix lost password link not working if WooCommerce is activated

## 1.15.4 - 2022-01-16

- Remove TGMPA, use a custom script to manage dependency

### MB Custom Post Type

- Fix the menu icon not showing from v2.2.6

### MB User Profile

- Update style for password strength

### MB Views

- Add code to ask to enable MB Conditional Logic and MB Tooltip inside AIO

### MB Builder

- Fix icon for tabs not working
- Add tooltip to explain max 10 items are displayed in the advanced location rules

### MB Geolocation

- Fix OSM field binding merging two fields doesn't work

### MB Tabs

- Fix tabs overflow container when too many tabs
- Fix JS validation error when no tabs

## 1.15.3 - 2021-12-15

### MB Views

- Output CSS in header if possible to make HTML validated
- Fix code area overflow to the right
- Fix REST requests not working if permalink is plain
- Always load list of posts/items when click the select box

### MB Custom Post Type

- Add migration tool from Custom Post Type UI

### Meta Box - Beaver Themer Integration

- Fix deprecated notice for conditional logic

### MB Columns

- Fix button in text mode - WYSIWYG field - has 100% width
- Remove unneeded CSS

### MB Relationships

- Fix php notice when delete an object
- Fix each\_connected does not working for users

## 1.15.2 - 2021-10-25

### MB Custom Post Type

- Fix generated code if labels contain single quotes
- Update list of Dashicons

### MB Frontend Submission

- Fix not detecting shortcode of edit page content in Oxygen Builder
- Add filter mbfs\_dashboard\_query\_args to modify the query args of posts in Dashboard
- Fix post title/content are removed if the shortcode doesn't have post fields.

### MB Relationships

- Fix deleting post objects, relationships are not deleted

### MB Settings Page

- Fix style for fields in the Customizer
- Improve style for no-boxes settings page

### MB User Profile

- Add email confirmation for registration form. Just add `email_confirmation="true"` to the form shortcode.

### MB Builder

- Admin columns: list correct columns for terms and users, also set default value for column position.
- Update list of Dashicons
- Delete clone option for taxonomy field as it's not cloneable
- Set default value of textarea field as a textarea, not input

## 1.15.1 - 2021-09-16

### MB Custom Table

- Rewrite the plugin with namespaces
- Add support for custom models, e.g. creating models with custom tables with no relations to WordPress posts, users or tems.

### MB Admin Columns

- Add support for custom models (from MB Custom Table extension)

### MB Custom Post Types & Custom Taxonomies

- Fix generated code
- Restrict access to admin menu for admins only

## 1.15.0 - 2021-09-08

- Add MB Rank Math extension

### MB Frontend Submission

- Fix post count not working on the dashboard page

### MB User Profile

- Update style for login/register form to match new style of Meta Box

### MB Relationships

- Fix querying by multiple relationships not working when a relationship has no connections.

### MB Yoast SEO Integration

- Fix the plugin not working due to change in Yoast SEO API.

## 1.14.3 - 2021-08-05

### MB Views

- Add filter for view data and Twig environment
- Fix PHP warning array\_map()
- Fix notice error when adding a new contact form with CF7
- Fix wysiwyg content not auto add paragraphs
- Fix taxonomy archive location
- Don't generate rewrite tag for view category

### MB Relationships

- Fix warning when querying multiple relationships

## 1.14.2 - 2021-07-28

### MB Blocks

- Add support for InnerBlocks

### MB Custom Post Type

- Fix custom archive slug not working

### MB Frontend Submission

- Fix color picker not working on the frontend
- Only redirect if validation is successful
- Show the form if validation is not successful

### MB Yoast SEO Integration

- Fix Yoast SEO not counting text, due to change in the Yoast SEO’s API.
- Fix undefined get\_current\_screen function on the frontend.

## 1.14.1 - 2021-07-14

### MB Builder

- Fix empty value for conditional logic not saving
- Fix showing wrong "for" admin column for taxonomies
- Fix style conflict with Yoast SEO
- Remove "size" attribute for key value field type

### MB User Meta

- Add locale to list of user fields
- Fix get current screen warning

### MB User Profile

- Fix no error class for error message
- Fix languages not loaded
- Fix get current screen object warning

### MB Relationships

- Fix reciprocal relationships not working with terms.
- Fix querying multiple relationships with relation AND not working.
- Fix bug when a post and a user have the same ID.
- Fix admin columns, API and WP\_Query not returning posts when the post type has exclude\_from\_search = true.
- Improve performance by not checking relationship table each time a page loads.

### MB Custom Post Type

- Add a warning if permalink is default.
- Add options for menu icon type, which supports SVG and custom URL.

### MB Blocks

- Fix width of inputs on sidebar

### MB ACF Migration

- Fix required rule not migrated properly

## 1.14.0 - 2021-06-15

- Add new extension: MB ACF Migration for migrating field groups and custom fields from Advanced Custom Fields to Meta Box

### MB Blocks

- Improve the toggle button
- Make input, select, textarea, select2, input group, columns 100% width on sidebar

### MB Columns

- Make columns 100% width on sidebar

### MB Tooltip

- Fix empty icon value not showing default icon

## 1.13.13 - 2021-06-02-

### MB Columns

- Fix fatal error: class not found

## 1.13.12 - 2021-06-02-

### MB Builder

- Add prepend, append for HTML5 fields
- Don't filter empty array, remove only empty string values in array

### MB Columns

- Set width 100% for input group and select2
- Fix responsiveness on mobiles
- Use namespace
- Move margin bottom to column instead of row to make it works consistently on mobiles

### MB Elementor Integration

- Fix field not showing if it’s the default selected field
- Update to be compatible with the latest version of Elementor

## 1.13.11 - 2021-05-17-

### MB Builder

- Fix fatal error for settings pages

## 1.13.10 - 2021-05-14-

### MB Builder

- Don't submit form when pressing Enter
- Fix blank page for old field groups (from v3) with include exclude rules
- Fix block alignment not saving 2 or more values
- Fix empty conditional logic rules break the page

### MB Columns

- Set input, select, textarea width 100% in columns
- Use flexbox

## 1.13.9 - 2021-04-29

### MB Frontend Submission

- Fix validation not working with ajax
- Add ID to forms
- Use post type labels instead of "posts"
- Scroll to message after submitting instead of to the form
- Add options to show/hide welcome message and change the columns in the dashboard. Props Biden.
- Add language files

### MB Custom Post Types & Custom Taxonomies

- Update Youtube video and documentation link in the welcome page.
- Fix supports = false when no supports features are selected
- Fix custom capabilities when set plural and singular names are similar

### MB Views

- Add categories for views
- Render views by name (slug)
- Fix not getting sub-field value in a group for video, map, file, sidebar

### Meta Box Text Limiter

- Fix notice “Trying to access array offset” (by checking field value if field not found).

## 1.13.8 - 2021-04-19

### MB Custom Post Type

- Add tooltips to all settings
- Show slug in lists of post types and taxonomies
- Show error and disable publish button if slug is one of WordPress reserved terms
- Set full custom capabilities for post types and add guidance message for custom capabilities
- Add missing labels for posts table headings
- Fix menu position not working

### MB User Profile

- Fix showing multi messages if put multi forms on a same page
- Show an error/warning if there are unvailable meta box IDs in shortcodes
- Add user\_registered field to allow change registration date
- Add role to list of user fields to allow change user role
- Fix password strength JS warning

### MB Conditional Logic

- Fix checkbox in groups

### MB Builder

- Fix tooltip for Google Maps API key
- Ignore scientific number

## 1.13.7 - 2021-04-01

### MB Builder

- Fix console log, support meta box ID starts with number
- Fix style for settings icon in the meta box
- Fix localizing global MBB variable

## 1.13.6 - 2021-04-01

### MB Views

- Fix compatibility with Gantry framework

### MB Builder

- Add default ID when create the new relationship
- Update Twig

## 1.13.5 - 2021-03-19

### MB Builder

- Add support for admin columns extension
- Add support for reciprocal relationships
- Add support for admin columns for relationships
- Do not custom table columns for heading, divider and button fields
- Fix divider inside groups
- Fix broken edit custom fields page when permalink is plain
- Fix not parsing block Dashicons

### MB Custom Post Type

- Fix default value for menu\_position
- Fix number in plural name.

## 1.13.4 - 2021-02-23

### MB Blocks

- Support saving block attributes to post meta and custom table. See docs for usage.
- Don't load FontAwesome in the front end

### MB Group

- Make sub-fields inherit clone\_default from group when not set

### MB Builder

- Fix missing export feature
- Fix trash & untrash swiping fields
- Fix default values for settings page, relationships not showing
- Fix settings pages not showing when no slug is entered
- Fix re-select top-menu not working

## 1.13.3 - 2021-02-09

### MB Builder

- Fix upgrade not working.
- Allow to force migrating data from version < 4 by adding a query string `mbb_version=3.3` to the URL
- Update links for `query_args` for terms and users

### MB Geolocation

- Fix auto populate not working for city, country fields

## 1.13.2 - 2021-02-06

### MB Builder

- Add confirmation when deleting a field
- Fix importing from old version (.dat) files
- Fix parsing tab when importing/upgrading

### MB Geolocation

- Add support for Open Street Maps
- Add support for two-way binding, e.g. when you update pin on the map, the latitude/longitude fields are auto updated (and vise-versa)

### MB Blocks

- Allow to set foreground, background for FontAwesome icon.

## 1.13.1 - 2021-02-03

### MB Builder

- Fix post type and taxonomy setting not saving multiple entries, and allows to save empty values.
- Fix block render code not saving
- Fix parsing button group inline settings
- Fix error getting PHP code for settings page & relationships for PHP < 8.
- Fix exporting/importing data not working
- Fix missing menu icon for settings page
- Fix wrong info in the admin columns
- Fix error if no field group ID is entered
- Updated to Twig version 3

### MB Frontend Submission

- Improve HTML validation
- Improve CSS for dashboard
- Add CSS class for error Ajax requests
- Fix no messages if Ajax requests are invalid

### MB Views

- Update Twig to version 3

## 1.13.0 - 2021-01-26

- Load extensions late. If you install a single extension, then that single version will be loaded instead of the version bundled in the AIO.

### MB Builder

- Completely rewrite in React with more beautiful, cleaner and faster UI
- Improve UX & interactions
- Support creating settings pages & relationships
- Support validation
- Support all field & field group settings
- Add API to extend the builder with more field controls and types

### MB Custom Table

- Fix not saving empty values

## 1.12.3 - 2020-12-29

- Remove dashboard news widget

### MB Builder

- Fix escaping multibyte characters
- Fix width of items in new WP version

### MB Blocks

- Fix field IDs starts with underscores not saving

## 1.12.2 - 2020-12-17

- Update TGMPA text and don't load extensions when Meta Box is inactive

## 1.12.1 - 2020-12-15

### MB Custom Post Type

- Fix missing options for show\_in\_menu and menu\_position
- Fix label reverts to English
- Fix icon styling in WordPress 5.6

## 1.12.0 - 2020-12-02

### MB Views

- Requires PHP 7.2.5+
- Use Twig 3.x which has more features
- Rewrite admin JavaScript using React hooks
- Fix PHP notices if no post/user found
- Fix adding custom attributes to shortcodes

### MB Custom Post Type

- Rewrite the UI with React.
- Update PHP code to use Composer.

### MB Custom Table

- Fix saving fields with "multiple" setting

### MB Term Meta

- Fix file field not uploading

### MB User Profile

- Fix Google reCaptcha not verifying
- Fix users are forced to logout when submitting front-end posts on non-HTTPS sites

### MB Tooltip

- Fix clicking tooltip submits the form.

## 1.11.19 - 2020-09-28-

### MB Group

- Fix group title shows null for subfield value when empty
- Fix clone index not rendering properly in the group title when updating subfield values

### MB Tabs

- Fix undefined `tab_default_value`.

### MB Relationship

- Fix SQL error when relationship ID contains dashes

### MB Views

- Add support for custom location rules via template tags or PHP code
- Improve search for post title only in Location rules
- Allow to set views for posts with 'any' status, which supports draft, protected posts.

### MB Settings Page

- Fix `no-box` style in WordPress 5.5
- Fix field value does not save after closing the Customizer
- Support `seamless` style per meta box

### MB Builder

- Fix default value for button group inline option.

## 1.11.18 - 2020-08-13-

### MB Settings Page & MB Builder

- Readd missing files.

## 1.11.17 - 2020-08-11-

### MB Views

- Add filter for checking action active
- Add hook for location validation
- Improve CSS

### MB User Profile

- Add `role` attribute to register shortcode, allowing to set role for new users.

### MB Settings Page

- Fix field value not updating in Customizer draft share preview link

### MB Relationships

- Fix non-reciprocal relationships break multiple relationship query
- Allow reciprocal relationships for users, terms

### MB Custom Post Type

- Use WordPress’s built-in clipboard script

## 1.11.16 - 2020-07-16-

### MB Builder

- Removed unneeded default value for some parameters

## 1.11.15 - 2020-07-07-

### MB Blocks

- Fix CSS affected normal side meta boxes.
- Fix blocks with side context not working.

### MB Builder

- Fix 0 value not working for button group choices

### MB Relationships

- Fix reciprocal post query doesn’t work with custom ‘orderby’ param

## 1.11.14 - 2020-07-06-

### MB Views

- Fix blocks not rendering in views
- Fix rendering fieldset text and text list fields
- Fix wrong version for assets

### MB Blocks

- Fix block doesn’t receive unique ID when duplicated.
- Fix "name" param not working in the block attributes.

### MB Relationships

- Fix wrong table prefix for getting reciprocal relationships.
- Add hooks for "add", "delete" relationships.

### MB Tabs

- Fix clicking icons not switching tabs.

## 1.11.13 - 2020-06-18-

### MB User Profile

- Fix shortcode profile info show admin info

### MB Builder

- Fix multi-file uploads not working in GravityForms

### MB Relationships

- Add filter for relationship settings
- Fix reciprocal not follow the order of the admin

## 1.11.12 - 2020-06-09-

- Add support for Loco Translate to translate all extensions

## 1.11.11 - 2020-06-08-

### MB Views

- Allow developers to bypass the default renderer and use an alternative method like Timber
- Fix JS error when MB Relationships is not active
- Fix not closing modal after insert

### MB Tooltip

- Add `allow_html` parameter to allow rendering HTML content or not.

## 1.11.10 - 2020-06-02-

### MB Views

- Allow to output views at a specific WP action
- Add render mode for whole page including header & footer

### MB Tabs

- Update style

## 1.11.9 - 2020-05-27

### MB User Profile

- Rewrite JavaScript to handle multiple forms on a same page (like sign up and log in).

### MB Builder

- Fix not parsing std for checkbox list
- Add clone attribute for checkbox
- Add messages when import error
- Use CodeMirror for export code
- Use CDN for assets

### MB Settings Page

- Fix no-boxes style not working if no tabs
- Improve style for left tabs with no boxes

### MB Columns

- Prevent checkboxes and radio buttons from trying to take on 99% of the available width when viewed on a small device
- Fix notice when a field has a column that's not available in the meta box settings

## 1.11.8 - 2020-05-19

### MB Views

- Fix not toggling group sub-fields
- Add View tab to insert sub-views

## 1.11.7 - 2020-05-11

### MB Conditional Logic

- Fix JSON-encode conditions

### MB Views

- Add support for terms
- Fix location rules not working for categories and tags
- Fix checkbox field output

### MB User Profile

- Add "show\_if\_user\_can" attribute for register shortcode, allowing users with proper capability to register for other people
- Allow to edit default user fields like display\_name, user\_email, user\_url

### MB Frontend Submission

- Rewrite the script, making it work more reliably and compatible with validation in Meta Box 5.3.0
- Add confirmation before deleting a post
- Fix edit=true not working
- Fix post hidden when click delete but not confirm on the dashboard
- Fix thumbnail not saving if user is not logged in

## 1.11.6 - 2020-04-27

### MB Group

- Fix timestamp for date field not showing correctly.

### MB Frontend Submission

- Fix validation not working with Ajax
- Don't hide post fields in admin if post has support for them

### MB Template

- Fix fatal error due to function not exists
- Use namespace

## 1.11.5 - 2020-04-23

### MB Relationships

- Add API to get relationships and relationships settings (hotfix for MB Views to work with relationships).

## 1.11.4 - 2020-04-22

### MB Views

- Add support for relationships (see Query tab)
- Add ability to include and inherit views in other views
- Fix error on all views screen when a view is not a archive/singular view

### MB Blocks

- Fix JS warning in the console for class/className
- Add support for FontAwesome icon, users only need to define 'icon' => 'fas fa-user'. No need to enqueue CSS for FontAwesome. Support FontAwesome 5, free version.

## 1.11.3 - 2020-04-17

### MB Blocks

- Fix style for fields on the sidebar
- Bypass if WordPress version < 5.0

### MB Views

- Fix settings fields empty
- Don't show user profile meta boxes
- Don't show relationships meta boxes

### MB Relationships

- Fix each\_connected not working

### MB Custom Post Type

- Fix notice: Undefined index `meta_box_cb`
- Fix warning for `supports` parameter in WordPress 5.3
- Add filter for advanced settings manipulation
- Improve toggle buttons

### MB Group

- Update the way to get child field meta. Requires Meta Box 5.2.9+.

### MB Include Exclude

- Add "capability" rule

## 1.11.2 - 2020-04-08

### MB Views

- Auto select "All" when add a new rule
- Add admin columns
- Show position for archive as well

### MB Relationships

- Fix query of the storage get function for reciprocal relationships
- Fix each\_connected not working

### MB Admin Columns

- Change: if you use the plugin to create custom columns, please update the code (see [docs](https://docs.metabox.io/extensions/mb-admin-columns/#custom-admin-columns) for details)
- Fix no output printed when the meta value is 0.
- Use Composer to load files

## 1.11.1 - 2020-03-23

### MB Builder

- Fix tooltip not showing
- Fix array to string conversion for key-value field

### MB Elementor Integration

- Add support for number field

## 1.11.0 - 2020-03-11

- Add [MB Views](https://metabox.io/plugins/mb-views/)

### MB Builder

- Allows users to deselect columns settings for fields
- Fix error when activate with Meta Box AIO plugin
- Fix copy to clipboard not working

### MB User Profile

- Fix submit button stuck as disabled when validation fails
- Disable password strength meter on login form.

## 1.10.18 - 2020-02-19-

### MB User Profile

- Fix wrong JS file name.

### MB Blocks

- Add support for block preview.

## 1.10.17 - 2020-02-07-

### MB Admin Columns

#### Fixed

- Fix sorting by fields from custom tables not working

### MB Frontend Submission

#### Added

- Allow to set order for post fields and mix post fields with meta box fields.

#### Fixed

- Fix security issue when passing parameters via query string. Now allow only post ID to pass via query string.

#### Changed

- Make shortcode's `id` parameter optional.

### MB User Profile

#### Added

- Add support for Google reCaptcha

### MB Beaver Builder Integration

#### Added

- Add support for post author’s custom fields
- Add support for user’s custom fields

### MB FacetWP Integration

#### Fixed

- Fixed PHP warning if post type doesn’t exist

### MB Group

#### Fixed

- Fix error when saving empty field group in MB Builder
- Fix duplicated ID for fieldset\_text

## 1.10.16 - 2020-01-31-

### MB Elementor Integration

#### Added

- Add support for output from `file_input` field

### MB Custom Table

#### Changed

- Make Storage::table property public
- Change ID column to bigint(20) unsigned to compatible with posts table

## 1.10.15 - 2020-01-22-

### MB User Profile

#### Fixed

- Fix not enqueuing assets on the front end

### MB Frontend Submission

#### Added

- Add support for ajax submission
- Add support for Google reCaptcha
- Add dashboard, allowing users to view, edit or delete their submitted posts
- Add move\_to\_trash parameter, allowing to delete posts permanently or not

## 1.10.14 - 2020-01-16-

### MB Blocks

#### Fixed

- Fix 'std' param not working
- Fix field description margin

### MB Custom Table

#### Fixed

- Fix not saving for term meta

### MB Relationships

#### Added

- Add support for reciprocal relationships
- Add `mb_relationships_registered` hook

### MB Term Meta

#### Fixed

- Fix not saving to custom tables

### Meta Box – Beaver Themer Integrator

#### Changed

- Conditional logic: make option name a select field for settings page

### MB Conditional Logic

#### Fixed

- Make it work with fields inside dynamic blocks created with [MB Blocks](https://metabox.io/plugins/mb-blocks/).
- Fix not working in the Customizer (for Settings Page extension).

## 1.10.13 - 2019-12-24-

### Fixed

- Fix Yoast SEO & text limiter extensions not loading properly.

### MB Settings Page

#### Added

- Add backup field for settings pages. To add a backup field to a settings page, simply create a field with `'type' => 'backup'`.
- Add tabs left style.
- Add tab icon.

#### Fixed

- Fix group order not saving
- Fix showing Customizer section appear in edit post screen
- Show error fields in hidden tabs when validating

### MB User Meta

#### Fixed

- Fix not working with custom table and relationships.

### MB User Profile

#### Fixed

- Fix label\_lost\_password not working for login form.

### MB Conditional Logic

#### Fixed

- Fix toggle type parameter not working.

### MB Tabs

#### Fixed

- Show error fields in hidden tabs when validating.

## 1.10.12 - 2019-12-19-

### MB Blocks

#### Added

- Add `mode` parameter for blocks in normal mode which allows developer to specify initial mode (`preview` or `edit`) for blocks.

### MB Conditional Logic

#### Fixed

- Fix conditional logic break with Frontend Submission. Now the JS is enqueued only if conditions exist.

### MB Comment Meta

#### Added

- Add support for comment meta. Requires MB Comment Meta plugin.

## 1.10.11 - 2019-12-11-

### MB Columns

#### Fixed

- Prevent checkboxes and radio buttons from trying to take on 99% of the available width when viewed on a small device

### MB Builder

#### Fixed

- Fix performance issue with MB Include Exclude extension when site has a lot of users.

#### Changed

- Show options in Settings tab for Gutenberg-supported post types.
- Hide edited user role/id when user meta extension is not active.

### MB User Meta

#### Changed

- Hide duplicated WordPress-registered user fields in the admin screen

### MB User Profile

#### Added

- Add lost/reset password feature which is built-in the login form.
- Add `email_as_username` parameter for the register shortcode to allows users to use email as username (no username input anymore).

#### Changed

- Show login form on edit profile page if user is not logged in.
- Re-style login form.

## 1.10.10 - 2019-12-04-

### MB Frontend Submission

#### Fixed

- Fix delete not working

### MB Blocks

#### Fixed

- Fix the scrolling issue for color field

## 1.10.9 - 2019-11-28

### MB Blocks

#### Fixed

- Fix group order not saving
- Fix background issue when a theme/plugin change Gutenberg background

#### Changed

- Add class for wrapper div

### MB Builder

#### Fixed

- Fix performance issue with MB Include Exclude extension
- Fix getting group value for blocks

#### Changed

- Improve field searching

### MB Beaver Builder Integration

#### Fixed

- Fix toggle rules for settings fields

### MB Custom Post Type

**Fixed**

- Fix warning for ‘supports’ parameter in WordPress 5.3.

## 1.10.8 - 2019-11-24

### MB Builder

#### Fixed

- Fix compatibility with WP < 5
- Fix performance issue when editing a field group
- Fix image sizes not showing
- Fix switching tab in a field affects other fields

#### Changed

- Change style for field tab nav

## 1.10.7 - 2019-11-14

### MB Blocks

#### Fixed

- Fix JavaScript error that causes blocks not available in the back end in WordPress 5.3.
- Fix title changes to "Auto Draft" when changing field values.
- Fix style for group title & button.

## 1.10.6 - 2019-11-07

### MB Relationships

#### Fixed

- Fix creating table error due to empty collate.

### MB Rest API

#### Fixed

- Fix term meta not available.

## 1.10.5 - 2019-11-01

### MB Group

#### Fixed

- Fix styling for term, user & settings page.

### MB Builder

#### Fixed

- Fix not able to deselect default dropdown value

#### Changed

- Set default field type = select advanced for taxonomy fields
- Remove clone option for taxonomy field

## 1.10.4 - 2019-10-18

### MB Frontend Submission

#### Changed

- Do not render the shortcode in the admin
- Do not display empty div if confirmation is empty
- Initialize session only on the front end

## 1.10.3 - 2019-10-18

### MB Blocks

#### Fixed

- Fix block over certain content length crashes

### MB Term Meta

#### Fixed

- Fix data not saving in term meta table

### MB Group

#### Fixed

- Fix `std` for child fields not working on the front end (with [MB Frontend Submission](https://metabox.io/plugins/mb-frontend-submission/))

## 1.10.2 - 2019-10-11

### MB Settings Page

#### Added

- Add support for Customizer
- Add support for network settings page

## 1.10.1 - 2019-10-09

### MB Blocks

#### Fixed

- Fix empty form when editing blocks.

### MB Settings Page

#### Added

- Add new `class` parameter for settings page wrapper.

#### Changed

- Use namespace.

### MB Group

### 1.3.5 - 2019-09-30

#### Fixed

- Fix file uploads (`file`, `image`) not working in groups.

### MB Yoast SEO Integration

#### Fixed

- Fix fatal error when enqueuing scripts on the front end when use with MB Frontend Submission or MB User Profile.

## 1.10.0 - 2019-09-18

### Added

- Make a completely new settings page, change menu name to "Extensions" instead of "All-In-One"

### Fixed

- Do not initialize session for extensions.

### MB Blocks 1.0.7

#### Fixed

- Fix custom assets are enqueued on all admin pages

### MB Custom Post Type 1.9.1

#### Fixed

- Fix menu icon not working

### MB Relationships 1.7.0

#### Added

- Add a separate `field` array for field settings
- Add `order_from` and `order_to` to API::add method

### MB User Profile 1.4.2

#### Changed

- Do not display empty div if confirmation is empty
- Do not render the shortcode in the admin. Fix inserting shortcode in the block editor.
- Initialize session only on the front end.

#### Fixed

- Fix visibility of user properties, for using in hooks

### MB Tooltip 1.1.2

#### Fixed

- Fix CSS issue when label is short and tooltip is long.

#### Changed

- Use tippy + popper JS for tooltips.

## 1.9.5 - 2019-08-30

### MB User Profile 1.4.1

#### Fixed

- Fix broken form due to missing code

## 1.9.4 - 2019-08-29

### MB Builder

- Don't escape result from Twig template code
- Downgrade Twig to 1.33.2 to compatible with WPML & 2FAS Light plugins
- Move `$prefix` out of the field IDs for easier update
- Add missing text domain for label description
- Hide options for post types that support Gutenberg

### MB Blocks

- Fix some fields (color, reorder images, etc.) not updating in real-time. Requires Meta Box 5.1.2+
- Fix CSS for wysiwyg field
- Fix CSS for color picker in the content area
- Allow to return value in `mb_the_block_field`

### MB Beaver Builder Integration

- Hide image size and date format when not needed

### MB Frontend Submission

- Improve security by hiding form settings on the front end
- Fix headers sent error in cron

### MB User Profile

#### Added

- Add mismatch rule for password fields
- Register form: show message when user is logged in
- Re-add `user_id` parameter to the profile form

#### Changed

- Hide login and register forms after submitted successfully
- Improve security by hiding form settings from the front end

### MB Custom Post Type

#### Added

- Add support for custom archive slug

#### Fixed

- Fix style in dashboard
- Do not show upgrade message for premium users

### MB Custom Taxonomy

#### Fixed

- Do not show upgrade message for premium users

## 1.9.3 - 2019-08-13

### MB Blocks

- Fix not saving fields that contains "-" in IDs

### MB Frontend Submission

- Fix saving post thumbnail
- Fix JavaScript not working

### MB Builder

- Add prefix settings, allowing to prefix all field IDs
- Add text domain settings (for exporting code only)
- Add readonly and disable settings for text fields
- Add `edited_user_role` & `edited_user_id` in Advanced location rules (requires [MB Include Exclude](https://metabox.io/plugins/meta-box-include-exclude/))
- Insert duplicated field right after the current field
- Allow to duplicate a whole group
- Fix not saving custom attribute `save_field`
- Fix not parsing options for autocomplete field
- Fix pressing Enter on inputs in custom attributes still submit the form
- Fix parsing email in dot notation
- Fix missing `key_value` field
- Fix not saving when an input contains slashes
- Fix error on nginx with SSI enabled

### MB Conditional Logic

- Fix conditional logic fails on switch

## 1.9.2 - 2019-08-05

### MB Builder

- Add support for building Gutenberg blocks visually. Also support Twig template engine for writing block template.
- Hide custom table settings when Show for = settings\_page
- Don't submit on enter on inputs
- Enable submit buttons only when meta is loaded
- Update Brazil translation

### MB Blocks

- Add missing `$post_id` parameter for the render callback.

### MB Elementor Integration

- Fix settings fields are not outputted.

## 1.9.1 - 2019-07-31

### Fixed

- Fixed when the Updater extension was enabled before (and it's removed from the current version since it's included in the Meta Box already).

## 1.9.0 - 2019-07-31

### Added

- Added MB Blocks extension to help you create custom Gutenberg blocks.

## 1.8.2 - 2019-07-17

### MB Custom Post Type

- Hide the meta box for taxonomy if set `meta_box_cb` = false in Gutenberg.

### MB Relationships

- Make `hidden` param work for `to` side
- Remove duplicate from query

### MB Elementor Integration

- Remove empty lists.
- Returns full image size for background settings.

## 1.8.1 - 2019-07-06

### MB Builder

- Fix color picker field JavaScript error.
- Fix tab label not updating in real-time.
- Fix `array_walk` error when upgrade data for posts.
- Fix duplicating field not working.
- Remove old `pages` param from the exported code.
- Upgrade old `context` param in the database.

### MB Frontend Submission

- Fix error when submitting posts with featured thumbnail.

## 1.8.0 - 2019-07-01

### MB Builder

- The plugin is rewritten completely with PHP namespace (using Composer) and requires PHP >= 5.6
- Make all text translatable
- Restructure the Settings tab, moving Exclude Include / Show Hide / Conditional Logic into one section "Location"
- Hide a lot of settings if some extensions required are not available (or enabled in the AIO)
- Update the rules for Location based on whenever Guteberg is active
- Remove the Import menu as it's kind of a solo item in the menu. It's now next to the Add New in the All Field Groups screen.
- Move Delete/Duplicate field button into the title bar
- Add a new column for front-end shortcode (if Frontend Submission extension is active)
- Fix some issues with drag and drop

### MB Group

- Fix JavaScript bug in group title if no settings for group title.

### MB Custom Post Type

- Fix quote in plural/singular names

### MB Conditional Logic

- Allow conditions to work for elements outside cloneable groups.

### MB Frontend Submission

- Fix edit=true not working.
- Use PHP namespace! No more long class names!

### MB User Meta

- Fix file/image fields not working.

### Changed

- Always enable the updater
- User Composer

## 1.7.6 - 2019-05-15

### MB Builder

- Update clipboard to not conflict with built-in version in WordPress 5.2. [See report](https://metabox.io/support/topic/javascript-error-disable-switching-the-editor-tabs/).
- Show group title in the field top bar when group label is not set.
- Fix max\_status = false default not working.
- Fixed bulk removed field groups.
- Added columns to Builder.

### MB Frontend Submission

- Don't disable submit button when validation is active.
- Hide form after submission.

## 1.7.5 - 2019-04-25

### MB Admin Columns

- Fixed searching not showing matched posts by title. And search query is showing empty string.

### MB Frontend Submission

- Prevent form submit duplication when users click submit button multiple times.

### MB User Profile

- Added parameter `password_strength` to indicate the minimum required password strength (very-weak, weak, medium, strong)
- Removed `user_id` parameter as the plugin always perform on current user. This also fixed a security issue.

### MB Builder

- Fixed when adding a link with target="\_blank" in a field description breaks field groups from saving in WordPress 5.1.
- Improved the code for import export page. Do not save the export file in the upload folder anymore.

### MB Group

- Fixed `default_state=collapsed` not working if `save_state=false` for cloneable groups.

## 1.7.4 - 2019-04-02

### MB Admin Columns

- Do not alter queries when searching and sorting

### MB Custom Post Type

- Set `map_meta_cap` to `true` for custom capability type, allowing admins can edit new post types.

### MB Custom Table

- Fixed not working for users.

### MB Elementor Integration

- Fixed output for `taxonomy` field.

### MB Frontend Submission

- Added `allow_delete` to let users delete their posts.

### MB REST API

- Fixed not working for users.

### MB Term Meta, MB User Meta, MB Columns, MB Tabs

- Improved styling.

## 1.7.3 - 2019-03-12

### MB Custom Post Type

- Enabled REST API by default for post types to make they work with Gutenberg.
- Added “custom” for capability type, allowing developers to set custom capabilities.
- Auto truncated post type slug to 20 characters.

### MB Elementor Integration

- Rewrote the plugin to better showing field list in post, archive and site groups.
- Removed the fields of internal post types used by MB Custom Post Type extension.

### MB - Relationship

- Added support for querying by multiple relationships. See [docs](https://docs.metabox.io/extensions/mb-relationships/) for details.

### MB User Profile

- Added password meter for registration and user edit forms. Note: do not change ID for password fields.

### MB Conditional Logic & MB Show Hide

- Added full support for Gutenberg. All conditions now work with Gutenberg.

## 1.7.2 - 2019-02-15

### Fixed

- Fixed styling for tabs, columns, image upload field in term/user/settings pages.
- Fixed responsiveness for columns on mobiles.
- Fixed user profile form not working if user enter password containing quotes.
- Fixed unindexed notice for Beaver Themer integration if MB User Profile is active.
- Fixed `std` not working for child fields.

### Added

- Added new Location column in the All Field Groups screen (MB Builder) to show the location of the meta box.
- Added a new parameter `bind_if_empty` which accepts `true` (default) or `false` for fields so that their value can be (or not be) updated if Google doesn't return any data. See [forum topic](https://metabox.io/support/topic/only-auto-populate-neighborhood-field-if-not-empty/).

### Changed

- Updated UI for tab settings in MB Builder

## 1.7.1 - 2019-01-30

### Fixed

- Fixed loading issue with MB User Profile that missed the default fields (user email and password).

## 1.7.0 - 2019-01-21

### Added

- Added all free extensions from .org. So you no longer need to install them separately. Just activate them in the _Meta Box → All-In-One_ settings.

### Updated

- Columns: now columns are responsive and work well with user profile, term edit and settings page.
- Tabs: tabs now work well with user profile, term edit and settings page.
- Builder:
    - Added new Location column in the All Field Groups screen to show the location of the meta box.
    - Updated UI for tab settings.
- Group:
    - Fixed not saving data of `file` in sub-groups.
    - Fixed `std` not working for group. @props Razib.
    - Fixed conditional logic for elements inside a collapsible sub-groups.

1.6.18
------

- Updated MB Frontend Submission, allowing users to edit the post after submitting.
- Fixed filter mb\_aio\_extensions not working if no extensions is enabled.
- Fixed dashboard settings not saving correctly.

1.6.17
------

- Updated MB Admin Columns to support search and sort for custom table.

1.6.16
------

- Updated Conditional Logic, Show Hide and User Profile

1.6.15
------

- Updated [MB Builder](https://metabox.io/plugins/meta-box-builder/)

1.6.14
------

- Updated [MB Conditional Logic](https://metabox.io/plugins/meta-box-conditional-logic/), [MB Builder](https://metabox.io/plugins/meta-box-builder/)

1.6.13
------

- Updated [MB Conditional Logic](https://metabox.io/plugins/meta-box-conditional-logic/)

1.6.12
------

- Updated [MB Custom Table](https://metabox.io/plugins/mb-custom-table/), [MB Conditional Logic](https://metabox.io/plugins/meta-box-conditional-logic/), [MB Group](https://metabox.io/plugins/meta-box-group/)

1.6.11
------

- Updated [MB Builder](https://metabox.io/plugins/meta-box-builder/), [MB Conditional Logic](https://metabox.io/plugins/meta-box-conditional-logic/)

1.6.10
------

- Updated [MB Revision](https://metabox.io/plugins/mb-revision/), [MB Settings Page](https://metabox.io/plugins/mb-settings-page/), [MB User Profile](https://metabox.io/plugins/mb-user-profile/).

1.6.9
-----

- Updated [MB Revision](https://metabox.io/plugins/mb-revision/) and [MB Custom Table](https://metabox.io/plugins/mb-custom-table/), making them work together.

1.6.8
-----

- Updated [MB Tabs](https://metabox.io/plugins/meta-box-tabs/)

1.6.7
-----

- Updated [MB Builder](https://metabox.io/plugins/meta-box-builder/)

1.6.6
-----

- Updated [MB Builder](https://metabox.io/plugins/meta-box-builder/)

1.6.5
-----

- Updated [MB Revision](https://metabox.io/plugins/mb-revision/)

1.6.4
-----

- Updated [MB Geolocation](https://metabox.io/plugins/meta-box-geolocation/)
- Updated [MB Template](https://metabox.io/plugins/meta-box-template/)

1.6.3
-----

- Updated [MB Builder](https://metabox.io/plugins/meta-box-builder/)
- Updated [MB Conditional Logic](https://metabox.io/plugins/meta-box-conditional-logic/)

1.6.2
-----

- Updated [MB Revision](https://metabox.io/plugins/mb-revision/)

1.6.1
-----

- Updated [MB Revision](https://metabox.io/plugins/mb-revision/)

1.6.0
-----

- Added a dashboard widget
- Refine the settings page, allowing users to filter extensions by tags

Version 1.5.17
--------------

- Updated [MB User Profile](https://metabox.io/plugins/mb-user-profile/)
- Updated [MB Frontend Submission](https://metabox.io/plugins/mb-frontend-submission/)

Version 1.5.16
--------------

- Updated [MB Builder](https://metabox.io/plugins/meta-box-builder/)
- Updated [MB Term Meta](https://metabox.io/plugins/mb-term-meta/)

Version 1.5.15
--------------

- Updated [MB User Profile](https://metabox.io/plugins/mb-user-profile/)

Version 1.5.14
--------------

- Updated [MB Builder](https://metabox.io/plugins/meta-box-builder/)

Version 1.5.13
--------------

- Updated [MB Geolocation](https://metabox.io/plugins/meta-box-geolocation/)
- Updated [MB Tabs](https://metabox.io/plugins/meta-box-tabs/)
- Updated [MB Frontend Submission](https://metabox.io/plugins/mb-frontend-submission/)

Version 1.5.12
--------------

- Updated [MB Conditional Logic](https://metabox.io/plugins/meta-box-conditional-logic/)
- Update [MB Group](https://metabox.io/plugins/meta-box-group/)

Version 1.5.11
--------------

- Updated [MB Tabs](https://metabox.io/plugins/meta-box-tabs/)

Version 1.5.10
--------------

- Updated [MB Include Exclude](https://metabox.io/plugins/meta-box-include-exclude/), [MB Frontend Submission](https://metabox.io/plugins/mb-frontend-submission/) & [MB Builder](https://metabox.io/plugins/meta-box-builder/)

Version 1.5.9
-------------

- Updated [MB Builder](https://metabox.io/plugins/meta-box-builder/)

Version 1.5.8
-------------

- Updated [MB Builder](https://metabox.io/plugins/meta-box-builder/)