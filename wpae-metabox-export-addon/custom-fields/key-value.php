<?php

namespace Wpae\Metabox;

use Wpae\AddonAPI\NotAString;
use function Wpae\AddonAPI\normalizeElementName;


class PMME_Key_Value_Field extends \Wpae\AddonAPI\PMXE_Addon_Field {

	use NotAString;

	public function exportXml( $article, $rows, $xmlWriter ) {

		$elName = normalizeElementName( $this->elName );
		$xmlWriter->beginElement( $this->elNameNs, $elName, null );

		foreach ( $rows as $index => $row ) {

			$rowElName = 'row';
			$xmlWriter->startElement( $rowElName );
			foreach ( $this->subfields as $sub_index => $subfield ) {
				$value               = $row[ $sub_index ] ?? '';
				$element_name        = normalizeElementName( $subfield['key'] );
				$elementOpenResponse = $xmlWriter->startElement( $element_name );

				if ( $elementOpenResponse ) {
					$xmlWriter->writeData( $value, $elName );
					$xmlWriter->closeElement();
				}
			}
			$xmlWriter->closeElement();
		}

		$xmlWriter->closeElement();

		return $article;
	}

	public function exportCustomXml( $article, $rows, $write = true ) {
		$subfield_list = [];

		foreach ( $rows as $index => $row ) {
			foreach ( $this->subfields as $sub_index => $subfield ) {
				$value        = $this->runPhpFunction( $row[ $sub_index ] );
				$element_name = $this->getSubfieldKey( $subfield );

				$subfield_list[ $index ][ $element_name ] = $value;
			}
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
		$items = [];

		foreach( $this->value as $index => $row ) {
			foreach ( $this->subfields as $sub_index => $subfield ) {
				$element_name = $this->getSubfieldKey( $subfield );
				$items[$element_name][]         = $this->runPhpFunction( $row[ $sub_index ] ?? '' );

			}
		}
		foreach($items as $el_name => $item ) {
			wp_all_export_write_article( $article, $el_name, implode(\XmlExportEngine::$implode, $item) );
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
