<?php

namespace Opencart\Catalog\Model\Extension\MottaslOc\Module;

class Mottasl extends \Opencart\System\Engine\Model
{
  public function checkIfNotified(string $cart_id, string $shot): bool
  {
    $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "mottasl_abandoned_carts` WHERE `cart_id` = '" . $cart_id . "' AND `shot` = '" . $shot . "'");

    if ((bool)$query->num_rows) {
      return true;
    } else {
      return false;
    }
  }

  public function notify(string $cart_id, string $shot): void
  {
    $this->db->query("INSERT INTO `" . DB_PREFIX . "mottasl_abandoned_carts` SET `cart_id` = '" . $cart_id . "' , `shot` = '" . $shot . "'");
  }


  public function getAllCarts()
  {
    $cart_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "cart`");
    return $cart_query->rows;
  }
}
