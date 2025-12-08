<?php

namespace Wpai\JetEngine;

class PMMI_Map_Field extends \Wpai\AddonAPI\PMXI_Addon_Map_Field {

    public function getApiKey( $use_custom = false, $custom_key = null ) {
        $default_key = parent::getApiKey( $use_custom, $custom_key );

        if ( $use_custom || ! empty( $default_key ) ) {
            return $default_key;
        }

        return $this->args['map_api_key'] ?? null;
    }

    public function getRegion( $custom_region = null ) {
        return parent::getRegion( $custom_region ) ?: $this->args['map_region'];
    }

    public function getLanguage( $custom_language = null ) {
        return parent::getLanguage( $custom_language ) ?: $this->args['map_language'];
    }

    public function beforeImport( $postId, $value, $data, $logger, $rawData ) {
        $return_value = parent::beforeImport( $postId, $value, $data, $logger, $rawData );

        if ( ! $return_value ) {
            return $return_value;
        }

        return $this->formatValue( $return_value, 'string' );
    }
}