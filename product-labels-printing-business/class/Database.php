<?php

namespace UkrSolution\ProductLabelsPrinting;

use UkrSolution\ProductLabelsPrinting\Helpers\Files;
use UkrSolution\ProductLabelsPrinting\Helpers\UserSettings;

require_once ABSPATH . 'wp-admin/includes/upgrade.php'; 

class Database
{

    public static $tablePaperFormats = "barcode_v3_paper_formats";
    public static $tableLabelSheets = "barcode_v3_label_sheets";
    public static $tableTemplates = "barcode_v3_templates";
    public static $tableTemplateToUser = "barcode_v3_template_to_user";
    public static $tableFieldsMatching = "barcode_v3_fields_matching";
    public static $tableUserSettings = "barcode_v3_user_settings";
    public static $tableProfiles = "barcode_v3_profiles";
    public static $tableShortcodes = "barcode_v3_shortcodes";
    public static $tableDimension = "barcode_v3_dimension";

    public static $optionSettingsOrdersKey = "a4barcode_settings_orders";
    public static $optionSettingsProductsKey = "a4barcode_settings_products";
    public static $optionSettingsCodePrefixKey = "wpbcu_barcode_generator_barcode_prefix";
    public static $optionSettingsCfPriorityKey = "wpbcu_barcode_generator_custom_fields_priority";
    public static $optionSettingsCurrencySymbolKey = "wpbcu_barcode_generator_currency_symbol";
    public static $optionSettingsLK = "wpbcu_barcode_generator_lk";
    public static $optionSettings = "barcode_print_settings";
    public static $optionPostImageSize = "barcode_print_post_image_size";


    public static function checkTables()
    {
        global $wpdb;

        try {
            $db = $wpdb->dbname;
            $key = "Tables_in_{$db}";
            $plTables = array();

            $plTables = array(
                $wpdb->prefix . self::$tableDimension,
                $wpdb->prefix . self::$tableFieldsMatching,
                $wpdb->prefix . self::$tableLabelSheets,
                $wpdb->prefix . self::$tablePaperFormats,
                $wpdb->prefix . self::$tableProfiles,
                $wpdb->prefix . self::$tableTemplateToUser,
                $wpdb->prefix . self::$tableTemplates,
                $wpdb->prefix . self::$tableUserSettings,
            );


            $result = $wpdb->get_results("SHOW TABLES");
            $tables = array();

            foreach ($result as $value) {
                $tables[] = $value->$key;
            }

            if (count(array_diff($plTables, $tables)) > 0) {
                self::createTables();
            }
        } catch (\Throwable $th) {
        }
    }


    public static function setupTables($network_wide)
    {
        global $wpdb;

        if (is_multisite() && $network_wide) {
            $blog_ids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
            foreach ($blog_ids as $blog_id) {
                switch_to_blog($blog_id);
                self::createTables();
                restore_current_blog();
            }
        } else {
            self::createTables();
        }

        self::createTables();

        UserSettings::migrateSettings();

    }

    public static function createTables()
    {
        self::setupFormatsTables();
        self::setupTemplatesTable();
        self::setDefaultValues();
    }

    protected static function setupFormatsTables()
    {
        global $wpdb;

        $tblPaperFormats = $wpdb->prefix . self::$tablePaperFormats;
        $sql = "CREATE TABLE `{$tblPaperFormats}` (
            `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `name` varchar(255) DEFAULT NULL,
            `width` decimal(12,4) DEFAULT '0.0000',
            `height` decimal(12,4) DEFAULT '0.0000',
            `default` tinyint(1) NOT NULL DEFAULT '0',
            `uol_id` int(11) DEFAULT 1 NULL,
            `landscape` tinyint(1) DEFAULT 0 NOT NULL,
            `is_roll` tinyint(1) DEFAULT 0 NOT NULL,
            `is_editable` tinyint(1) DEFAULT 1 NOT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
        dbDelta($sql);

        $tblSheetFormat = $wpdb->prefix . self::$tableLabelSheets;
        $sql = "CREATE TABLE `{$tblSheetFormat}` (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `userId` BIGINT(20) DEFAULT NULL,
            `name` varchar(255) DEFAULT NULL,
            `width` decimal(12,4) DEFAULT NULL,
            `height` decimal(12,4) DEFAULT NULL,
            `around` decimal(12,4) DEFAULT NULL,
            `across` decimal(12,4) DEFAULT NULL,
            `marginLeft` decimal(12,4) DEFAULT NULL,
            `marginRight` decimal(12,4) DEFAULT NULL,
            `marginTop` decimal(12,4) DEFAULT NULL,
            `marginBottom` decimal(12,4) DEFAULT NULL,
            `aroundCount` int(11) DEFAULT NULL,
            `acrossCount` int(11) DEFAULT NULL,
            `datetime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `paperId` int(11) unsigned DEFAULT '1' COMMENT 'linked to install',
            `default` tinyint(1) NOT NULL DEFAULT '0',
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
        dbDelta($sql);

        $tblTemplateToUser = $wpdb->prefix . self::$tableTemplateToUser;
        $sql = "CREATE TABLE `{$tblTemplateToUser}` (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `userId` BIGINT(20) DEFAULT NULL,
            `templateId` int(10) DEFAULT NULL,
            `datetime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
        dbDelta($sql);

        $tblFieldsToUser = $wpdb->prefix . self::$tableFieldsMatching;
        $sql = "CREATE TABLE `{$tblFieldsToUser}` (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `userId` BIGINT(20) DEFAULT NULL,
            `field` varchar(255) DEFAULT NULL,
            `matching` LONGTEXT DEFAULT NULL,
            `datetime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
        dbDelta($sql);

        $tblUserSettings = $wpdb->prefix . self::$tableUserSettings;
        $sql = "CREATE TABLE `{$tblUserSettings}` (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `userId` BIGINT(20) DEFAULT NULL,
            `param` varchar(255) DEFAULT NULL,
            `value` LONGTEXT DEFAULT NULL,
            `datetime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
        dbDelta($sql);

        $tblProfiles = $wpdb->prefix . self::$tableProfiles;
        $sql = "CREATE TABLE `{$tblProfiles}` (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `userId` BIGINT(20) DEFAULT NULL,
            `name` varchar(255) DEFAULT NULL,
            `templateId` int(10) DEFAULT NULL,
            `paperId` int(10) DEFAULT NULL,
            `sheetId` int(10) DEFAULT NULL,
            `params` LONGTEXT DEFAULT NULL,
            `barcodeRotate` tinyint(1) DEFAULT 0,
            `sortAlphabetically` tinyint(1) DEFAULT 0,
            `fontSize` varchar(255) DEFAULT '',
            `fontAlgorithm` varchar(255) DEFAULT 'auto',
            `fontLineBreak` varchar(255) DEFAULT 'word',
            `barcodePosition` varchar(255) DEFAULT 'center',
            `imageTextGap` varchar(255) DEFAULT 0,
            `barcodeHeightAuto` tinyint(1) DEFAULT 1,
            `showBarcode` tinyint(1) DEFAULT 1,
            `barcodeHeight` varchar(255) DEFAULT 0,
            `barcodeWidth` varchar(255) DEFAULT 0,
            `basePadding` varchar(255) DEFAULT 8,
            `marginTop` varchar(255) DEFAULT 8,
            `marginRight` varchar(255) DEFAULT 8,
            `marginBottom` varchar(255) DEFAULT 8,
            `marginLeft` varchar(255) DEFAULT 8,
            `datetime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
        dbDelta($sql);


        $tblDimension = $wpdb->prefix . self::$tableDimension;
        $sql = "CREATE TABLE `{$tblDimension}` (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `name` varchar(255) DEFAULT NULL,
            `label` varchar(255) DEFAULT NULL,
            `is_default` tinyint(1) DEFAULT NULL,
            `datetime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
        dbDelta($sql);

        $dataDimension = require __DIR__ . '/../config/dimension.php';

        $dataLabelsFormats = require __DIR__ . '/../config/labels.php';

        $dataPaperFormats = require __DIR__ . '/../config/papers.php';


        for ($i = 0; $i < count($dataDimension); ++$i) {
            $shortcode = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM `{$tblDimension}` WHERE (`id` = %s);",
                    array($dataDimension[$i]['id'])
                )
            );

            if (!$shortcode) {
                $wpdb->insert($tblDimension, $dataDimension[$i]);
            }
        }

        for ($i = 0; $i < count($dataPaperFormats); ++$i) {
            $paperFormat = $wpdb->get_row(
                $wpdb->prepare(
                    "
                        SELECT *
                        FROM `{$tblPaperFormats}`
                        WHERE (`id` = %s)
                    ",
                    array($dataPaperFormats[$i]['id'])
                )
            );

            if (!$paperFormat) {
                $wpdb->insert($tblPaperFormats, $dataPaperFormats[$i]);

                $insertId = $wpdb->insert_id;

                if (count($dataLabelsFormats) > 0) {
                    foreach ($dataLabelsFormats as $label) {
                        $label['userId'] = get_current_user_id();
                        if ($i + 1 == $label['paperId']) {
                            $label['paperId'] = $insertId;
                            $wpdb->insert($tblSheetFormat, $label);
                        }
                    }
                }
            } else {
                if ('1' === $paperFormat->default && '4' !== $paperFormat->id) {
                    $wpdb->update(
                        $tblPaperFormats,
                        array(
                            'name' => $dataPaperFormats[$i]['name'],
                            'width' => $dataPaperFormats[$i]['width'],
                            'height' => $dataPaperFormats[$i]['height'],
                            'default' => $dataPaperFormats[$i]['default'],
                            'uol_id' => $dataPaperFormats[$i]['uol_id'],
                            'is_editable' => $dataPaperFormats[$i]['is_editable'],
                        ),
                        array('id' => $dataPaperFormats[$i]['id'])
                    );
                } elseif (4 === $dataPaperFormats[$i]['id']) {
                    $wpdb->update(
                        $tblPaperFormats,
                        array(
                            'default' => $dataPaperFormats[$i]['default'],
                            'is_editable' => $dataPaperFormats[$i]['is_editable'],
                        ),
                        array('id' => $dataPaperFormats[$i]['id'])
                    );
                }
            }
        }

    }

    protected static function setupTemplatesTable()
    {
        global $wpdb;

        $tbl = $wpdb->prefix . self::$tableTemplates;

        $sql = "CREATE TABLE `{$tbl}` (
            `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `type` varchar(255) DEFAULT 'label',
            `name` varchar(255) DEFAULT NULL,
            `slug` varchar(255) DEFAULT NULL,
            `template` TEXT,
            `matching` LONGTEXT DEFAULT NULL,
            `matchingType` varchar(255) DEFAULT NULL,
            `readonlyMatching` tinyint(1) NOT NULL DEFAULT '0',
            `is_default` tinyint(1) NOT NULL DEFAULT '0',
            `is_base` tinyint(1) NOT NULL DEFAULT '0',
            `height` decimal(12,4) DEFAULT 37 NULL,
            `width` decimal(12,4) DEFAULT 70 NULL,
            `uol_id` int(11) DEFAULT 1 NULL,
            `base_padding_uol` decimal(12,4) NULL,
            `label_margin_top` decimal(12,4) NULL,
            `label_margin_right` decimal(12,4) NULL,
            `label_margin_bottom` decimal(12,4) NULL,
            `label_margin_left` decimal(12,4) NULL,
            `code_match` TINYINT(4) DEFAULT 0  NOT NULL,
            `single_product_code` TEXT,
            `variable_product_code` TEXT,
            `fontStatus` TINYINT(4) DEFAULT 1 NOT NULL,
            `fontTagLink` TEXT,
            `fontCssRules` TEXT,
            `customCss` TEXT,
            `jsAfterRender` TEXT,
            `barcode_type` varchar(255) DEFAULT NULL,
            `logo` varchar(255) DEFAULT NULL,
            `senderAddress` LONGTEXT DEFAULT NULL,
            `is_removed` TINYINT(1) DEFAULT 0  NOT NULL,
            `datetime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
        dbDelta($sql);

        $data = require __DIR__ . '/../config/templates.php';

        foreach ($data as $datum) {
            $datum['template'] = preg_replace('/\[logo_img_url]/', plugin_dir_url(dirname(__FILE__)) . 'assets/img/amazon-200x113.jpg', $datum['template']);

            $datumExists = $wpdb->get_results(
                $wpdb->prepare("SELECT * FROM `{$tbl}` WHERE `slug` = %s AND `is_default` = 1", array($datum['slug']))
            );

            if ($datumExists) {
                $wpdb->update(
                    $tbl,
                    array(
                        'name' => $datum['name'],
                        'template' => $datum['template'],
                        'width' => $datum['width'],
                        'height' => $datum['height'],
                        'uol_id' => $datum['uol_id'],
                    ),
                    array('slug' => $datum['slug'], 'is_default' => 1)
                );
            } else {
                $wpdb->insert($tbl, $datum);
            }
        }
    }

    protected static function setDefaultValues()
    {
        global $wpdb;

        $tbl = $wpdb->prefix . self::$tableTemplates;
        $templates = $wpdb->get_results("SELECT * FROM `{$tbl}` WHERE `is_default` = 1 AND `is_base` = 1", ARRAY_A);

        foreach ($templates as $template) {
            if (!$template['matching']) {
                $defMatching = '{"lineBarcode":{"value":"ID","label":"Product Id","type":"standart","fieldType":"standart","customType":"label"},"fieldLine1":{"value":"post_title","label":"Name","type":"standart","fieldType":"standart","customType":"label"},"fieldLine2":{"value":"_price","label":"Actual price","type":"custom","fieldType":"standart","customType":"label"},"fieldLine3":{"value":"ID","label":"Product Id","type":"standart","fieldType":"standart","customType":"label"},"fieldLine4":{"value":"wc_category","label":"Category","type":"wc_category","fieldType":"standart","customType":"label"}}';
                $defMatchingType = 'products';
                $defBarcodeType = 'C128';

                $wpdb->update(
                    $tbl,
                    array('matching' => $defMatching, 'matchingType' => $defMatchingType, 'barcode_type' => $defBarcodeType,),
                    array('id' => $template['id'])
                );
            }
        }
    }
}
