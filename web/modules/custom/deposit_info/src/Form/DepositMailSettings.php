<?php
namespace Drupal\deposit_info\Form;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
/**
* Configure user settings for this site.
*
* @internal
*/
class DepositMailSettings extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'deposit_info_mail_settings';
  }
  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'deposit_info.mail_settings',
    ];
  }
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // $form = parent::buildForm($form, $form_state);.
    $config = $this->config('deposit_info.mail_settings');
    $form['smtp_host'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Enter SMTP Host Address'),
      '#default_value' => $config->get('smtp_host'),
      '#description' => $this->t('SMTP server:- smtp.gmail.com'),
      '#required' => TRUE,
    ];
    $form['smtp_port'] = [
      '#type' => 'number',
      '#title' => $this->t('Enter SMTP Port Number'),
      '#default_value' => $config->get('smtp_port'),
      '#description' => $this->t('Add Port 587 for Gmail'),
      '#required' => TRUE,
    ];
    $form['display_username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Enter Display Name'),
      '#default_value' => $config->get('display_username'),
      '#description' => $this->t('Display Name Along with Email'),
      '#required' => TRUE,
    ];
    $form['smtp_username'] = [
      '#type' => 'email',
      '#title' => $this->t('Enter Host Username'),
      '#default_value' => $config->get('smtp_username'),
      '#description' => $this->t('SMTP username'),
      '#required' => TRUE,
    ];
    $form['smtp_password'] = [
      '#type' => 'password',
      '#title' => $this->t('Enter SMTP password'),
      '#default_value' => $config->get('smtp_password'),
      '#description' => $this->t('SMTP password'),
      '#required' => TRUE,
    ];
    $form['email_header'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Enter Email Header Section'),
      '#default_value' => $config->get('email_header'),
      '#required' => TRUE,
    ];
    $form['email_footer'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Enter Email Footer Section'),
      '#default_value' => $config->get('email_footer'),
      '#required' => TRUE,
    ];
    return parent::buildForm($form, $form_state);
  }
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('deposit_info.mail_settings')
      ->set('smtp_host', $form_state->getValue('smtp_host'))
      ->set('smtp_port', $form_state->getValue('smtp_port'))
      ->set('display_username', $form_state->getValue('display_username'))
      ->set('smtp_username', $form_state->getValue('smtp_username'))
      ->set('smtp_password', $form_state->getValue('smtp_password'))
      ->set('email_header', $form_state->getValue('email_header'))
      ->set('email_footer', $form_state->getValue('email_footer'))
      ->save();
    parent::submitForm($form, $form_state);
  }
}
