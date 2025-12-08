<?php $cloneable = $field['args']['is_cloneable']; ?>
<div class="pmmi-group pmmi-group--inline">
    <?php foreach ($field['choices'] as $choice) { ?>
        <div class="pmmi-group-subfield">
            <?php
            $subfield = new \Wpai\AddonAPI\PMXI_Addon_Text_Field(
                [
                    'type' => 'text',
                    'label' => $choice['label'],
                    'key' => $choice['value'],
                ],
                $field_class->view,
                $field_class
            );

            if ($cloneable) {
                $subfield->setRowIndex($row_index);
            }

            $subfield->show();
            ?>
        </div>
    <?php } ?>
</div>