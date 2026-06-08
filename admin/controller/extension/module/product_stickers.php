<?php
/*
 * @ URL: https://oc3x.ru
 * @ Version: 3.0
 */

class ControllerExtensionModuleProductStickers extends Controller
{
    private $error = array();
    public function index()
    {
        $this->load->language("extension/module/product_stickers");
        $this->document->setTitle($this->language->get("heading_title"));
        $this->load->model("setting/setting");
        if ($this->request->server["REQUEST_METHOD"] == "POST" && $this->validate()) {
            $this->model_setting_setting->editSetting("module_product_stickers", $this->request->post);
            $this->session->data["success"] = $this->language->get("text_success");
            if ((int) $this->request->post["module_product_stickers_apply"]) {
                $this->response->redirect($this->url->link("extension/module/product_stickers", "user_token=" . $this->session->data["user_token"], true));
            }
            $this->response->redirect($this->url->link("marketplace/extension", "user_token=" . $this->session->data["user_token"] . "&type=module", true));
        }
        if (isset($this->error["warning"])) {
            $data["error_warning"] = $this->error["warning"];
        } else {
            $data["error_warning"] = "";
        }
        if (isset($this->error["product_stickers_price_name"])) {
            $data["error_product_stickers_price_name"] = $this->error["product_stickers_price_name"];
        } else {
            $data["error_product_stickers_price_name"] = array();
        }
        if (isset($this->error["product_stickers_custom_name"])) {
            $data["error_product_stickers_custom_name"] = $this->error["product_stickers_custom_name"];
        } else {
            $data["error_product_stickers_custom_name"] = array();
        }
        if (isset($this->session->data["success"])) {
            $data["success"] = $this->session->data["success"];
            unset($this->session->data["success"]);
        } else {
            $data["success"] = "";
        }
		$data['OC3X_version'] = '3.0';
        $data["breadcrumbs"] = array();
        $data["breadcrumbs"][] = array("text" => $this->language->get("text_home"), "href" => $this->url->link("common/dashboard", "user_token=" . $this->session->data["user_token"], true));
        $data["breadcrumbs"][] = array("text" => $this->language->get("text_extension"), "href" => $this->url->link("marketplace/extension", "user_token=" . $this->session->data["user_token"] . "&type=module", true));
        $data["breadcrumbs"][] = array("text" => $this->language->get("heading_title"), "href" => $this->url->link("extension/module/product_stickers", "user_token=" . $this->session->data["user_token"], true));
        $data["action"] = $this->url->link("extension/module/product_stickers", "user_token=" . $this->session->data["user_token"], true);
        $data["cancel"] = $this->url->link("marketplace/extension", "user_token=" . $this->session->data["user_token"] . "&type=module", true);
        if (isset($this->request->post["module_product_stickers_status"])) {
            $data["module_product_stickers_status"] = $this->request->post["module_product_stickers_status"];
        } else {
            $data["module_product_stickers_status"] = $this->config->get("module_product_stickers_status");
        }
        if (isset($this->request->post["module_product_stickers_position"])) {
            $data["module_product_stickers_position"] = $this->request->post["module_product_stickers_position"];
        } else {
            $data["module_product_stickers_position"] = $this->config->get("module_product_stickers_position");
        }
        if (isset($this->request->post["module_product_stickers_type"])) {
            $data["module_product_stickers_type"] = $this->request->post["module_product_stickers_type"];
        } else {
            $data["module_product_stickers_type"] = $this->config->get("module_product_stickers_type");
        }
        if (isset($this->request->post["module_product_stickers_new"])) {
            $data["module_product_stickers_new"] = $this->request->post["module_product_stickers_new"];
        } else {
            $data["module_product_stickers_new"] = $this->config->get("module_product_stickers_new");
        }
        if (isset($this->request->post["module_product_stickers_special"])) {
            $data["module_product_stickers_special"] = $this->request->post["module_product_stickers_special"];
        } else {
            $data["module_product_stickers_special"] = $this->config->get("module_product_stickers_special");
        }
        if (isset($this->request->post["module_product_stickers_bestseller"])) {
            $data["module_product_stickers_bestseller"] = $this->request->post["module_product_stickers_bestseller"];
        } else {
            $data["module_product_stickers_bestseller"] = $this->config->get("module_product_stickers_bestseller");
        }
        if (isset($this->request->post["module_product_stickers_stock"])) {
            $data["module_product_stickers_stock"] = $this->request->post["module_product_stickers_stock"];
        } else {
            $data["module_product_stickers_stock"] = $this->config->get("module_product_stickers_stock");
        }
        $this->load->model("tool/image");
        $data["placeholder"] = $this->model_tool_image->resize("no_image.png", 70, 70);
        if (isset($this->request->post["module_product_stickers_price"])) {
            $data["module_product_stickers_price"] = $this->request->post["module_product_stickers_price"];
        } else {
            if ($this->config->get("module_product_stickers_price")) {
                $data["module_product_stickers_price"] = $this->config->get("module_product_stickers_price");
            } else {
                $data["module_product_stickers_price"] = array();
            }
        }
        if (isset($this->request->post["module_product_stickers_custom"])) {
            $data["module_product_stickers_custom"] = $this->request->post["module_product_stickers_custom"];
        } else {
            if ($this->config->get("module_product_stickers_custom")) {
                $data["module_product_stickers_custom"] = $this->config->get("module_product_stickers_custom");
            } else {
                $data["module_product_stickers_custom"] = array();
            }
        }
        $this->load->model("localisation/language");
        $data["languages"] = $this->model_localisation_language->getLanguages();
        $data["header"] = $this->load->controller("common/header");
        $data["column_left"] = $this->load->controller("common/column_left");
        $data["footer"] = $this->load->controller("common/footer");
        $this->response->setOutput($this->load->view("extension/module/product_stickers", $data));
    }
    protected function validate()
    {
        if (!$this->user->hasPermission("modify", "extension/module/product_stickers")) {
            $this->error["warning"] = $this->language->get("error_permission");
        }
        $this->load->model("localisation/language");
        $languages = $this->model_localisation_language->getLanguages();
        if (isset($this->request->post["module_product_stickers_price"])) {
            foreach ($this->request->post["module_product_stickers_price"] as $key => $product_stickers) {
                foreach ($languages as $language) {
                    if (utf8_strlen($product_stickers[$language["language_id"]]["name"]) < 1) {
                        $this->error["product_stickers_price_name"][$key][$language["language_id"]] = $this->language->get("error_name");
                        $this->error["warning"] = $this->language->get("error_name");
                    }
                }
            }
        }
        if (isset($this->request->post["module_product_stickers_custom"])) {
            foreach ($this->request->post["module_product_stickers_custom"] as $key => $product_stickers) {
                foreach ($languages as $language) {
                    if (utf8_strlen($product_stickers[$language["language_id"]]["name"]) < 1) {
                        $this->error["product_stickers_custom_name"][$key][$language["language_id"]] = $this->language->get("error_name");
                        $this->error["warning"] = $this->language->get("error_name");
                    }
                }
            }
        }
        return !$this->error;
    }
    public function install()
    {
        $this->load->model("extension/module/product_stickers");
        $this->model_extension_module_product_stickers->createColumns();
    }
}

?>