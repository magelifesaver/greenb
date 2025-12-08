<?php

namespace Wpae\Metabox;

use Wpae\AddonAPI\NotAString;
use \Wpae\AddonAPI\PMXE_Addon_Field;
use function Wpae\AddonAPI\normalizeElementName;

class PMME_Group_Field extends \Wpae\AddonAPI\PMXE_Addon_Field {

    use NotAString;

    public function getSubField($subfield) {
        return PMXE_Addon_Field::from(
            $subfield,
            $this->resolver,
            $this->settings,
            $this->getSubfieldKey($subfield),
            $this->elNameNs,
            $this->phpFunction,
            $this,
            $subfield['key']
        );
    }

    public function exportXml($article, $value, $xmlWriter) {
        $elName = $this->parent ? 'row' : normalizeElementName($this->elName);
        $xmlWriter->beginElement($this->elNameNs, $elName, null);

        foreach($this->subfields as $subfield) {
            $subfield_instance = $this->getSubField($subfield);
            if (!$subfield_instance) continue;
            $subfield_instance->exportXml($article, $value[$subfield['key']] ?? '', $xmlWriter, $subfield_instance->elName);
        }

        $xmlWriter->closeElement();

        return $article;
    }

	public function exportCustomXml( $article, $value, $write = true ) {
		$row_values = [];

		foreach ( $this->subfields as $subfield ) {

			$subfield_instance = $this->getSubField($subfield);

			if (!$subfield_instance) continue;
			$row_values[$subfield_instance->elName] = $subfield_instance->exportCustomXml( $article, $value[$subfield['key']] ?? '', false );

		}

		// Remove the parent element name from the subfield's subfields.
		// This ensures that you get a value like 'height' for the nested fields
		// instead of 'parent_field_key_asdlfkj234_height'.
		$row_values = $this->clean_nested_array_keys($row_values, $this->elName);

		wp_all_export_write_article( $article, $this->elName, $row_values );

		return $article;
	}

	public function exportCsv( $article, $rows, $preview = false ) {
		foreach ( $this->subfields as $subfield ) {
			$subfield_instance = $this->getSubField( $subfield );
			$article           = $subfield_instance->exportCSV( $article, $rows[ $subfield['key'] ] ?? '', $preview );
		}

		return $article;
	}

    /*
     * Add subfields as headers to the export file.
     */
	public function getHeaders() {
		$headers = [
			"-{$this->elName}",
		];

		foreach ( $this->subfields as $subfield ) {
			$subfield_instance = $this->getSubField( $subfield );
			$subheaders        = $subfield_instance->getHeaders();
			foreach ( $subheaders as $subheader ) {
				if ( strpos( $subheader, '-' ) !== 0 ) {
					$headers[] = $subheader;
				}
			}
		}

		return $headers;
	}

	public function clean_nested_array_keys( $input_array, $replacement_value ) {
		$final_array = [];
		foreach ( $input_array as $key => $value ) {
			if ( is_array( $value ) ) {
				$sub_array = $this->clean_nested_array_keys( $value, $replacement_value );
				foreach ( $sub_array as $sub_key => $sub_value ) {
					$remaining_key = str_replace( $key, '', $sub_key );
					$final_array[ $key ][ $replacement_value . $remaining_key ] = $sub_value;
				}
			} else {
				$final_array[ $key ] = $value;
			}
		}

		return $final_array;
	}

}
