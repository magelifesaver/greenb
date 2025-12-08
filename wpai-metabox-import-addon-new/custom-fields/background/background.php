<?php

namespace Wpai\Metabox;

class PMMI_Background_Field extends \Wpai\AddonAPI\PMXI_Addon_Field {

    public static $locate_template_in = __DIR__;

    public function beforeImport($postId, $value, $data, $logger, $rawData) {
        return [
            'color' => $value['color'] ?? '',
            'image' => $value['image'] ?? '',
            'repeat' => $value['repeat'] ?? '',
            'position' => $value['position'] ?? '',
            'attachment' => $value['attachment'] ?? '',
            'size' => $value['size'] ?? '',
        ];
    }
}
