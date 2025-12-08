<?php

namespace Wpae\Metabox;

use function Wpae\AddonAPI\normalizeElementName;

class PMME_Mb_Taxonomy extends \Wpae\AddonAPI\PMXE_Addon_Field {

	public function toString() {
		$delimiter = $this->getInvertedDelimiter();
		$terms  = [];
		$values = is_array( $this->value ) ? $this->value : [ $this->value ];

		foreach ( $values as $value ) {
			if ( isset( $value->name ) ) {
				$terms[] = $value->name;
			}
		}

		return implode( $delimiter, $terms );
	}

	public function exportCustomXml( $article, $value, $write = true ) {
		$formatted_values = [];

		foreach( $value as $term ){
			$formatted_values[$this->elName .'_'. $term->taxonomy][] = $term->name;
		}

		foreach( $formatted_values as $index => $taxonomy_terms ){
			if( is_array($taxonomy_terms) ){
				$formatted_values[$index] = implode($this->getInvertedDelimiter(), $taxonomy_terms);
			}
		}

		// By default we write the values to $article and return it.
		// But if !$write we return the list of subfields we built instead.
		if ( $write ) {
			wp_all_export_write_article( $article, $this->elName, $formatted_values );
			return $article;
		} else {
			return $formatted_values;
		}

	}

	public function exportXml($article, $value, $xmlWriter, $elementName = null) {
		$elName = $this->parent ? 'row' : normalizeElementName($this->elName);
		$elName = !empty($elementName) ? $elementName : $elName;
		$xmlWriter->beginElement($this->elNameNs, $elName, null);
		$formatted_values = [];

		foreach( $value as $term ){
			$formatted_values[$term->taxonomy][] = $term->name;
		}

		foreach( $formatted_values as $index => $taxonomy_terms ){
			if( is_array($taxonomy_terms) ){
				$taxonomy_terms = implode($this->getInvertedDelimiter(), $taxonomy_terms);
			}

			$element_name = normalizeElementName($index);
			$elementOpenResponse = $xmlWriter->startElement($element_name);

			if ($elementOpenResponse) {
				$xmlWriter->writeData($taxonomy_terms, $elName);
				$xmlWriter->closeElement();
			}
		}

		$xmlWriter->closeElement();

		return $article;
	}

	public function exportCsv( $article, $value, $preview = false ) {
		$formatted_values = [];

		$value = $this->runPhpFunction($value);

		foreach( $value as $term ){
			$formatted_values[$this->elName .'_'. $term->taxonomy][] = $term->name;
		}

		foreach( $formatted_values as $index => $taxonomy_terms ){
			if( is_array($taxonomy_terms) ){
				wp_all_export_write_article($article, $index, implode($this->getInvertedDelimiter(), $taxonomy_terms));
			}
		}

		return $article;
	}

	public function getHeaders() {
		$headers = [
			"-{$this->elName}",
		];

		foreach($this->target as $taxonomy) {
			if(!in_array($this->elName .'_'. $taxonomy, $headers)) {
				$headers[] = $this->elName .'_'. $taxonomy;
			}
		}

		return $headers;
	}

}
