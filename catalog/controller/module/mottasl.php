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
  public function sendMessage(string &$route, array &$args): void
  {
    error_log($route);

    $order_id = $args[0];
    $order_status_id = $args[1];

    $this->load->model('checkout/order');
    $order_data = $this->model_checkout_order->getOrder($order_id);

    $this->load->model('account/customer');
    $customer_data = $this->model_account_customer->getCustomer($order_data['customer_id']);

    $this->load->model('setting/setting');
    $settings = $this->model_setting_setting->getSetting('module_mottasl');

    if (!isset($settings['module_mottasl_api_key']) || !isset($settings['module_mottasl_template_id']) || !isset($settings['module_mottasl_template_lang'])) {
      $json = [];
      $json["error"] = "Some Mottal settings are not set.";
      $this->response->addHeader('Content-Type: application/json');
      $this->response->setOutput(json_encode($json));
    } else {
      $this->__sendMessageWithZoko($customer_data['telephone'], $customer_data['firstname'], $order_data['invoice_prefix'] . $order_data['invoice_no'], $this->__getOrderStatus($order_status_id), $settings);
    }
  }

  protected function __sendMessageWithZoko(string $customer_phone, string $customer_firstname, string $order_title, string $order_status, array $settings): void
  {
    $url = 'https://chat.zoko.io/v2/message';

    // The data you want to send via POST
    $fields = [
      'channel' => 'whatsapp',
      'recipient' => $customer_phone[0] == '+' ? substr($customer_phone, 1) : $customer_phone,
      'type' => 'template',
      'templateId' => $settings['module_mottasl_template_id'],
      'templateLanguage' => $settings['module_mottasl_template_lang'],
      'templateArgs' => [$customer_firstname, $order_title, $order_status]
    ];

    //url-ify the data for the POST
    $fields_string = json_encode($fields);

    $headers = array();
    $headers[] = 'Content-Type: application/json';
    $headers[] = 'Apikey: ' . $settings['module_mottasl_api_key'];
    // $headers[] = 'Apikey: 7f411978-8df5-4ea2-8a26-5fc8e6423f7f';

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

  public function uninstall(): void
  {
  }
}
