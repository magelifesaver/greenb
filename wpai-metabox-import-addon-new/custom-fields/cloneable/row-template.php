<?php
    /**
     * @var array $field
     * @var \Wpai\AddonAPI\PMXI_Addon_Field $field_class
     * @var int $row_index
     */
?>

<div class="pmmi-cloneable-row" data-test="repeater-row">
    <?php
    $should_self_reference = count($field['subfields']) === 1 && $field['subfields'][0]['key'] === $field['key'];
    $parent_class = $should_self_reference ? $field_class : null;
    \Wpai\AddonAPI\PMXI_Addon_Field::from(
        $field,
        $field_class->view,
        $parent_class,
        false,
    )->setRowIndex($row_index)->show();
    ?>

    <button class="pmmi-cloneable-remove-row dashicons dashicons-dismiss" type="button" arial-label="<?php echo _e('Remove', 'wp_all_import_metabox_add_on') ?>">
    </button>
</div>
