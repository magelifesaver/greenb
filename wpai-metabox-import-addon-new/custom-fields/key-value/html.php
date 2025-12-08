<?php $cloneable = $field['args']['is_cloneable']; ?>
<div class="pmmi-group pmmi-group--inline">
    <div class="pmmi-group-subfield">
        <?php
        $subfield_key = new \Wpai\AddonAPI\PMXI_Addon_Text_Field(
            [
                'type' => 'text',
                'label' => $field['placeholder']['key'],
                'key' => 'key',
            ],
            $field_class->view,
            $field_class
        );

        if ($cloneable) {
            $subfield_key->setRowIndex($row_index);
        }

        $subfield_key->show();
        ?>
    </div>

    <div class="pmmi-group-subfield">
        <?php
        $subfield_value = new \Wpai\AddonAPI\PMXI_Addon_Text_Field(
            [
                'type' => 'text',
                'label' => $field['placeholder']['value'],
                'key' => 'value',
            ],
            $field_class->view,
            $field_class
        );

        if ($cloneable) {
            $subfield_value->setRowIndex($row_index);
        }

        $subfield_value->show();
        ?>
    </div>
</div>