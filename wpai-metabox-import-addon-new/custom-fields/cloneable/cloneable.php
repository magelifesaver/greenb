<?php

namespace Wpai\Metabox;

class PMMI_Cloneable_Field extends \Wpai\AddonAPI\PMXI_Addon_Repeater_Field {

    public static $locate_template_in = __DIR__;

    public function beforeImport($postId, $value, $data, $logger, $rawData) {
        $value = parent::beforeImport($postId, $value, $data, $logger, $rawData);

        // If is single subfield and subfield is same as current field
        // TODO: This is a hack, we should find a better way to handle this and update it on row-template.php as well
        if ($this->subfields && count($this->subfields) === 1 && $this->subfields[0]['key'] === $this->key) {
            return array_map(function ($v) {
                return $v[$this->key];
            }, $value);
        }

        return $value;
    }

}
