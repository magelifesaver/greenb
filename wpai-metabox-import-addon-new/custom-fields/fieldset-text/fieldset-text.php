<?php

namespace Wpai\Metabox;

class PMMI_Fieldset_Text_Field extends \Wpai\AddonAPI\PMXI_Addon_Field {

    public static $locate_template_in = __DIR__;

    public function beforeImport($postId, $value, $data, $logger, $rawData) {
        return $value;
    }

}
