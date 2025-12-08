<?php

namespace Wpai\Metabox;

class PMMI_Group_Field extends \Wpai\AddonAPI\PMXI_Addon_Field {

    public static $locate_template_in = __DIR__;

    public function beginHtml() {
        \Wpai\AddonAPI\view(
            'separator',
            $this->params()
        );
    }
    public function endHtml() {
        \Wpai\AddonAPI\view(
            'separator-end',
            $this->params(),
        );
    }
}
