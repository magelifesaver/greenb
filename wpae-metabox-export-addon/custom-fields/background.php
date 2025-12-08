<?php

namespace Wpae\Metabox;

use Wpae\AddonAPI\NotAString;
use function Wpae\AddonAPI\normalizeElementName;

class PMME_Background_Field extends \Wpae\AddonAPI\PMXE_Addon_Field {

    use NotAString;

    public function exportXml($article, $rows, $xmlWriter) {
        $elName = $this->parent ? 'row' : normalizeElementName($this->elName);
        $xmlWriter->beginElement($this->elNameNs, $elName, null);

        // Ensure we have values to process.
        if (is_array($rows)) {
            foreach ($rows as $key => $value) {
                // uppercase first letter of key
                $element_name = normalizeElementName(ucfirst($key));
                $elementOpenResponse = $xmlWriter->startElement($element_name);

                if ($elementOpenResponse) {
                    $xmlWriter->writeData($value, $elName);
                    $xmlWriter->closeElement();
                }
            }
        }

        $xmlWriter->closeElement();

        return $article;
    }

    public function exportCsv( $article, $value, $preview = false ) {
        // Ensure we have values to write.
        if (is_array($value)) {
            foreach ($value as $key => $item) {
                $element_name = $this->elName . '_' . ucfirst($key);
                wp_all_export_write_article($article, $element_name, $item);
            }
        }

        return $article;
    }

    /*
     * Add subfields as headers to the export file.
     */
    public function getHeaders() {
        return [
            "-{$this->elName}",
            $this->elName . '_Color',
            $this->elName . '_Image',
            $this->elName . '_Repeat',
            $this->elName . '_Position',
            $this->elName . '_Attachment',
            $this->elName . '_Size',
        ];
    }
}
