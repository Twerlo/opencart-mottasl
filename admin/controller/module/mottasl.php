<?php

namespace Opencart\Admin\Controller\Extension\MottaslOc\Module;
// 7f411978-8df5-4ea2-8a26-5fc8e6423f7f
// order_status_notification_zid_store_plain
// en
class Mottasl extends \Opencart\System\Engine\Controller
{
  public function index(): void
  {
    $this->load->language('extension/mottasl_oc/module/mottasl');

    $this->document->setTitle($this->language->get('heading_title'));

    $data['breadcrumbs'] = [];

    $data['breadcrumbs'][] = [
      'text' => $this->language->get('text_home'),
      'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
    ];

    $data['breadcrumbs'][] = [
      'text' => $this->language->get('text_extension'),
      'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module')
    ];

    $data['breadcrumbs'][] = [
      'text' => $this->language->get('heading_title'),
      'href' => $this->url->link('extension/mottasl_oc/module/mottasl', 'user_token=' . $this->session->data['user_token'])
    ];

    $data['save'] = $this->url->link('extension/mottasl_oc/module/mottasl.save', 'user_token=' . $this->session->data['user_token']);
    $data['back'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module');

    $data['header'] = $this->load->controller('common/header');
    $data['column_left'] = $this->load->controller('common/column_left');
    $data['footer'] = $this->load->controller('common/footer');

    $this->load->model('setting/setting');
    $settings = $this->model_setting_setting->getSetting('module_mottasl');

    $data['api_key'] = isset($settings['module_mottasl_api_key']) ? $settings['module_mottasl_api_key'] : '';

    $data['customer_created_status'] = isset($settings['module_mottasl_customer_created_status']) ? $settings['module_mottasl_customer_created_status'] : '';
    $data['customer_created_temp_id'] = isset($settings['module_mottasl_customer_created_temp_id']) ? $settings['module_mottasl_customer_created_temp_id'] : '';
    $data['customer_created_temp_lang'] = isset($settings['module_mottasl_customer_created_temp_lang']) ? $settings['module_mottasl_customer_created_temp_lang'] : '';

    $data['order_created_status'] = isset($settings['module_mottasl_order_created_status']) ? $settings['module_mottasl_order_created_status'] : '';
    $data['order_created_temp_id'] = isset($settings['module_mottasl_order_created_temp_id']) ? $settings['module_mottasl_order_created_temp_id'] : '';
    $data['order_created_temp_lang'] = isset($settings['module_mottasl_order_created_temp_lang']) ? $settings['module_mottasl_order_created_temp_lang'] : '';

    $data['order_updated_status'] = isset($settings['module_mottasl_order_updated_status']) ? $settings['module_mottasl_order_updated_status'] : '';
    $data['order_updated_temp_id'] = isset($settings['module_mottasl_order_updated_temp_id']) ? $settings['module_mottasl_order_updated_temp_id'] : '';
    $data['order_updated_temp_lang'] = isset($settings['module_mottasl_order_updated_temp_lang']) ? $settings['module_mottasl_order_updated_temp_lang'] : '';

    $data['abandon_first_status'] = isset($settings['module_mottasl_abandon_first_status']) ? $settings['module_mottasl_abandon_first_status'] : '';
    $data['abandon_first_temp_id'] = isset($settings['module_mottasl_abandon_first_temp_id']) ? $settings['module_mottasl_abandon_first_temp_id'] : '';
    $data['abandon_first_temp_lang'] = isset($settings['module_mottasl_abandon_first_temp_lang']) ? $settings['module_mottasl_abandon_first_temp_lang'] : '';

    $data['abandon_second_status'] = isset($settings['module_mottasl_abandon_second_status']) ? $settings['module_mottasl_abandon_second_status'] : '';
    $data['abandon_second_temp_id'] = isset($settings['module_mottasl_abandon_second_temp_id']) ? $settings['module_mottasl_abandon_second_temp_id'] : '';
    $data['abandon_second_temp_lang'] = isset($settings['module_mottasl_abandon_second_temp_lang']) ? $settings['module_mottasl_abandon_second_temp_lang'] : '';

    $data['template_id'] = isset($settings['module_mottasl_template_id']) ? $settings['module_mottasl_template_id'] : '';
    $data['template_lang'] = isset($settings['module_mottasl_template_lang']) ? $settings['module_mottasl_template_lang'] : '';

    $this->response->setOutput($this->load->view('extension/mottasl_oc/module/mottasl', $data));
  }

  public function save(): void
  {
    $this->load->language('extension/mottasl_oc/module/mottasl');

    $json = [];

    if (!$this->user->hasPermission('modify', 'extension/mottasl_oc/module/mottasl')) {
      $json['error'] = $this->language->get('error_permission');
    }

    $new_settings = [
      'module_mottasl_api_key' => $this->request->post['api_key'],

      'module_mottasl_customer_created_status' => $this->request->post['customer_created_status'],
      'module_mottasl_customer_created_temp_id' => $this->request->post['customer_created_temp_id'],
      'module_mottasl_customer_created_temp_lang' => $this->request->post['customer_created_temp_lang'],

      'module_mottasl_order_created_status' => $this->request->post['order_created_status'],
      'module_mottasl_order_created_temp_id' => $this->request->post['order_created_temp_id'],
      'module_mottasl_order_created_temp_lang' => $this->request->post['order_created_temp_lang'],

      'module_mottasl_order_updated_status' => $this->request->post['order_updated_status'],
      'module_mottasl_order_updated_temp_id' => $this->request->post['order_updated_temp_id'],
      'module_mottasl_order_updated_temp_lang' => $this->request->post['order_updated_temp_lang'],

      'module_mottasl_abandon_first_status' => $this->request->post['abandon_first_status'],
      'module_mottasl_abandon_first_temp_id' => $this->request->post['abandon_first_temp_id'],
      'module_mottasl_abandon_first_temp_lang' => $this->request->post['abandon_first_temp_lang'],

      'module_mottasl_abandon_second_status' => $this->request->post['abandon_second_status'],
      'module_mottasl_abandon_second_temp_id' => $this->request->post['abandon_second_temp_id'],
      'module_mottasl_abandon_second_temp_lang' => $this->request->post['abandon_second_temp_lang'],

      'module_mottasl_template_id' => $this->request->post['template_id'],
      'module_mottasl_template_lang' => $this->request->post['template_lang'],
      'module_mottasl_status' => ((bool)$this->request->post['api_key'] &&
        ((bool)$this->request->post['customer_created_status'] ||
          (bool)$this->request->post['order_created_status'] ||
          (bool)$this->request->post['order_updated_status'] ||
          (bool)$this->request->post['abandon_first_status'] ||
          (bool)$this->request->post['abandon_second_status'])
      )
    ];

    if (!$json) {
      $this->load->model('setting/setting');

      $this->model_setting_setting->editSetting('module_mottasl', $new_settings);

      $json['success'] = $this->language->get('text_success');
    }

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }

  public function install()
  {
    // registering events to show menu
    $this->__registerEvents();
  }
  /**
   * __registerEvents
   *
   * @return void
   */
  protected function __registerEvents()
  {
    // events array
    $events   = array();
    $events[] = array(
      'code'        => "mottasl_order_add",
      'trigger'     => "catalog/model/checkout/order/addHistory/before",
      'action'      => "extension/mottasl_oc/module/mottasl.sendMessage",
      'description' => "Customer Account Menu",
      'status'      => 1,
      'sort_order'  => 0,
    );

    // loading event model
    $this->load->model('setting/event');
    foreach ($events as $event) {
      // registering events in DB
      $this->model_setting_event->addEvent($event);
    }
  }

  public function uninstall(): void
  {
  }
}
