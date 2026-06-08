<?php

class ModelExtensionModuleAvail extends Model {

    public function getOptionsId($product_option_id, $product_option_value_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_option_value WHERE product_option_id like " . (int)$product_option_id . " and product_option_value_id = " . (int)$product_option_value_id."");


        return $query->row;
    }
    public function getOptionsNames($option_id, $option_value_id, $product_option_value_id) {
        $query = $this->db->query("SELECT od.name, ovd.name as value_name, ". $product_option_value_id ." as product_option_value_id  FROM " . DB_PREFIX . "option_description od, " . DB_PREFIX . "option_value_description ovd WHERE  ovd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND od.option_id = " . (int)$option_id . " and ovd.option_id = od.option_id and ovd.option_value_id in(" . $option_value_id . ")");

        return $query->row;
    }
    public function addMail($data) {
        if ($data['special']) {
            $price = $data['special'];
        } else {
            $price = $data['price'];
        }
        $this->db->query("INSERT INTO " . DB_PREFIX . "avail SET email = '" .  $this->db->escape($data['email']) . "', product = '" .  $this->db->escape($data['product']) . "', arbitrary_fields = '" .  $this->db->escape($data['arbitrary_fields']) . "', logged_id = '" .  (int)$data['logged_id'] . "', product_id = '" .  (int)$data['product_id'] . "', desired_quantity = '" .  (int)$data['desired_quantity'] . "', price = '" .  (float)$price . "', name = '" .  $this->db->escape($data['name']) . "', comment = '" .  $this->db->escape($data['comment']) . "', status = '" .  $this->db->escape($data['status']) . "', link_page = '" . $this->db->escape($data['href']) . "', language_id = '" . (int)$data['language_id'] . "'");

    }
    public function getLastId() {
        $query = $this->db->query("SELECT max(id) FROM " . DB_PREFIX . "avail");

        return $query->row['max(id)'];
    }
    public function addOption($data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "avail_options SET main_id ='" . (int)$data['main_id'] . "', product_id = '" . (int)$data['product_id'] . "', option_value_id = '" . (int)$data['option_value_id'] . "', option_quantity = '" . (int)$data['option_quantity'] . "', product_option_value_id = '" . (int)$data['product_option_value_id'] . "', option_name = '" . $this->db->escape($data['option_name']) . "', option_type = '" . $this->db->escape($data['option_type']) . "'");
    }
    public function getProductId($product_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product WHERE product_id= ".(int)$product_id."");

        return $query->row;
    }

    public function getAvailabilities($logged_id, $data = array())
    {
        if( $data )
        {
            $sql = "SELECT a.*,p.model ,p.quantity, pd.product_name, p.stock_status_id FROM " . DB_PREFIX . "avail a, " . DB_PREFIX . "product p , " . DB_PREFIX . "product_description pd WHERE a.product_id = p.product_id AND p.product_id = pd.product_id AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'  AND a.logged_id = " . (int)$logged_id . "";
            if (!empty($data['filter_name'])) {
                $sql .= " AND a.product LIKE '" . $this->db->escape($data['filter_name']) . "%'";
            }
            if (!empty($data['filter_email'])) {
                $sql .= " AND a.product LIKE '" . $this->db->escape($data['filter_email']) . "%'";
            }
            if (isset($data['filter_status']) && !is_null($data['filter_status'])) {
                $sql .= " AND a.status = " . $this->db->escape($data['filter_status']) . "";
            }
            if (!empty($data['filter_date_start'])) {
                $sql .= " AND DATE(a.time) >= DATE('" . $this->db->escape($data['filter_date_start']) . "')";
            }
            if (!empty($data['filter_date_end'])) {
                $sql .= " AND DATE(a.time) <= DATE('" . $this->db->escape($data['filter_date_end']) . "')";
            }
            if (!empty($data['filter_model'])) {
                $sql .= " AND p.model like '". $this->db->escape($data['filter_model']). "%'";
            }

            $sort_data = array(
                'a.time',
                'a.product',
                'p.model',
                'a.price',
                'a.email',
                'a.name',
                'a.statuse',
                'a.statuse',
                'p.model',
                'p.quantity'
            );

            //  $sort_data = array( "id", "status" );
            if( isset($data["sort"]) && in_array($data["sort"], $sort_data) )
            {                    $sql .= " ORDER BY " . $data["sort"];
            }                else
            {                    $sql .= " ORDER BY id";
            }

            if( isset($data["order"]) && $data["order"] == "DESC" )
            {
                $sql .= " DESC";
            }
            else
            {
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

            foreach( $query->rows as $option )
            {
                $option_latest[] = array( "id" => $option["id"], "logged_id" => $option["logged_id"], "time" => $option["time"], "email" => $option["email"], "product_id" => $option["product_id"], "price" => $option["price"], "link_page" => $option["link_page"], "name" => $option["name"],"model" => $option["model"], "comment" => $option["comment"], "status" => $option["status"], "stock_status_id" => $option["stock_status_id"] , "quantity" => $option["quantity"], "product" => $option["product"], "language_id" => $option["language_id"], "option" => $this->OptionBuyProduct($option["product_id"], $option["id"]), "product_name" => $option["product_name"] );
            }

        } else {
            $query = $this->db->query("SELECT a.*,p.model ,p.quantity, pd.name as product_name, p.stock_status_id FROM " . DB_PREFIX . "avail a, " . DB_PREFIX . "product p , " . DB_PREFIX . "product_description pd WHERE a.product_id = p.product_id  AND p.product_id = pd.product_id AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'  AND a.logged_id = " . (int)$logged_id . " AND a.status = '0'");
            $option_val = array();
            $option_latest = array();
            foreach( $query->rows as $option )
            {
                $option_latest[] = array( "id" => $option["id"], "logged_id" => $option["logged_id"], "time" => $option["time"], "email" => $option["email"], "product_id" => $option["product_id"], "price" => $option["price"], "link_page" => $option["link_page"], "name" => $option["name"], "comment" => $option["comment"], "status" => $option["status"], "stock_status_id" => $option["stock_status_id"] , "quantity" => $option["quantity"], "product" => $option["product"], "model" => $option["model"], "language_id" => $option["language_id"], "option" => $this->OptionBuyProduct($option["product_id"], $option["id"]), "product_name" => $option["product_name"] );
            }
        }
        return $option_latest;
    }
    public function OptionBuyProduct($product_id, $notify_id = "")
    {
        $sql = "SELECT DISTINCT *, pov.product_option_value_id as product_option_value_id, pov.quantity as option_quantity, ao.option_type, ao.option_name  FROM " . DB_PREFIX . "avail_options ao, " . DB_PREFIX . "product_option_value pov ";
        $sql .= "WHERE ao.product_id = " . $product_id . " AND ao.option_value_id = pov.option_value_id AND pov.product_id = " . (int)$product_id . "";
        if( !empty($notify_id) )
        {
            $sql .= " AND ao.main_id = " . (int)$notify_id . " AND pov.product_option_value_id = ao.product_option_value_id";
        }

        $query = $this->db->query($sql);
        return $query->rows;

    }

    public function changeAvailStatus($id, $status){

        // echo $id .' --- '.  $status;

        if( $this->db->query("UPDATE " . DB_PREFIX . "avail SET status = '" . (int) $status . "' WHERE id = '" . (int) $id . "'") )
        {
            return true;
        }

        return false;

    }
    public function notifyOption($product_id = NULL)
    {
        $sql = "SELECT distinct(a.id), a.name, a.language_id, a.product, p.quantity, p.price, a.product_id, a.desired_quantity, a.email,p.stock_status_id, a.arbitrary_fields from " . DB_PREFIX . "product p, " . DB_PREFIX . "avail a  WHERE a.id not in (SELECT  distinct(main_id) FROM " . DB_PREFIX . "avail_options ao) AND p.quantity > 0  AND p.product_id = a.product_id ";
        if( !is_null($product_id) )
        {
            $sql .= "AND a.product_id = " . (int)$product_id;
        }

        $sql .= " AND a.status = 0 group by a.id";
        $query = $this->db->query($sql);
        return $query->rows;
    }

    public function ProductWithOption($product_id = NULL)
    {
        $sql = "SELECT DISTINCT (a.id), a.product, a.language_id, a.name, a.email, p.quantity, a.desired_quantity, p.price,a.product_id, p.stock_status_id, a.arbitrary_fields FROM " . DB_PREFIX . "product p, " . DB_PREFIX . "avail a, " . DB_PREFIX . "avail_options ao WHERE a.id = ao.main_id AND p.product_id = a.product_id";
        if( !is_null($product_id) )
        {
            $sql .= " AND a.product_id = " . (int)$product_id;
        }

        $sql .= " AND a.status = 0";
        $query = $this->db->query($sql);
        return $query->rows;
    }
    public function ProductStockStatusId($product_id = NULL)
    {
        $sql = "SELECT stock_status_id, quantity FROM " . DB_PREFIX . "product p WHERE p.product_id = ". (int)$product_id;
        $query = $this->db->query($sql);
        return $query->row;
    }
    public function getPrice($product_id) {
        $query = $this->db->query("SELECT p.price as price, (SELECT price FROM " . DB_PREFIX . "product_discount pd2 WHERE pd2.product_id = p.product_id AND pd2.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND pd2.quantity = '1' AND ((pd2.date_start = '0000-00-00' OR pd2.date_start < NOW()) AND (pd2.date_end = '0000-00-00' OR pd2.date_end > NOW())) ORDER BY pd2.priority ASC, pd2.price ASC LIMIT 1) AS discount, (SELECT price FROM " . DB_PREFIX . "product_special ps WHERE ps.product_id = p.product_id AND ps.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND ((ps.date_start = '0000-00-00' OR ps.date_start < NOW()) AND (ps.date_end = '0000-00-00' OR ps.date_end > NOW())) ORDER BY ps.priority ASC, ps.price ASC LIMIT 1) AS special, p.sort_order FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) WHERE p.product_id = '" . (int)$product_id . "' AND p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "'");
        if ($query->num_rows) {
            return array(
                'price'            => ($query->row['discount'] ? $query->row['discount'] : $query->row['price']),
                'special'          => $query->row['special'],
            );

        } else {
            return false;
        }
    }
    public function changeMailStatus($id, $status)
    {
        if( $this->db->query("UPDATE " . DB_PREFIX . "avail SET status = '" . (int) $status . "' WHERE id = '" . (int) $id . "'") )
        {
            return true;
        }

        return false;
    }
    public function notifyProductByStokStatus($product_id = NULL)
    {
        $sql = "SELECT distinct(a.id), a.name, a.language_id, a.product, p.quantity, a.desired_quantity, p.price, a.product_id, a.email,p.stock_status_id, a.arbitrary_fields from " . DB_PREFIX . "product p, " . DB_PREFIX . "avail a  WHERE  p.product_id = a.product_id ";
        if( !is_null($product_id) )
        {
            $sql .= "AND a.product_id = " . (int)$product_id;
        }
        $sql .= " AND a.status = 0 group by a.id";
        $query = $this->db->query($sql);
        return $query->rows;
    }
    public function getOptionPrice($product_id, $option){
        $option_price = 0;
        $option_points = 0;
        $option_weight = 0;

        $option_data = array();
        foreach ($option as $product_option_id => $value) {
            $option_query = $this->db->query("SELECT po.product_option_id, po.option_id, od.name, o.type FROM " . DB_PREFIX . "product_option po LEFT JOIN `" . DB_PREFIX . "option` o ON (po.option_id = o.option_id) LEFT JOIN " . DB_PREFIX . "option_description od ON (o.option_id = od.option_id) WHERE po.product_option_id = '" . (int)$product_option_id . "' AND po.product_id = '" . (int)$product_id . "' AND od.language_id = '" . (int)$this->config->get('config_language_id') . "'");
            if ($option_query->num_rows) {
                if ($option_query->row['type'] == 'select' || $option_query->row['type'] == 'radio') {
                    $option_value_query = $this->db->query("SELECT pov.option_value_id, ovd.name, pov.quantity, pov.subtract, pov.price, pov.price_prefix, pov.points, pov.points_prefix, pov.weight, pov.weight_prefix FROM " . DB_PREFIX . "product_option_value pov LEFT JOIN " . DB_PREFIX . "option_value ov ON (pov.option_value_id = ov.option_value_id) LEFT JOIN " . DB_PREFIX . "option_value_description ovd ON (ov.option_value_id = ovd.option_value_id) WHERE pov.product_option_value_id = '" . (int)$value . "' AND pov.product_option_id = '" . (int)$product_option_id . "' AND ovd.language_id = '" . (int)$this->config->get('config_language_id') . "'");

                    if ($option_value_query->num_rows) {
                        if ($option_value_query->row['price_prefix'] == '+') {
                            $option_price += $option_value_query->row['price'];
                        } elseif ($option_value_query->row['price_prefix'] == '-') {
                            $option_price -= $option_value_query->row['price'];
                        }

                        if ($option_value_query->row['points_prefix'] == '+') {
                            $option_points += $option_value_query->row['points'];
                        } elseif ($option_value_query->row['points_prefix'] == '-') {
                            $option_points -= $option_value_query->row['points'];
                        }

                        if ($option_value_query->row['weight_prefix'] == '+') {
                            $option_weight += $option_value_query->row['weight'];
                        } elseif ($option_value_query->row['weight_prefix'] == '-') {
                            $option_weight -= $option_value_query->row['weight'];
                        }
                    }
                } elseif ($option_query->row['type'] == 'checkbox' && is_array($value)) {
                    foreach ($value as $product_option_value_id) {

                        $option_value_query = $this->db->query("SELECT pov.option_value_id, pov.quantity, pov.subtract, pov.price, pov.price_prefix, pov.points, pov.points_prefix, pov.weight, pov.weight_prefix, ovd.name FROM " . DB_PREFIX . "product_option_value pov LEFT JOIN " . DB_PREFIX . "option_value_description ovd ON (pov.option_value_id = ovd.option_value_id) WHERE pov.product_option_value_id = '" . (int)$product_option_value_id . "' AND pov.product_option_id = '" . (int)$product_option_id . "' AND ovd.language_id = '" . (int)$this->config->get('config_language_id') . "'");

                        if ($option_value_query->num_rows) {
                            if ($option_value_query->row['price_prefix'] == '+') {
                                $option_price += $option_value_query->row['price'];
                            } elseif ($option_value_query->row['price_prefix'] == '-') {
                                $option_price -= $option_value_query->row['price'];
                            }

                            if ($option_value_query->row['points_prefix'] == '+') {
                                $option_points += $option_value_query->row['points'];
                            } elseif ($option_value_query->row['points_prefix'] == '-') {
                                $option_points -= $option_value_query->row['points'];
                            }

                            if ($option_value_query->row['weight_prefix'] == '+') {
                                $option_weight += $option_value_query->row['weight'];
                            } elseif ($option_value_query->row['weight_prefix'] == '-') {
                                $option_weight -= $option_value_query->row['weight'];
                            }
                        }
                    }
                }
            }
        }

        return  $option_price;
    }

    public function getOptionType($options){
        $product_option = Array();

        foreach ($options as $key => $option) {
            $product_option_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_option po LEFT JOIN " . DB_PREFIX . "option o ON (po.option_id = o.option_id) WHERE po.product_option_id = ". (int)$key );
            $option_type = $product_option_query->rows[0]['type'];

            $product_option[$key] = array('val' => $option,
                'type' => $option_type,
            );
        }

        return $product_option;
    }




    public function getDataAvail($id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "avail WHERE id=".(int)$id."");
        return $query->row;
    }

    public function getTotalAvailOpen() {
        $query = $this->db->query("SELECT count(id) as count FROM " . DB_PREFIX . "avail WHERE status = 0");
        if($query->rows){
            return $query->row['count'];
        } else {
            return 0;
        }

    }

}

?>
