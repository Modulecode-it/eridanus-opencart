<?php

class ModelExtensionModuleAvail extends Model
{


    public function checklicense($data)
    {
        $input = parse_url(HTTP_CATALOG, PHP_URL_HOST);


        if (!empty($data)) {
            $license = $data;
        } else {
            $license = $this->config->get("availl_license");
        }

        if (empty($license)) {
            $result = $this->getLicense();
            if (!empty($result['license'])) {
                $license = $result['license'];
            }
        }

        if (!empty($license)) {
            $iv = '1234567895436874';
            $textToEncrypt = $license;
            $encryptionMethod = "AES-256-CBC";  // AES is used by the U.S. gov't to encrypt top secret documents.
            $secretHash = "25c6c7ff35b9979b151f2136cd13b0ff";
            // $encryptedMessage = openssl_encrypt($textToEncrypt, $encryptionMethod, $secretHash,false, $iv);
            //  echo  $encryptedMessage.'<br>';


            $decrypted = openssl_decrypt($textToEncrypt, $encryptionMethod, $secretHash, false, $iv);
            //  echo    $license.'<br>';
            // echo    $decrypted.'<br>';
            //   echo    $input.'<br>';
            if ($decrypted == $input) {
                return true;
            } else {
                return false;
            }

        } else {
            return false;
        }

    }

    public function getAvailabilitiesTotal($data = array())
    {
        $sql = "SELECT a.*,p.model ,p.quantity, ss.name as product_status , p.sku as sku FROM " . DB_PREFIX . "avail a LEFT JOIN " . DB_PREFIX . "product p ON (a.product_id = p.product_id) LEFT JOIN " . DB_PREFIX . "stock_status ss ON (ss.stock_status_id = p.stock_status_id )";


        $query = $this->db->query($sql);

        return $query->num_rows;
    }

    public function getAvailabilities($data = array())
    {
        if ($this->checklicense($this->config->get("availl_license")) == 'true') {
            if ($data) {
                $sql = "SELECT a.*,p.model ,p.quantity, ss.name as product_status, p.sku as sku  FROM " . DB_PREFIX . "avail a, " . DB_PREFIX . "product p , " . DB_PREFIX . "stock_status ss WHERE ss.stock_status_id = p.stock_status_id AND a.product_id = p.product_id ";
                if (!empty($data['filter_name'])) {
                    $sql .= " AND a.product LIKE '" . $this->db->escape($data['filter_name']) . "%'";
                }
                if (!empty($data['filter_email'])) {
                    $sql .= " AND a.email LIKE '" . $this->db->escape($data['filter_email']) . "%'";
                }
                if (isset($data['filter_status']) && !is_null($data['filter_status'])) {
                    $sql .= " AND a.status = " . $data['filter_status'] . "";
                }
                if (!empty($data['filter_date_start'])) {
                    $sql .= " AND DATE(a.time) >= DATE('" . $this->db->escape($data['filter_date_start']) . "')";
                }
                if (!empty($data['filter_date_end'])) {
                    $sql .= " AND DATE(a.time) <= DATE('" . $this->db->escape($data['filter_date_end']) . "')";
                }
                if (!empty($data['filter_model'])) {
                    $sql .= " AND p.model like '" . $data['filter_model'] . "%'";
                }
                if (!empty($data['filter_sku'])) {
                    $sql .= " AND p.sku like '" . $data['filter_sku'] . "%'";
                }

                $sort_data = array(
                    'a.time',
                    'a.product',
                    'p.model',
                    'a.price',
                    'p.price',
                    'a.email',
                    'a.name',
                    'a.status',
                    'p.sku',
                    'p.quantity'
                );
                $sql .= " GROUP BY a.id ";
                //  $sort_data = array( "id", "status" );
                if (isset($data["sort"]) && in_array($data["sort"], $sort_data)) {
                    $sql .= " ORDER BY " . $data["sort"];
                } else {
                    $sql .= " ORDER BY id";
                }

                if (isset($data["order"]) && $data["order"] == "DESC") {
                    $sql .= " DESC";
                } else {
                    $sql .= " ASC";
                }


                if (isset($data['start']) || isset($data['limit'])) {
                    if ($data['start'] < 0) {
                        $data['start'] = 0;
                    }

                    if ($data['limit'] < 1) {
                        $data['limit'] = 20;
                    }

                    $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
                }

                $query = $this->db->query($sql);

                $option_val = array();
                $option_latest = array();

                foreach ($query->rows as $option) {
                    $option_latest[] = array("id" => $option["id"],
                        "time" => $option["time"],
                        "email" => $option["email"],
                        "product_id" => $option["product_id"],
                        "price" => $option["price"],
                        "link_page" => $option["link_page"],
                        "name" => $option["name"],
                        "model" => $option["model"],
                        "comment" => $option["comment"],
                        "sku" => $option["sku"],
                        "status" => $option["status"],
                        "product_status" => $option["product_status"],
                        "quantity" => $option["quantity"],
                        "desired_quantity" => $option["desired_quantity"],
                        "product" => $option["product"],
                        "arbitrary" => $option["arbitrary_fields"],
                        "language_id" => $option["language_id"],
                        "option" => $this->OptionBuyProduct($option["product_id"], $option["id"]));
                }
            } else {
                $query = $this->db->query("SELECT a.*,p.model ,p.quantity, ss.name as product_status, p.sku FROM " . DB_PREFIX . "avail a, " . DB_PREFIX . "product p, " . DB_PREFIX . "stock_status ss WHERE ss.stock_status_id = p.stock_status_id AND a.product_id = p.product_id ");
                $option_val = array();
                $option_latest = array();
                foreach ($query->rows as $option) {
                    $option_latest[] = array("id" => $option["id"],
                        "time" => $option["time"],
                        "email" => $option["email"],
                        "product_id" => $option["product_id"],
                        "price" => $option["price"],
                        "link_page" => $option["link_page"],
                        "name" => $option["name"],
                        "comment" => $option["comment"],
                        "status" => $option["status"],
                        "quantity" => $option["quantity"],
                        "product" => $option["product"],
                        "model" => $option["model"],
                        "sku" => $option["sku"],
                        "language_id" => $option["language_id"],
                        "option" => $this->OptionBuyProduct($option["product_id"], $option["id"]));
                }
            }

            return $option_latest;
        } else {
            return false;
        }
    }


    public function getProcessed($data = array())
    {
        if ($data) {

            $sql = "SELECT a.*,p.model ,p.quantity, ss.name as product_status,p.sku as sku  FROM " . DB_PREFIX . "avail a, " . DB_PREFIX . "product p, " . DB_PREFIX . "stock_status ss WHERE ss.stock_status_id = p.stock_status_id AND a.product_id = p.product_id AND a.status = '1' ";
            $sort_data = array("id", "status", "name", 'p.price');

            if (!empty($data['filter_name_close'])) {
                $sql .= " AND a.product LIKE '" . $this->db->escape($data['filter_name_close']) . "%'";
            }
            if (!empty($data['filter_email_close'])) {
                $sql .= " AND a.email LIKE '" . $this->db->escape($data['filter_email_close']) . "%'";
            }
            if (!empty($data['filter_date_start_close'])) {
                $sql .= " AND DATE(a.time) >= DATE('" . $this->db->escape($data['filter_date_start_close']) . "')";
            }
            if (!empty($data['filter_date_end_close'])) {
                $sql .= " AND DATE(a.time) <= DATE('" . $this->db->escape($data['filter_date_end_close']) . "')";
            }
            if (!empty($data['filter_model'])) {
                $sql .= " AND p.model like '" . $data['filter_model'] . "%'";
            }
            if (!empty($data['filter_sku'])) {
                $sql .= " AND p.sku like '" . $data['filter_sku'] . "%'";
            }

            $sql .= "  GROUP BY a.id ";

            if (isset($data["sort"]) && in_array($data["sort"], $sort_data)) {
                $sql .= " ORDER BY " . $data["sort"];
            } else {
                $sql .= " ORDER BY id";
            }

            if (isset($data["order"]) && $data["order"] == "DESC") {
                $sql .= " DESC";
            } else {
                $sql .= " ASC";
            }

            if (isset($data["start"]) || isset($data["limit"])) {
                if ($data["start"] < 0) {
                    $data["start"] = 0;
                }

                if ($data["limit"] < 1) {
                    $data["limit"] = 20;
                }

                $sql .= " LIMIT " . (int)$data["start"] . "," . (int)$data["limit"];
            }


            $query = $this->db->query($sql);
            $option_val = array();
            $option_latest = array();
            foreach ($query->rows as $option) {
                $option_latest[] = array("id" => $option["id"],
                    "time" => $option["time"],
                    "email" => $option["email"],
                    "product_id" => $option["product_id"],
                    "price" => $option["price"],
                    "link_page" => $option["link_page"],
                    "name" => $option["name"],
                    "model" => $option["model"],
                    "sku" => $option["sku"],
                    "comment" => $option["comment"],
                    "status" => $option["status"],
                    "quantity" => $option["quantity"],
                    "product_status" => $option["product_status"],
                    "product" => $option["product"],
                    "option" => $this->OptionBuyProduct($option["product_id"], $option["id"]));
            }

        } else {

            $query = $this->db->query("SELECT a.*,p.model, p.quantity, ss.name as product_status, p.sku as sku FROM " . DB_PREFIX . "avail a, " . DB_PREFIX . "product p, " . DB_PREFIX . "stock_status ss WHERE ss.stock_status_id = p.stock_status_id AND a.product_id = p.product_id AND a.status = '1' ");
            $option_val = array();
            $option_latest = array();
            foreach ($query->rows as $option) {
                $option_latest[] = array("id" => $option["id"],
                    "time" => $option["time"],
                    "email" => $option["email"],
                    "product_id" => $option["product_id"],
                    "price" => $option["price"],
                    "link_page" => $option["link_page"],
                    "name" => $option["name"],
                    "model" => $option["model"],
                    "sku" => $option["sku"],
                    "comment" => $option["comment"],
                    "status" => $option["status"],
                    "product_status" => $option["product_status"],
                    "quantity" => $option["quantity"],
                    "product" => $option["product"],
                    "option" => $this->OptionBuyProduct($option["product_id"], $option["id"]));
            }

        }
        return $option_latest;
    }

    public function getProducts($data = array())
    {
        if ($data) {

            $sql = "SELECT a.*, COUNT(a.product_id) AS Qty_all, p.model ,p.quantity, p.sku, ss.name as product_status  FROM " . DB_PREFIX . "avail a, " . DB_PREFIX . "product p, " . DB_PREFIX . "stock_status ss WHERE a.product_id = p.product_id AND ss.stock_status_id = p.stock_status_id ";
            $sort_data = array("id", "model", "name", 'p.price', 'sku');
            if (!empty($data['filter_name_products'])) {
                $sql .= " AND a.product LIKE '" . $this->db->escape($data['filter_name_products']) . "%'";
            }

            if (!empty($data['filter_model_products'])) {
                $sql .= " AND p.model like '" . $data['filter_model_products'] . "%'";
            }

            $sql .= " GROUP BY a.product_id";

            if (isset($data["sort"]) && in_array($data["sort"], $sort_data)) {
                $sql .= " ORDER BY " . $data["sort"];
            } else {
                $sql .= " ORDER BY id";
            }

            if (isset($data["order"]) && $data["order"] == "DESC") {
                $sql .= " DESC";
            } else {
                $sql .= " ASC";
            }

            $query = $this->db->query($sql);
            $option_latest = array();
            foreach ($query->rows as $option) {
                $checkallopt = array();
                $notifybyid = $this->GetNotifyIdByProductId($option["product_id"]);

                //$opt1 = $this->OptionBuyProductGrouped($option["product_id"]);
                $openallquant = 0;
                if ($notifybyid) {
                    $countnotify = count($notifybyid);

                    foreach ($notifybyid as $notify) {
                        $checkopt = "";
                        $optiongroups = $this->OptionBuyProductGroupedTest($option["product_id"], $notify['aoid']);
                        foreach ($optiongroups as $opt) {
                            $checkopt .= $opt['ao_id'] . "_";

                        }
                        if (in_array($checkopt, $checkallopt)) {
                        } else {
                            $checkallopt[] = $checkopt;
                            $optiongroup[] = $optiongroups;
                            $openallquant += $opt['Qty_open'];
                        }
                    }
                } else {
                    $optiongroup = false;
                    $countnotify = 0;
                }
                // $opt1 = $this->OptionBuyProductGroupedTest($option["product_id"],$option['id']);
                $checkquantopt = $this->checkOptionsQuantity($option["product_id"]);
                $open_quant = $this->countAvailByStatus($option["product_id"]);
                $option_latest[] = array(
                    "product_id" => $option["product_id"],
                    "price" => $option["price"],
                    "link_page" => $option["link_page"],
                    "name" => $option["product"],
                    "model" => $option["model"],
                    "sku" => $option["sku"],
                    "quantity" => $option["quantity"],
                    "product_status" => '',
                    "check" => $checkquantopt,
                    "option" => $optiongroup,
                    "avail_quant" => ($option["Qty_all"] - $countnotify),
                    "avail_quant_open" => ($open_quant - $openallquant)
                );
            }

        } else {

            $sql = "SELECT a.*, COUNT(a.product_id) AS Qty_all, p.model ,p.quantity, p.sku, ss.name as product_status   FROM " . DB_PREFIX . "avail a, " . DB_PREFIX . "product p, " . DB_PREFIX . "stock_status ss WHERE a.product_id = p.product_id AND ss.stock_status_id = p.stock_status_id ";
            $sql .= " GROUP BY a.product_id";
            //  $option_val = array();
            $query = $this->db->query($sql);
            $option_latest = array();
            foreach ($query->rows as $option) {
                $opt1 = $this->OptionBuyProductGrouped($option["product_id"]);
                $open_quant = $this->countAvailByStatus($option["product_id"]);
                $option_latest[] = array(
                    "product_id" => $option["product_id"],
                    "price" => $option["price"],
                    "link_page" => $option["link_page"],
                    "name" => $option["product"],
                    "model" => $option["model"],
                    "sku" => $option["sku"],
                    "quantity" => $option["quantity"],
                    "product_status" => $option["product_status"],
                    "status" => $option["status"],
                    "option" => $opt1,
                    "avail_quant" => $option["Qty_all"],
                    "proc_quant" => $open_quant
                );
            }

        }
        return $option_latest;
    }

    public function getProductQuantity($product_id)
    {
        $query = $this->db->query("SELECT quantity FROM " . DB_PREFIX . "product where product_id = " . $product_id);
        foreach ($query->row as $key => $value) {
            return $value;
        }
    }

    public function getProductStatusId($product_id)
    {
        $query = $this->db->query("SELECT ss.* FROM " . DB_PREFIX . "product p, " . DB_PREFIX . "stock_status ss  where p.product_id = " . $product_id . " AND ss.stock_status_id = p.stock_status_id");
        foreach ($query->row as $key => $value) {
            return $value;
        }
    }

    public function countAvailByStatus($product_id, $status = 0)
    {
        $query = $this->db->query("SELECT COUNT(*) AS Qty FROM " . DB_PREFIX . "avail a WHERE a.product_id = '" . (int)$product_id . "' AND status ='" . (int)$status . "'");
        if ($query->num_rows) {
            return $query->row['Qty'];
        } else {
            return 0;
        }
    }

    public function countAllAvailByStatus($status = 0)
    {
        $query = $this->db->query("SELECT COUNT(*) AS Qty FROM " . DB_PREFIX . "avail a WHERE status ='" . (int)$status . "'");
        if ($query->num_rows) {
            return $query->row['Qty'];
        } else {
            return 0;
        }
    }

    public function countAvailOptByStatus($product_id, $option, $status = "")
    {
        $sql = "SELECT COUNT(*) AS Qty FROM " . DB_PREFIX . "avail a LEFT JOIN " . DB_PREFIX . "avail_options ao ON a.id = ao.main_id WHERE a.product_id = '" . (int)$product_id . "' AND ao.option_value_id = '" . $option . "'";
        if ($status !== "") {
            $sql .= " AND a.status = '" . (int)$status . "'";
        }
        $query = $this->db->query($sql);
        if ($query->num_rows) {
            return $query->row['Qty'];
        } else {
            return 0;
        }
    }

    public function GetNotifyIdByProductId($product_id)
    {
        $query = $this->db->query("SELECT ao.main_id as aoid FROM " . DB_PREFIX . "avail_options ao WHERE ao.product_id = '" . (int)$product_id . "' GROUP BY ao.main_id");
        if ($query->num_rows) {
            return $query->rows;
        } else {
            return false;
        }
    }


    public function notify()
    {
        $query = $this->db->query("SELECT * from " . DB_PREFIX . "product p, " . DB_PREFIX . "avail a WHERE p.quantity > 0 AND p.product_id = a.product_id AND a.status = 0");
        return $query->rows;
    }

    public function notifyOption($product_id = NULL)
    {
        $sql = "SELECT distinct(a.id), a.name, a.language_id, a.product, p.quantity, p.price, a.product_id, a.desired_quantity, a.email,p.stock_status_id, a.arbitrary_fields from " . DB_PREFIX . "product p, " . DB_PREFIX . "avail a  WHERE a.id not in (SELECT  distinct(main_id) FROM " . DB_PREFIX . "avail_options ao) AND p.quantity > 0  AND p.product_id = a.product_id ";
        if (!is_null($product_id)) {
            $sql .= "AND a.product_id = " . $product_id;
        }

        $sql .= " AND a.status = 0 group by a.id";
        $query = $this->db->query($sql);
        return $query->rows;
    }

    public function notifyProductByStokStatus($product_id = NULL)
    {
        $sql = "SELECT distinct(a.id), a.name, a.language_id, a.product, p.quantity, a.desired_quantity, p.price, a.product_id, a.email,p.stock_status_id, a.arbitrary_fields from " . DB_PREFIX . "product p, " . DB_PREFIX . "avail a  WHERE  p.product_id = a.product_id ";
        if (!is_null($product_id)) {
            $sql .= "AND a.product_id = " . $product_id;
        }
        $sql .= " AND a.status = 0 group by a.id";
        $query = $this->db->query($sql);
        return $query->rows;
    }

    public function ProductWithOption($product_id = NULL)
    {
        $sql = "SELECT DISTINCT (a.id), a.product, a.language_id, a.name, a.email, p.quantity, a.desired_quantity, p.price,a.product_id, p.stock_status_id, a.arbitrary_fields FROM " . DB_PREFIX . "product p, " . DB_PREFIX . "avail a, " . DB_PREFIX . "avail_options ao WHERE a.id = ao.main_id AND p.product_id = a.product_id";
        if (!is_null($product_id)) {
            $sql .= " AND a.product_id = " . $product_id;
        }

        $sql .= " AND a.status = 0";
        $query = $this->db->query($sql);
        return $query->rows;
    }

    public function OptionBuyProduct($product_id, $notify_id = "")
    {
        $sql = "SELECT DISTINCT *, pov.option_id as option_id, pov.quantity as option_quantity, ao.option_type, ao.option_name  FROM " . DB_PREFIX . "avail_options ao, " . DB_PREFIX . "product_option_value pov ";
        $sql .= "WHERE ao.product_id = " . $product_id . " AND ao.option_value_id = pov.option_value_id AND pov.product_id = " . $product_id . "";
        if (!empty($notify_id)) {
            $sql .= " AND ao.main_id = " . $notify_id . " AND pov.product_option_value_id = ao.product_option_value_id";
        }

        $query = $this->db->query($sql);
        return $query->rows;
    }


    public function OptionBuyProductGrouped($product_id, $notify_id = "")
    {
        $sql = "SELECT pov.quantity as option_quantity, ao.option_value_id as ao_id, COUNT(ao.option_value_id) AS Qty_all, ao.option_type, ao.option_name FROM " . DB_PREFIX . "avail_options ao, " . DB_PREFIX . "product_option_value pov ";
        $sql .= "WHERE ao.product_id = " . $product_id . " AND ao.option_value_id = pov.option_value_id AND pov.product_id = " . $product_id . "";
        if (!empty($notify_id)) {
            $sql .= " AND ao.main_id = " . $notify_id . " AND pov.product_option_value_id = ao.product_option_value_id";
        }
        $sql .= " GROUP BY ao.option_value_id";
        $query = $this->db->query($sql);
        if ($query) {
            $options = array();
            foreach ($query->rows as $row) {
                $qty_open = $this->OptionBuyProductGroupedByStatus($product_id, $row['ao_id']);
                $options[] = array(
                    'option_type' => $row['option_type'],
                    'option_name' => $row['option_name'],
                    'option_quantity' => $row['option_quantity'],
                    'Qty_all' => $row['Qty_all'],
                    'Qty_open' => $qty_open
                );
            }
            return $options;
        } else {
            return false;
        }
    }

    public function OptionBuyProductGroupedTest($product_id, $notify_id = "")
    {
        $sql = "SELECT pov.quantity as option_quantity, ao.option_value_id as ao_id, COUNT(ao.option_value_id) AS Qty_all, ao.option_type, ao.option_name  FROM " . DB_PREFIX . "avail_options ao, " . DB_PREFIX . "product_option_value pov ";
        $sql .= "WHERE ao.product_id = " . $product_id . " AND ao.option_value_id = pov.option_value_id AND pov.product_id = " . $product_id . "";
        if (!empty($notify_id)) {
            $sql .= " AND ao.main_id = " . $notify_id . " AND pov.product_option_value_id = ao.product_option_value_id";
        }
        $sql .= " GROUP BY ao.option_value_id";
        $query = $this->db->query($sql);
        if ($query) {
            $options = array();
            foreach ($query->rows as $row) {
                $qty_open = $this->countAvailOptByStatus($product_id, $row['ao_id'], 0);
                $qty_all = $this->countAvailOptByStatus($product_id, $row['ao_id']);
                $options[] = array(
                    'ao_id' => $row['ao_id'],
                    'option_type' => $row['option_type'],
                    'option_name' => $row['option_name'],
                    'option_quantity' => $row['option_quantity'],
                    'Qty_all' => $qty_all,
                    'Qty_open' => $qty_open
                );
            }
            return $options;
        } else {
            return false;
        }
    }

    public function OptionBuyProductGroupedByStatus($product_id, $ao_id, $status = "")
    {
        $sql = "SELECT COUNT(ao.main_id) as Qty FROM " . DB_PREFIX . "avail_options ao LEFT JOIN " . DB_PREFIX . "avail a ON a.id = ao.main_id  WHERE ao.product_id = '" . (int)$product_id . "' AND ao.option_value_id = '" . (int)$ao_id . "'";
        if ($status !== "") {
            $sql .= " AND a.status = '" . (int)$status . "'";
        }
        $query = $this->db->query($sql);
        return $query->row['Qty'];
    }

    public function notifyByProductId($product_id)
    {
        $store = HTTP_CATALOG;

        $this->load->model('catalog/product');
        $this->language->load('extension/module/avail');

        $success = '';
        $success_options = '';

        if ($this->config->get('avail_options_status') == 2) { // по статусу товара
            $result = $this->notifyProductByStokStatus($product_id);
            /*если работаем по статусам то запрос к заявкам с опциями не делаем так как все заявки отбираются предидущим запросом*/
            //  $result_options = Array();
            $result_options = $this->ProductWithOption($product_id);
        } else {
            $result = $this->notifyOption($product_id);
            /*получаем список заявок по продуктам с опциями*/
            $result_options = $this->ProductWithOption($product_id);
        }

        $messages = $this->config->get('avail');
        $product = Array();

//        $data['token'] = $this->session->data['token'];
        /*mail send*/
        $notifi_for_admin = 1;
        require_once(DIR_CATALOG . 'controller/extension/module/availmail.php');
        /*end mail send*/

        if (!empty($success) || !empty($success_options)) {
            $json['success'] = $this->language->get('success');
            $this->response->setOutput(json_encode($json));
        } else {
            $json['error'] = $this->language->get('error');
            $this->response->setOutput(json_encode($json));
        }

    }

    public function getAvailability($id)
    {
        if ($id) {
            $sql = "SELECT * FROM " . DB_PREFIX . "avail where id = " . $id . "";
            $query = $this->db->query($sql);
            return $query->row;
        }

    }

    public function getTotalCalls()
    {
        $query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "avail");
        return $query->row["total"];
    }

    public function getTotalAvail()
    {
        $query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "avail");
        return $query->row["total"];
    }

    public function checkOptionsQuantity($product_id)
    {
        $query = $this->db->query("SELECT COUNT(product_id) AS total FROM " . DB_PREFIX . "avail WHERE product_id = '" . (int)$product_id . "'");
        $query_opt = $this->db->query("SELECT COUNT(DISTINCT main_id) AS total FROM " . DB_PREFIX . "avail_options WHERE product_id = '" . (int)$product_id . "'");
        if ($query->row['total'] > $query_opt->row['total']) {
            return true;
        } else {
            return false;
        }

    }

    public function changeMailStatus($id, $status)
    {
        if ($this->db->query("UPDATE " . DB_PREFIX . "avail SET status = '" . (int)$status . "' WHERE id = '" . (int)$id . "'")) {
            return true;
        }

        return false;
    }

    public function deleteNotifications($id)
    {
        $this->db->query("DELETE FROM " . DB_PREFIX . "avail WHERE id = '" . (int)$id . "'");
    }

    public function getPrice($product_id)
    {
        $query = $this->db->query("SELECT p.price as price, (SELECT price FROM " . DB_PREFIX . "product_discount pd2 WHERE pd2.product_id = p.product_id AND pd2.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND pd2.quantity = '1' AND ((pd2.date_start = '0000-00-00' OR pd2.date_start < NOW()) AND (pd2.date_end = '0000-00-00' OR pd2.date_end > NOW())) ORDER BY pd2.priority ASC, pd2.price ASC LIMIT 1) AS discount, (SELECT price FROM " . DB_PREFIX . "product_special ps WHERE ps.product_id = p.product_id AND ps.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND ((ps.date_start = '0000-00-00' OR ps.date_start < NOW()) AND (ps.date_end = '0000-00-00' OR ps.date_end > NOW())) ORDER BY ps.priority ASC, ps.price ASC LIMIT 1) AS special, p.sort_order FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) WHERE p.product_id = '" . (int)$product_id . "' AND p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "'");
        if ($query->num_rows) {
            return array(
                'price' => ($query->row['discount'] ? $query->row['discount'] : $query->row['price']),
                'special' => $query->row['special'],
            );

        } else {
            return false;
        }
    }

    public function install()
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "avail` (
            `id` int(6) NOT NULL AUTO_INCREMENT,
            `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `email` varchar(50) NOT NULL,
            `logged_id` varchar(50) NOT NULL,
            `product` varchar(255) NOT NULL,
            `product_id` int(11) NOT NULL,
            `desired_quantity` int(11) NOT NULL,
            `price` varchar(50) NOT NULL,
            `link_page` varchar(255) NOT NULL,
            `name` varchar(50) NOT NULL,
            `comment` text NOT NULL,
            `status` varchar(50) NOT NULL,
            `language_id` int(3) NOT NULL,
			`arbitrary_fields` text NOT NULL,
             PRIMARY KEY (`id`)
             ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;");
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "avail_options` (
           `id` int(6) NOT NULL AUTO_INCREMENT,
           `main_id` int(6) NOT NULL,
           `product_id` int(11) NOT NULL,
           `option_value_id` int(11) NOT NULL,
           `option_quantity` int(11) NOT NULL,
           `option_name` varchar(50) NOT NULL,
           `option_type` varchar(50) NOT NULL,
           `product_option_value_id` int(11) NOT NULL,
           PRIMARY KEY (`id`)) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;");


    }

    public function uninstall()
    {
        $this->db->query("DROP TABLE IF EXISTS " . DB_PREFIX . "avail_license");
    }

    private function getLicense()
    {
        $sql = "SHOW TABLES LIKE '" . DB_PREFIX . "avail_license'";
        $result = $this->db->query($sql);
        $result = $result->num_rows;

        if ($result == 1) {
            $query = $this->db->query("SELECT license FROM " . DB_PREFIX . "avail_license");
            return $query->row;
        } else {
            return false;
        }
    }

    public function addLicense($key)
    {
        $query = $this->db->query("select * FROM " . DB_PREFIX . "avail_license");
        if (!empty($query->row)) {
            $this->db->query("UPDATE " . DB_PREFIX . "avail_license SET license = '" . $key . "'");
        } else {
            $this->db->query("INSERT INTO " . DB_PREFIX . "avail_license (license) VALUES ('" . $key . "')");
        }

    }

    public function getLeyoutByModule($code)
    {
        $query = $this->db->query("SELECT count(code) as count FROM `" . DB_PREFIX . "layout_module` Where `code` = '" . $code . "'");

        return $query->row['count'];
    }


    public function getAvailArbitrary($id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "avail WHERE id=".$id."");
        return $query->row;
    }


    /******************* Проверка на наличии поля в таблицах ****************/

    // вызывем функцию создания поля для нужного поля того которого не хватает в базе
    public function UpdateTo96($data) {
       foreach ($data as $dat){
            $function = 'alter'.$dat;
            $this->$function();

        }
        return 'ok';

    }
    public function alterdesired_quantity () {
        $query  = $this->db->query("ALTER TABLE " . DB_PREFIX . "avail ADD COLUMN desired_quantity int(11) NOT NULL AFTER language_id");
        if($query) {
            return 'ok';
        } else {
            return 'error';
        }
    }
    public function alteradmin_email() {
        $query  = $this->db->query("ALTER TABLE " . DB_PREFIX . "avail ADD COLUMN admin_email 	varchar(50) NOT NULL AFTER language_id");
        if($query) {
            return 'ok';
        } else {
            return 'error';
        }
    }
    public function alterarbitrary_fields() {
        $query  = $this->db->query("ALTER TABLE " . DB_PREFIX . "avail ADD COLUMN arbitrary_fields 	varchar(50) NOT NULL AFTER language_id");
        if($query) {
            return 'ok';
        } else {
            return 'error';
        }
    }


    public function CheckColumn(){

        $Array= Array();

        $query  = $this->db->query("SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '" . DB_PREFIX . "avail' AND table_schema = '".DB_DATABASE."' AND COLUMN_NAME = 'arbitrary_fields'");
        if($query->row['count'] == 0) {
            $Array []= 'arbitrary_fields';

        }

        $query1  = $this->db->query("SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '" . DB_PREFIX . "avail' AND table_schema = '".DB_DATABASE."' AND COLUMN_NAME = 'desired_quantity'");
        if($query1->row['count'] == 0) {
            $Array []= 'desired_quantity';

        }

        $query2  = $this->db->query("SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '" . DB_PREFIX . "avail' AND table_schema = '".DB_DATABASE."' AND COLUMN_NAME = 'admin_email'");
        if($query2->row['count'] == 0) {
            $Array []= 'admin_email';

        }

        return $Array;
    }


    /******************* Проверка на наличии поля в таблицах ****************/

    public function getTotalAvailOpen()
    {
        $query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "avail WHERE status = 0");
        return $query->row["total"];
    }

    public function CheckColumnDesiredQuantity(){
        $query  = $this->db->query("SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '" . DB_PREFIX . "avail' AND table_schema = '".DB_DATABASE."' AND COLUMN_NAME = 'desired_quantity'");
        if($query->row['count'] > 0) {
            return '1';
        } else {
            return '0';
        }
    }

}