<?php

namespace Opencart\Catalog\Controller\Extension\MottaslOc\Module;

use DateTime;

class Mottasl extends \Opencart\System\Engine\Controller
{
  public function index(): void
  {
  }

  public function save(): void
  {
  }

  public function install()
  {
  }
  public function uninstall(): void
  {
  }
  // catalog/model/checkout/order/addHistory/before
  public function handleOrderEvent(string &$route, array &$args): void
  {
    $order_id = $args[0];
    $order_status_id = $args[1];

    $this->load->model('checkout/order');
    $order_data = $this->model_checkout_order->getOrder((int)$order_id);

    if (!isset($order_data['order_id'])) {
      return;
    }

    // check that is order created or updated event
    $is_created_event =  $this->__isDateBeforeTenSeconds($order_data['date_added']);

    $this->load->model('account/customer');
    $customer_data = $this->model_account_customer->getCustomer($order_data['customer_id']);

    $this->load->model('setting/setting');
    $settings = $this->model_setting_setting->getSetting('module_mottasl');

    $is_enabled = isset($settings['module_mottasl_api_key']) && $is_created_event ? (bool)$settings['module_mottasl_order_created_status'] : (bool)$settings['module_mottasl_order_updated_status'];

    if (!$is_enabled) {
      $json = [];
      $json["error"] = "Order notification is disabled for " .
        ($is_created_event ? "order created" : "order updated") .
        "notifcations";
      $this->response->addHeader('Content-Type: application/json');
      $this->response->setOutput(json_encode($json));
      return;
    }

    $is_valid = $is_created_event ?
      isset($settings['module_mottasl_order_created_temp_id']) && isset($settings['module_mottasl_order_created_temp_lang']) :
      isset($settings['module_mottasl_order_updated_temp_id']) && isset($settings['module_mottasl_order_updated_temp_lang']);

    if (!$is_valid) {
      $json = [];
      $json["error"] = "Some Mottal settings are not set.";
      $this->response->addHeader('Content-Type: application/json');
      $this->response->setOutput(json_encode($json));
      return;
    }

    error_log("handle order event");
    error_log($is_created_event ? "created" : "updated");

    $mottasl_api_key = $settings['module_mottasl_api_key'];
    $customer_phone = $customer_data['telephone'][0] == '+' ? substr($customer_data['telephone'], 1) : $customer_data['telephone'];
    $mottasl_template_id = $is_created_event ? $settings['module_mottasl_order_created_temp_id'] : $settings['module_mottasl_order_updated_temp_id'];
    $mottasl_template_lang = $is_created_event ? $settings['module_mottasl_order_created_temp_lang'] : $settings['module_mottasl_order_updated_temp_lang'];
    $args = [$customer_data['firstname'], $order_data['invoice_prefix'] . $order_data['invoice_no'], $this->__getOrderStatus($order_status_id)];

    $this->__sendMessageWithZoko($mottasl_api_key, $customer_phone, $mottasl_template_id, $mottasl_template_lang, $args);
  }

  // catalog/model/account/customer/addCustomer/after
  public function customerCreated(string &$route, array &$args, mixed &$output): void
  {
    $customer_id = $output;

    if (!$customer_id) {
      return;
    }

    $this->load->model('account/customer');
    $customer_data = $this->model_account_customer->getCustomer($customer_id);

    $this->load->model('setting/setting');
    $settings = $this->model_setting_setting->getSetting('module_mottasl');

    $is_enabled = isset($settings['module_mottasl_api_key']) && (bool)$settings['module_mottasl_customer_created_status'];

    if (!$is_enabled) {
      $json = [];
      $json["error"] = "WA notification is disabled for customer created";
      $this->response->addHeader('Content-Type: application/json');
      $this->response->setOutput(json_encode($json));
      return;
    }

    $is_valid = isset($settings['module_mottasl_customer_created_temp_id']) && isset($settings['module_mottasl_customer_created_temp_lang']);

    if (!$is_valid) {
      $json = [];
      $json["error"] = "Some Mottal settings are not set.";
      $this->response->addHeader('Content-Type: application/json');
      $this->response->setOutput(json_encode($json));
      return;
    }

    error_log("customer created event");

    $mottasl_api_key = $settings['module_mottasl_api_key'];
    $customer_phone = $customer_data['telephone'][0] == '+' ? substr($customer_data['telephone'], 1) : $customer_data['telephone'];
    $mottasl_template_id = $settings['module_mottasl_customer_created_temp_id'];
    $mottasl_template_lang = $settings['module_mottasl_customer_created_temp_lang'];
    $args = [$customer_data['firstname']];

    $this->__sendMessageWithZoko($mottasl_api_key, $customer_phone, $mottasl_template_id, $mottasl_template_lang, $args);
  }

  public function abandonCart(): void
  {
    error_log("abandon cart cron");
    $this->load->model('setting/setting');
    $settings = $this->model_setting_setting->getSetting('module_mottasl');

    $is_enabled = isset($settings['module_mottasl_api_key']) && ((bool)$settings['module_mottasl_abandon_first_status'] || (bool)$settings['module_mottasl_abandon_second_status']);

    if (!$is_enabled) {
      $json = [];
      $json["error"] = "WA notification is disabled for abandoned cart";
      $this->response->addHeader('Content-Type: application/json');
      $this->response->setOutput(json_encode($json));
      return;
    }

    $is_valid_first = !(bool)$settings['module_mottasl_abandon_first_status'] || isset($settings['module_mottasl_abandon_first_temp_id']) && isset($settings['module_mottasl_abandon_first_temp_lang']);
    $is_valid_second = !(bool)$settings['module_mottasl_abandon_second_status'] || isset($settings['module_mottasl_abandon_second_temp_id']) && isset($settings['module_mottasl_abandon_second_temp_lang']);
    $is_valid = $is_valid_first && $is_valid_second;

    if (!$is_valid) {
      $json = [];
      $json["error"] = "Some Mottal settings are not set.";
      $this->response->addHeader('Content-Type: application/json');
      $this->response->setOutput(json_encode($json));
      return;
    }

    $mottasl_api_key = $settings['module_mottasl_api_key'];
    $mottasl_template_id_first = $settings['module_mottasl_abandon_first_temp_id'] ?? '';
    $mottasl_template_lang_first = $settings['module_mottasl_abandon_first_temp_lang'] ?? '';
    $mottasl_template_id_second = $settings['module_mottasl_abandon_second_temp_id'] ?? '';
    $mottasl_template_lang_second = $settings['module_mottasl_abandon_second_temp_lang'] ?? '';

    $this->load->model('extension/mottasl_oc/module/mottasl');
    $carts = $this->model_extension_mottasl_oc_module_mottasl->getAllCarts();

    $this->load->model('account/customer');

    foreach ($carts as $cart) {
      // cart date started was before 3 hours and not before 24
      $time = time();
      $cart_added_time = strtotime($cart['date_added']);
      $cart_first_shot_time = strtotime('+3 hour', $cart_added_time);
      $cart_second_shot_time = strtotime('+24 hour', $cart_added_time);

      // get all carts created before 24 hours as second shot
      $is_first = $time > $cart_first_shot_time && $time < $cart_second_shot_time;
      // get all carts created before 3 hours and less than 24 as first shot
      $is_second = $time > $cart_first_shot_time && $time > $cart_second_shot_time;

      if (!$is_first && !$is_second) {
        continue;
      }

      $shot = $is_first ? '1' : '2';

      // check if cart is already notified about, if so ignore
      // if not notify customer and save a record in database
      $is_notifed = $this->model_extension_mottasl_oc_module_mottasl->checkIfNotified($cart['cart_id'], $shot);
      if ($is_notifed) {
        continue;
      }

      $temp_id = $is_first ? $mottasl_template_id_first : $mottasl_template_id_second;
      $temp_lang = $is_first ? $mottasl_template_lang_first : $mottasl_template_lang_second;
      $customer_data = $this->model_account_customer->getCustomer($cart['customer_id']);
      $customer_phone = $customer_data['telephone'][0] == '+' ? substr($customer_data['telephone'], 1) : $customer_data['telephone'];
      $args = [$customer_data['firstname']];

      $this->__sendMessageWithZoko($mottasl_api_key, $customer_phone, $temp_id, $temp_lang, $args);
      $this->model_extension_mottasl_oc_module_mottasl->notify($cart['cart_id'], $shot);
    }
  }

  protected function __sendMessageWithZoko(string $api_key, string $recipient, string $template_id, string $template_language, array $template_args): void
  {
    $url = 'https://chat.zoko.io/v2/message';

    $headers = array();
    $headers[] = 'Content-Type: application/json';
    $headers[] = 'Apikey: ' . $api_key;
    // $headers[] = 'Apikey: 7f411978-8df5-4ea2-8a26-5fc8e6423f7f';

    // The data you want to send via POST
    $fields = [
      'channel' => 'whatsapp',
      'recipient' => $recipient,
      'type' => 'template',
      'templateId' => $template_id,
      'templateLanguage' => $template_language,
      'templateArgs' => $template_args
    ];

    //url-ify the data for the POST
    $fields_string = json_encode($fields);

    $curl = curl_init($url);

    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
    curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $fields_string);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($curl);

    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    error_log($response);
    if (curl_errno($curl)) {
      error_log('Error:' . curl_error($curl));
    }

    curl_close($curl);
  }

  protected function __getOrderStatus(string $order_status_id): string
  {
    $statuses = [
      '1' => 'Pending',
      '2' => 'Processing',
      '3' => 'Shipped',
      '5' => 'Complete',
      '7' => 'Canceled',
      '8' => 'Denied',
      '9' => 'Canceled Reversal',
      '10' => 'Failed',
      '11' => 'Refunded',
      '12' => 'Reversed',
      '13' => 'Chargeback',
      '14' => 'Expired',
      '15' => 'Processed',
      '16' => 'Voided'
    ];

    return $statuses[$order_status_id];
  }

  protected function __isDateBeforeTenSeconds(string $dateString): bool
  {
    $currentDateTime = new DateTime();
    $givenDateTime = DateTime::createFromFormat("Y-m-d H:i:s", $dateString);

    $difference = $currentDateTime->getTimestamp() - $givenDateTime->getTimestamp();

    return ($difference <= 10);
  }
}
