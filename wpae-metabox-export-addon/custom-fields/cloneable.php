<?php

namespace Wpae\Metabox;

use Wpae\AddonAPI\NotAString;
use Wpae\AddonAPI\PMXE_Addon_Field;
use function Wpae\AddonAPI\normalizeElementName;

class PMME_Cloneable_Field extends \Wpae\AddonAPI\PMXE_Addon_Field {

    use NotAString;

    public function realField($index) {
        return PMXE_Addon_Field::from(
            $this->data,
            $this->resolver,
            $this->settings,
            $this->elName,
            $this->elNameNs,
            $this->phpFunction,
            $this,
            $index,
            false
        );
    }

    public function exportXml($article, $rows, $xmlWriter) {
        $xmlWriter->beginElement($this->elNameNs, $this->elName, null);

        foreach($rows as $index => $row) {
            $field = $this->realField($index);
	        // Set local value for real field.
	        $field->local_value = $row;
            $field->exportXml($article, $row, $xmlWriter);
        }

        $xmlWriter->closeElement();

        return $article;
    }

	public function exportCustomXml( $article, $rows, $write = true ) {
		$row_values = [];

		foreach ( $rows as $index => $row ) {
			$field        = $this->realField( $index );
			$row_values[] = $field->exportCustomXml( $article, $row, false );

		}

		wp_all_export_write_article( $article, $this->elName, $row_values );

		return $article;
	}

    public function exportCsv( $article, $rows, $preview = false ) {
        $new_row = [];

        foreach ($this->value as $index => $row) {
	        $field              = $this->realField( $index );
			// Set the local value for the real field.
	        $field->local_value = $row;
			// When toString is called it doesn't use the $row value.
	        // Setting the local value above ensures it has the correct value.
	        $row_article        = $field->exportCsv( [], $row );

	        if ( count( $field->subfields ) > 0 ) {
		        foreach ( $field->subfields as $subfield ) {
			        $key               = $this->elName . '_' . $subfield['key'];
			        $new_row[ $key ][] = $row_article[ $key ];

		        }
	        }else{
				$new_row[ $this->elName ][] = $row_article[ $this->elName ];
	        }
        }

        foreach ($new_row as $key => $value) {
            wp_all_export_write_article(
                $article,
                $key,
                implode(\XmlExportEngine::$implode, $value)
            );
        }

        return $article;
    }

    public function getHeaders() {
        return $this->realField(0)->getHeaders();
    }
}
