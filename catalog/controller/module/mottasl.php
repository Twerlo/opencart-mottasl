<?php

namespace Opencart\Catalog\Controller\Extension\MottaslOc\Module;

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
      $json = [];
      $json["error"] = "Some Mottal settings are not set.";
      $this->response->addHeader('Content-Type: application/json');
      $this->response->setOutput(json_encode($json));
    } else {
      $this->__sendMessageWithZoko($customer_data['telephone'], $customer_data['firstname'], $order_data['invoice_prefix'] . $order_data['invoice_no'], $this->__getOrderStatus($order_status_id), $settings);
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
