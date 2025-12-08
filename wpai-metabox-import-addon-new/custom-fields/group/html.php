<div class="pmmi-group">
    <?php foreach ($field['subfields'] as $subfield) { ?>
        <div class="pmmi-group-subfield" data-key="<?php echo $subfield['key']; ?>" data-type="<?php echo $subfield['type']; ?>">
            <?php
            \Wpai\AddonAPI\PMXI_Addon_Field::from(
                $subfield,
                $field_class->view,
                $field_class
            )->show();
            ?>
        </div>
    <?php } ?>
</div>