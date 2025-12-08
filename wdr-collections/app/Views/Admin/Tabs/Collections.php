<?php defined('ABSPATH') or exit ?>

<br>
<div id="wpbody-content" class="awdr-container" style="">
    <?php if ($is_pro) { ?>
        <div class="col-md-6 col-lg-6 text-left">
            <h1 class="wp-heading-inline"><?php _e('Collections', 'wdr-collections'); ?></h1>
            <a href="<?php echo esc_url(admin_url("admin.php?" . http_build_query(array('page' => WDR_SLUG, 'tab' => 'collections', 'task' => 'create')))); ?>"
               class="btn btn-primary"><?php _e('Add New Collection', 'wdr-collections'); ?></a>
            <a href="https://docs.flycart.org/en/articles/6465907-collections?utm_source=wdr-collections&utm_campaign=doc&utm_medium=text-click&utm_content=documentation"
               target="_blank"
               class="btn btn-info text-right"
               style="float: right"><?php _e('Documentation', 'wdr-collections'); ?></a>
            <br><br>
            <input type="hidden" id="awdr_get_collection_linked_rules_nonce" value="<?php echo esc_attr(\Wdr\App\Helpers\Helper::create_nonce('wdr_ajax_get_collection_linked_rules')); ?>">
            <table class="wp-list-table widefat fixed posts">
                <thead>
                <tr>
                    <th scope="col" id="title" class="manage-column column-title column-primary sortable desc">
                        <a href="javascript:void(0);">
                            <span><?php _e('Title', 'wdr-collections'); ?></span>
                            <span class="sorting-indicator"></span>
                        </a>
                    </th>
                    <!-- <th scope="col" id="author" class="manage-column column-author">--><?php //_e('Collection Type', 'wdr-collections'); ?><!--</th>-->
                    <th scope="col" id="author" class="manage-column column-author"><?php _e('Created', 'wdr-collections'); ?></th>
                    <th scope="col" id="author" class="manage-column column-author"><?php _e('Modified', 'wdr-collections'); ?></th>
                    <th scope="col" id="title" class="manage-column column-title"><?php _e('Action', 'wdr-collections'); ?></th>
                </tr>
                </thead>
                <tbody>
                    <?php if ($collections) { ?>
                       <?php foreach ($collections as $collection) { ?>
                            <tr id="<?php echo esc_attr($collection->getId()); ?>" class="awdr-listing-collection-tr">
                                <td class="title column-title has-row-actions column-primary page-title" data-colname="Title">
                                    <strong>
                                        <a class="row-title"
                                           href="<?php echo esc_url(admin_url("admin.php?" . http_build_query(array('page' => WDR_SLUG, 'tab' => 'collections', 'task' => 'view', 'id' => $collection->getId())))); ?>"
                                           aria-label="“<?php echo esc_attr($collection->getTitle()); ?>” (Edit)">
                                            <?php echo esc_html($collection->getTitle()); ?>
                                        </a>
                                    </strong>
                                </td>
                                <!--<td class="author column-author" data-colname="Author">--><?php //echo ucfirst(esc_html($collection->getType())); ?><!--</td>-->
                                <td>
                                    <div class="awdr_created_date_html">
                                        <?php
                                        $created_by = $collection->getCollectionCreatedBy();
                                        if ($created_by) {
                                            if (function_exists('get_userdata')) {
                                                if ($user = get_userdata($created_by)) {
                                                    if (isset($user->data->display_name)) {
                                                        $created_by = $user->data->display_name;
                                                    }
                                                }
                                            }
                                        }
                                        $created_on = $collection->getCollectionCreatedOn();
                                        if (!empty($created_by) && !empty($created_on)) { ?>
                                            <span class="wdr_desc_text"><?php _e('By: ' . $created_by . '', 'wdr-collections'); ?>
                                            ,<?php _e(' On: ' . $created_on, 'wdr-collections'); ?> &nbsp;</span>
                                        <?php } ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="awdr_created_date_html">
                                        <?php
                                        $modified_by = $collection->getCollectionModifiedBy();
                                        if ($modified_by) {
                                            if (function_exists('get_userdata')) {
                                                if ($user = get_userdata($modified_by)) {
                                                    if (isset($user->data->display_name)) {
                                                        $modified_by = $user->data->display_name;
                                                    }
                                                }
                                            }
                                        }
                                        $modified_on = $collection->getCollectionModifiedOn();
                                        if (!empty($modified_by) && !empty($modified_on)) { ?>
                                            <span class="wdr_desc_text"><?php _e('By: ' . $modified_by . '', 'wdr-collections'); ?>
                                            ,<?php _e(' On: ' . $modified_on, 'wdr-collections'); ?> </span>
                                        <?php } ?>
                                    </div>
                                </td>
                                <td class="awdr-rule-buttons">
                                    <a class="btn btn-primary"
                                       href="<?php echo esc_url(admin_url("admin.php?" . http_build_query(array('page' => WDR_SLUG, 'tab' => 'collections', 'task' => 'view', 'id' => $collection->getId())))); ?>">
                                        <?php _e('Edit', 'wdr-collections'); ?></a>
                                    <a class="btn btn-danger wdr_delete_collection"
                                       data-delete-collection="<?php echo esc_attr($collection->getId()); ?>"
                                       data-awdr_nonce="<?php echo esc_attr(\Wdr\App\Helpers\Helper::create_nonce('wdr_ajax_delete_collection' . $collection->getId())); ?>">
                                        <?php _e('Delete', 'wdr-collections'); ?></a>
                                </td>
                            </tr>
                        <?php } ?>
                    <?php } else {
                        ?>
                        <tr class="no-items">
                            <td class="colspanchange" colspan="2"><?php _e('No collections found.', 'wdr-collections'); ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    <?php } else { ?>
       <p> <?php  _e("Unlock this feature by <a href='https://www.flycart.org/products/wordpress/woocommerce-discount-rules?utm_source=woo-discount-rules-v2&utm_campaign=doc&utm_medium=text-click&utm_content=unlock_pro' target='_blank'>Upgrading to Pro</a>", 'wdr-collections'); ?></p>
    <?php } ?>
</div>

