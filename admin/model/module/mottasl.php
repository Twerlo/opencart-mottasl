<?php

namespace Opencart\Admin\Model\Extension\MottaslOc\Module;

class Mottasl extends \Opencart\System\Engine\Model
{
  public function install(): void
  {
    $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "mottasl_abandoned_carts` (
		  `notify_id` varchar(40) NOT NULL,
		  `cart_id` varchar(40) NOT NULL,
		  `shot` varchar(40) NOT NULL,
		  `date_added` datetime NOT NULL,
		  PRIMARY KEY (`notify_id`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci");
  }

  public function uninstall(): void
  {
    $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "mottasl_abandoned_carts`");
  }

  public function addNotfiy(string $notify_id): void
  {
    $this->db->query("INSERT INTO `" . DB_PREFIX . "mottasl_abandoned_carts` SET `notify_id` = '" . $this->db->escape($notify_id) . "', `date_added` = NOW()");
  }

  public function removeNotify(string $notify_id): void
  {
    $this->db->query("DELETE FROM `" . DB_PREFIX . "mottasl_abandoned_carts` WHERE `notify_id` = '" . $this->db->escape($notify_id) . "'");
  }

  public function getTotalNotfys(): int
  {
    $query = $this->db->query("SELECT COUNT(*) AS `total` FROM `" . DB_PREFIX . "mottasl_abandoned_carts`");

    return (int)$query->row['total'];
  }
}
