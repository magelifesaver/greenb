<?php

namespace UkrSolution\ProductLabelsPrinting\BarcodeTemplates;

use UkrSolution\ProductLabelsPrinting\Database;
use UkrSolution\ProductLabelsPrinting\Dimensions;
use UkrSolution\ProductLabelsPrinting\Helpers\UserSettings;
use UkrSolution\ProductLabelsPrinting\Helpers\Variables;
use UkrSolution\ProductLabelsPrinting\Request;
use UkrSolution\ProductLabelsPrinting\Settings;
use UkrSolution\ProductLabelsPrinting\Validator;

class BarcodeTemplatesController
{
    protected $wpdb;
    protected $tbl;
    protected $tblTemplateToUser;
    protected $digitalTemplateValidationRules = array(
        'id' => 'numeric',
        'height' => 'required|numeric',
        'width' => 'required|numeric',
        'base_padding_uol' => 'numeric',
        'barcode_type' => 'string',
    );
    protected $templateValidationRules = array(
        'id' => 'numeric',
        'name' => 'required',
        'type' => 'required|string',
        'logo' => 'string',
        'senderAddress' => 'html',
        'template' => 'xml',
        'height' => 'required|numeric',
        'width' => 'required|numeric',
        'label_margin_top' => 'required',
        'label_margin_right' => 'required',
        'label_margin_bottom' => 'required',
        'label_margin_left' => 'required',
        'barcode_type' => 'string',
        'code_match' => 'numeric',
        'fontStatus' => 'numeric',
        'fontTagLink' => 'html',
        'fontCssRules' => 'string',
        'customCss' => 'html',
        'jsAfterRender' => 'js',
    );
    protected $defaultTemplateValidationRules = array(
        'id' => 'numeric',
        'name' => 'string',
        'barcode_type' => 'string',
        'code_match' => 'numeric',
        'fontStatus' => 'numeric',
        'fontTagLink' => 'html',
        'fontCssRules' => 'string',
        'customCss' => 'html',
        'jsAfterRender' => 'js',
    );

    protected $allowedCssAttrs = array(
        'transform',
        'transform-origin',
    );

    public $allowedHtml = 'post';

    public function __construct()
    {
        global $wpdb;

        $this->wpdb = $wpdb;
        $this->tbl = $wpdb->prefix . Database::$tableTemplates;
        $this->tblTemplateToUser = $wpdb->prefix . Database::$tableTemplateToUser;
    }

    public function create()
    {
        $templates = $this->wpdb->get_results("SELECT * FROM `{$this->tbl}`");

        include_once Variables::$A4B_PLUGIN_BASE_PATH . 'templates/barcode-templates/create.php';
    }

    public function createNewTemplate()
    {
        Request::ajaxRequestAccess();
        if (!current_user_can('manage_options')) {
            wp_die();
        }

        $post = array();

        foreach (array('name', 'type') as $key) {
            if (isset($_POST[$key])) {
                $post[$key] = sanitize_text_field($_POST[$key]);
            }
        }
        if (isset($_POST['template'])) {
            $post['template'] = wp_kses($_POST['template'], 'post');
        }

        $data = Validator::create($post, array(
            'name' => 'required',
            'template' => 'string',
            'type' => 'string',
        ), true)->validate();

        $dimensions = new Dimensions();

        $data["uol_id"] = $dimensions->getActive();

        if ((int) $dimensions->getActive() === 2) {
            $data["width"] = 2.70;
            $data["height"] = 1.40;
        } elseif ((int) $dimensions->getActive() === 3) {
            $data["width"] = 250;
            $data["height"] = 160;
        } else {
            $data["width"] = 70;
            $data["height"] = 37;
        }


        $data["label_margin_top"] = 0;
        $data["label_margin_right"] = 0;
        $data["label_margin_bottom"] = 0;
        $data["label_margin_left"] = 0;
        $data["fontStatus"] = 1;
        $data["fontTagLink"] = '<link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">';
        $data["fontCssRules"] = "font-family: 'Roboto', sans-serif;";

        if ($this->wpdb->insert($this->tbl, $data)) {
            $templateId = $this->wpdb->insert_id;
            $template = $this->wpdb->get_row(
                $this->wpdb->prepare("SELECT * FROM `{$this->tbl}` WHERE `id` = %s", $templateId),
                ARRAY_A
            );

            uswbg_a4bJsonResponse(array(
                "message" => __('Template created successfully!', 'wpbcu-barcode-generator'),
                "template" => $template,
            ));
        } else {
            uswbg_a4bJsonResponse(array(
                "error" => $this->wpdb->last_error,
            ));
        }

    }

    public function edit()
    {
        $pluginUrl = plugin_dir_url(dirname(dirname(__FILE__)));
        echo '<script>window.barcodePluginUrl = "' . esc_js($pluginUrl) . '"</script>';

        $prefix = '';

        echo '<div><a href="#" id="'.esc_attr('barcode' . $prefix . '-custom-templates').'"></a></div>';
    }

    public function update()
    {
        Request::ajaxRequestAccess();

        if (!current_user_can('manage_options')) {
            wp_die();
        }

        $id = isset($_POST['id']) ? intval(sanitize_key($_POST['id'])) : null;
        $chosenTemplateRow = $this->wpdb->get_row($this->wpdb->prepare("SELECT * FROM `{$this->tbl}` WHERE `id` = %s", $id), ARRAY_A);

        if (isset($_POST['code_match']) && '1' === sanitize_key($_POST['code_match'])) {
            $this->defaultTemplateValidationRules['single_product_code'] = 'complexCodeValue';
            $this->defaultTemplateValidationRules['variable_product_code'] = 'complexCodeValue';
            $this->templateValidationRules['single_product_code'] = 'complexCodeValue';
            $this->templateValidationRules['variable_product_code'] = 'complexCodeValue';
        }

        $post = array();
        foreach (array(
            'id', 'name', 'type', 'logo', 'height', 'width',
            'label_margin_top', 'label_margin_right', 'label_margin_bottom', 'label_margin_left',
            'barcode_type', 'code_match', 'fontStatus'
        ) as $key) {
            if (isset($_POST[$key])) {
                $post[$key] = sanitize_text_field($_POST[$key]);
            }
        }
        foreach (array('senderAddress', 'fontTagLink', 'fontCssRules', 'customCss', 'jsAfterRender') as $key) {
            if (isset($_POST[$key])) {
                $post[$key] = stripslashes($_POST[$key]);
            }
        }

        if (isset($_POST['template'])) {
            $post['template'] = stripslashes($_POST['template']);
        }

        foreach (array('single_product_code', 'variable_product_code') as $key) {
            if (isset($_POST[$key])) {
                $post[$key] = sanitize_textarea_field($_POST[$key]);
            }
        }

        if ($chosenTemplateRow['is_default'] || $chosenTemplateRow['is_base']) {
            $data = Validator::create($post, $this->defaultTemplateValidationRules, true)->validate();
        } else {
            $data = Validator::create($post, $this->templateValidationRules, true)->validate();
        }

        if (!current_user_can('unfiltered_html')) {
            unset($data['jsAfterRender']);
        }

        $res = $this->wpdb->update($this->tbl, $data, array('id' => $data['id']));

        if ($res !== false) {
            uswbg_a4bJsonResponse(array(
                "message" => __('Template updated successfully!', 'wpbcu-barcode-generator'),
            ));
        } else {
            uswbg_a4bJsonResponse(array(
                "error" => $this->wpdb->last_error,
            ));
        }

    }

    public function templateAllowedStyleAttrFilterHook($attr)
    {
        foreach ($this->allowedCssAttrs as $allowedCssAttr) {
            if (!isset($attr[$allowedCssAttr])) {
                $attr[] = $allowedCssAttr;
            }
        }

        return $attr;
    }

    public function templateAllowedStyleCssFilterHook($allowCss, $cssTestString)
    {
        return true;
    }

    public function updateDigital()
    {
        Request::ajaxRequestAccess();

        if (!current_user_can('manage_options')) {
            wp_die();
        }

        $id = isset($_POST['id']) ? intval(sanitize_key($_POST['id'])) : null;
        $chosenTemplateRow = $this->wpdb->get_row($this->wpdb->prepare("SELECT * FROM `{$this->tbl}` WHERE `id` = %s", $id), ARRAY_A);

        $post = array();
        foreach (array('id', 'height', 'width', 'padding', 'barcode_type') as $key) {
            if (isset($_POST[$key])) {
                $post[$key] = sanitize_text_field($_POST[$key]);
            }
        }

        if (isset($post['padding'])) {
            $post['base_padding_uol'] = $post['padding'];
            unset($post['padding']);
        }

        $data = Validator::create($post, $this->digitalTemplateValidationRules, true)->validate();
        $res = $this->wpdb->update($this->tbl, $data, array('id' => $data['id']));

        if ($res !== false) {
            uswbg_a4bJsonResponse(array(
                "message" => __('Template updated successfully!', 'wpbcu-barcode-generator'),
            ));
        } else {
            uswbg_a4bJsonResponse(array(
                "error" => $this->wpdb->last_error,
            ));
        }

        uswbg_a4bJsonResponse(array(
            "error" => __('Unknown error', 'wpbcu-barcode-generator'),
        ));
    }

    public function delete()
    {
        Request::ajaxRequestAccess();

        if (!current_user_can('manage_options')) {
            wp_die();
        }

        $post = array();
        if (isset($_POST['id'])) {
            $post['id'] = sanitize_key($_POST['id']);
        }

        $data = Validator::create($post, array('id' => 'required'), true)->validate();

        $template = $this->wpdb->get_row($this->wpdb->prepare("SELECT `name` FROM {$this->tbl} WHERE id = %d;",  $data['id']));
        $random = bin2hex(openssl_random_pseudo_bytes(5));
        $name = $template ? $template->name . "-" . $random : $random;

        if ($this->wpdb->update($this->tbl, array('name' => $name, 'is_removed' => 1), array('id' => $data['id'], 'is_default' => 0))) {
            uswbg_a4bFlashMessage(__('Template deleted successfully!'), 'success');
        } else {
            uswbg_a4bFlashMessage($this->wpdb->last_error ?: __('Template not found.'), 'error');
        }

        $this->redirectToEditPage();

    }

    public function copy()
    {
        Request::ajaxRequestAccess();
        if (!current_user_can('manage_options')) {
            wp_die();
        }

        $post = array();
        if (isset($_POST['id'])) {
            $post['id'] = sanitize_key($_POST['id']);
        }
        if (isset($_POST['name'])) {
            $post['name'] = sanitize_text_field($_POST['name']);
        }

        $data = Validator::create($post, array('id' => 'required', 'name' => 'string'), true)->validate();

        $template = $this->getTemplateById($data["id"]);

        if (!$template) {
            uswbg_a4bJsonResponse(array(
                "error" => __('Template not found!'),
            ));
        }

        unset($template->id);


        $template->slug = "";
        $template->is_default = 0;
        $template->name = $data['name'] ? $data['name'] : $template->name . " - copy";

        $this->wpdb->insert(
            $this->tbl,
            (array) $template
        );

        uswbg_a4bJsonResponse(array(
            "message" => __('Template copied successfully!'),
            "id" => $this->wpdb->insert_id,
        ));

    }

    public function setactive()
    {
        Request::ajaxRequestAccess();
        if (!current_user_can('manage_options')) {
            wp_die();
        }

        $id = isset($_POST['id']) ? intval($_POST['id']) : null;
        $resetSettings = isset($_POST['resetSettings']) ? sanitize_key($_POST['resetSettings']) : 1;
        $page = isset($_POST['page']) ? sanitize_key($_POST['page']) : "";
        $resetMatching = $page === "template-settings";

        $paperSheet = null;

        if ($id) {
            $this->setActiveTemplate($id, $resetMatching, false);

            $userSettings = UserSettings::get();
            $settings = new Settings();

            foreach ($settings->defProfilesSettingsKeys as $key) {
                if ((int) $resetSettings && isset($userSettings[$key]) && isset($userSettings[$key]->profileId) && $userSettings[$key]->profileId) {
                    $userSettings[$key]->profileId = "";
                    $tableUserSettings = $this->wpdb->prefix . Database::$tableUserSettings;
                    $userId = get_current_user_id();
                    $paperSheet = json_encode($userSettings[$key]);
                    $this->wpdb->update("{$tableUserSettings}", array('value' => $paperSheet), array('userId' => $userId, 'param' => $key));
                }
            }
        }

        uswbg_a4bJsonResponse(array(
            "message" => __('Template activated successfully!'),
            "userSettings" => UserSettings::get(),
        ));

    }

    public function setActiveTemplate($id, $resetMatching = true, $isAjax = true)
    {
        $this->wpdb->delete(
            $this->tblTemplateToUser,
            array('userId' => get_current_user_id()),
            array('%d')
        );

        $this->wpdb->insert(
            $this->tblTemplateToUser,
            array(
                'userId' => get_current_user_id(),
                'templateId' => $id,
            ),
            array('%d', '%d')
        );

    }

    protected function redirectToEditPage($id = null)
    {
        wp_redirect(admin_url('/admin.php?page=wpbcu-barcode-templates-edit&id=' . $id));
        exit;
    }

    protected function redirectToCreatePage()
    {
        wp_redirect(admin_url('/admin.php?page=wpbcu-barcode-templates-create'));
        exit;
    }

    public function getActiveTemplate()
    {
        $userId = get_current_user_id();

        $userTemplate = $this->wpdb->get_row("SELECT * FROM `{$this->tblTemplateToUser}` WHERE `userId` = {$userId}");

        $chosenTemplateRow = null;

        if ($userTemplate) {
            $chosenTemplateRow = $this->wpdb->get_row("SELECT * FROM `{$this->tbl}` WHERE `id` = {$userTemplate->templateId}");
        }

        if (!$chosenTemplateRow) {
            $chosenTemplateRow = $this->wpdb->get_row("SELECT * FROM `{$this->tbl}` WHERE `slug` = 'default-1'");
        }

        try {
            if ($chosenTemplateRow && $chosenTemplateRow->matching) {
                $chosenTemplateRow->matching = @json_decode($chosenTemplateRow->matching);
            }
        } catch (\Throwable $th) {
            return $chosenTemplateRow;
        }

        $dimensions = new Dimensions();
        $activeDimension = $dimensions->getActive();

        if ($chosenTemplateRow && $chosenTemplateRow->is_base && $chosenTemplateRow->is_default && $activeDimension) {
            $chosenTemplateRow->uol_id = $activeDimension;
        }


        return $chosenTemplateRow;
    }

    public function getTemplateById($id)
    {
        $chosenTemplateRow = $this->wpdb->get_row(
            $this->wpdb->prepare("SELECT * FROM `{$this->tbl}` WHERE `id` = %s", $id),
            OBJECT
        );

        return $chosenTemplateRow;
    }

    public function getAllTemplates()
    {
        $userId = get_current_user_id();

        $dimensions = new Dimensions();

        $templates = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT TU.id AS 'is_active', T.* "
                    . " FROM `{$this->tbl}` AS T "
                    . " LEFT JOIN `{$this->tblTemplateToUser}` AS TU ON T.id = TU.templateId AND TU.userId = %s "
                    . " WHERE (T.uol_id = '%d' OR T.is_base = 1) "
                    . " AND T.is_removed = 0 "
                    . " ORDER BY T.id ",
                $userId,
                $dimensions->getActive()
            )
        );

        $activeIndex = null;

        foreach ($templates as $key => $template) {
            if ($template->is_active) {
                $activeIndex = $key;
            }


            if ($template->matching) {
                $template->matching = @json_decode($template->matching);
            }

        }

        if ($activeIndex === null && $templates) {
            $templates[0]->is_active = $userId;
        }

        return $templates;
    }

    public function getConstantAttributes($template, $constant)
    {
        $attributes = array(
            "width" => $template->width,
            "height" => $template->height,
        );

        preg_match("/\[$constant\s(.*)\]/i", $template->template, $m);

        if (!isset($m[1])) {
            return $attributes;
        }

        $arr = explode(" ", $m[1]);

        if (!$arr) {
            return $attributes;
        }

        foreach ($arr as $value) {
            $attr = explode("=", $value);

            if ($attr && count($attr) === 2) {
                $attributes[$attr[0]] = $attr[1];
            }
        }

        return $attributes;
    }

    public function getNodes($template, $tag)
    {
        $list = array();

        $p = xml_parser_create();
        xml_parse_into_struct($p, $template, $vals, $index);
        xml_parser_free($p);

        if (!$vals) {
            return $list;
        }

        foreach ($vals as $node) {
            if (strtolower($node["tag"]) === $tag) {
                $attrs = array(
                    'dominant-baseline' => '',
                    'text-anchor' => '',
                );

                foreach ($node["attributes"] as $key => $value) {
                    $attrs[strtolower($key)] = strtolower($value);
                }
                $list[] = $attrs;
            }
        }

        return $list;
    }

    public function updatePaperSize($uolId, $width, $height)
    {
        $table = $this->wpdb->prefix . Database::$tablePaperFormats;
        $this->wpdb->update($table, array('width' => $width, 'height' => $height), array('is_roll' => 1, 'uol_id' => $uolId));
    }
}
