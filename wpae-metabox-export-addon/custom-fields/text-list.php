<?php

namespace Wpae\Metabox;

use Wpae\AddonAPI\NotAString;
use function Wpae\AddonAPI\normalizeElementName;

class PMME_Text_List_Field extends \Wpae\AddonAPI\PMXE_Addon_Field {

    use NotAString;

    public function exportXml($article, $rows, $xmlWriter) {
        $elName = $this->parent ? 'row' : normalizeElementName($this->elName);
        $xmlWriter->beginElement($this->elNameNs, $elName, null);

        foreach($this->subfields as $index => $subfield) {
            $value = $rows[$index] ?? '';
            $element_name = normalizeElementName($subfield['key']);
            $elementOpenResponse = $xmlWriter->startElement($element_name);

            if ($elementOpenResponse) {
                $xmlWriter->writeData($value, $elName);
                $xmlWriter->closeElement();
            }
        }

        $xmlWriter->closeElement();

        return $article;
    }

	public function exportCustomXml( $article, $rows, $write = true ) {
		$subfield_list = [];

		// Add each subfield into our subfield list.
		foreach ( $this->subfields as $index => $subfield ) {
			$element_name = $this->getSubfieldKey( $subfield );
			$value        = $this->runPhpFunction( $rows[ $index ] ?? '' );
			// Named keys of the style 'parent name_subfield' are subfields.
			// All other arrays are treated as duplicate parents and presented in separate elements.
			$subfield_list[ $element_name ] = $value;
		}

		// By default we write the values to $article and return it.
		// But if !$write we return the list of subfields we built instead.
		if ( $write ) {
			wp_all_export_write_article( $article, $this->elName, $subfield_list );
			return $article;
		} else {
			return $subfield_list;
		}
	}

    public function exportCsv( $article, $rows, $preview = false ) {
        foreach($this->subfields as $index => $subfield) {
            $element_name = $this->getSubfieldKey($subfield);
            $item = $this->runPhpFunction($rows[$index] ?? '');
            wp_all_export_write_article($article, $element_name, $item);
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

        foreach($this->subfields as $subfield) {
            $headers[] = $this->getSubfieldKey($subfield);
        }

        return $headers;
    }
}
