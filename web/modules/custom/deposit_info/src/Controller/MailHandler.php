<?php

namespace Drupal\deposit_info\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
use Drupal\Core\Session\AccountProxy;

/**
 * Our namelist controller.
 */
class MailHandler extends ControllerBase {


  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;
  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * Constructs a new MenuDeleteForm.
   *
   * @param Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager object.
   * @param \Drupal\Core\Session\AccountProxy $currentUser
   *   Current account details service.
   */

  public function __construct(EntityManagerInterface $entity_manager, AccountProxy $currentUser) {
    $this->entityManager = $entity_manager;
	$this->currentUser = $currentUser;
  }



  /**
   * {@inheritdoc}
   */

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
	  $container->get('current_user')
    );
  }
  /**
   * Send Mail
   */
  public function sendMail($category, $node_id) {
    $mail = new PHPMailer(true);

    if(empty($category) || !is_numeric($node_id)){
      \Drupal::logger('send-mail')->error("Invalid Input Detected");
      return FALSE;
    }
    $node_data = $this->entityManager->getStorage('node')->load($node_id);
	$loggedin_user_id = $this->currentUser->id();
    $user_data = $this->entityManager->getStorage('user')->load($loggedin_user_id);
    if (!empty($node_data)) {
      $account_number = trim($node_data->field_account_number->value);
      $account_number = $this->maskInput($account_number);
      $maturity_date = $node_data->field_maturity_date->value;
    }
    else{
      \Drupal::logger('send-mail')->error("Node not found.");
      drupal_set_message('Unable to send mail as requested account not found','error');
      return FALSE;
    }
    if (!empty($user_data)) {
      $recipient_email = $user_data->mail->value;
    }
    else{
      \Drupal::logger('send-mail')->error("Recipient Email not found.");
      drupal_set_message('Unable to send mail as requested Recipient Email not found','error');
      return FALSE;
    }
    // Get Configurational Details
    $config = $this->config('deposit_info.mail_settings');
    $smtp_host = $config->get('smtp_host');
    $smtp_port = $config->get('smtp_port');
    $display_username = $config->get('display_username');
    $smtp_username = $config->get('smtp_username');
    $smtp_password = $config->get('smtp_password');
    $email_header = $config->get('email_header');
    $email_footer = $config->get('email_footer');

    if(empty($smtp_host) || empty($smtp_port) || empty($display_username) || empty($smtp_password) || empty($recipient_email) || empty($email_header) || empty($email_footer) ){
      \Drupal::logger('send-mail')->error("Configuration Error");
      return FALSE;
    }
    try {
        //Server settings
        // $mail->SMTPDebug = SMTP::DEBUG_SERVER;                      // Enable verbose debug output
        $mail->isSMTP();                                            // Send using SMTP
        $mail->Host       = trim($smtp_host);                   // Set the SMTP server to send through
        $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
        $mail->Username   = $smtp_username;                     // SMTP username
        $mail->Password   = $smtp_password;                               // SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;         // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` also accepted
        $mail->Port       = $smtp_port;                                    // TCP port to connect to

        //Recipients
        $mail->setFrom($smtp_username, trim($display_username));
        $mail->addAddress($recipient_email);     // Add a recipient

        // Content
        // Set email format to HTML
        $mail->isHTML(true);
        if($category == 'edit'){
          $mail->Subject = 'Deposit no:- '.$account_number.' is updated';
          $body = $email_header;
          $body .= '<br><b>'.$account_number.'</b> is updated!!!';
          $body .= $email_footer;
          $mail->Body    = $body;
          $mail->AltBody = 'Deposit no:- '.$account_number.'is successfully updated';
        }
        elseif($category == 'new'){
          $mail->Subject = 'Deposit no:- '.$account_number.' is created';
          $body = $email_header;
          $body .= '<br><b>'.$account_number.'</b> is created with maturity on '.$maturity_date;
          $body .= $email_footer;
          $mail->Body    = $body;
          $mail->AltBody = 'Deposit no:- '.$account_number.'is successfully created';
        }
        elseif($category == 'archived'){
          $mail->Subject = 'Deposit no:- '.$account_number.' is archived';
          $body = $email_header;
          $body .= '<br><b>'.$account_number.'</b> is archived with maturity on '.$maturity_date;
          $body .= $email_footer;
          $mail->Body    = $body;
          $mail->AltBody = 'Deposit no:- '.$account_number.'is successfully archived';
        }
        else{
          $mail->Subject = 'Here is the subject';
          $mail->Body    = 'This is the HTML message body <b>in bold!</b> Now';
          $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';
        }

        $mail->send();
        drupal_set_message('Message has been sent');
        return TRUE;
    } catch (Exception $e) {
        \Drupal::logger('send-mail')->error("Mail not Sent");
        drupal_set_message('Unable to send mail','error');
        return $e->getMessage();
    }
  }
  /**
   * Notify Mail for Login
   */
  public function notifyMail($category, $user_id, $reset_url = '') {
    $mail = new PHPMailer(true);

    if(empty($category) || !is_numeric($user_id)){
      \Drupal::logger('notify-mail')->error("Invalid Input Detected for Notify Mail");
      return FALSE;
    }
    $user_data = $this->entityManager->getStorage('user')->load($user_id);
    if (!empty($user_data)) {
      $recipient_email = $user_data->mail->value;
      $recipient_name = trim($user_data->field_full_name->value);
    }
    else{
      \Drupal::logger('notify-mail')->error("Recipient Email not found.");
      drupal_set_message('Unable to send mail as requested Recipient Email not found','error');
      return FALSE;
    }
    // Get Configurational Details
    $config = $this->config('deposit_info.mail_settings');
    $smtp_host = $config->get('smtp_host');
    $smtp_port = $config->get('smtp_port');
    $display_username = $config->get('display_username');
    $smtp_username = $config->get('smtp_username');
    $smtp_password = $config->get('smtp_password');
    $email_header = $config->get('email_header');
    $email_footer = $config->get('email_footer');

    if(empty($smtp_host) || empty($smtp_port) || empty($display_username) || empty($smtp_password) || empty($recipient_email) || empty($email_header) || empty($email_footer) ){
      \Drupal::logger('send-mail')->error("Configuration Error");
      return FALSE;
    }
    try {
        //Server settings
        // $mail->SMTPDebug = SMTP::DEBUG_SERVER;                      // Enable verbose debug output
        $mail->isSMTP();                                            // Send using SMTP
        $mail->Host       = trim($smtp_host);                   // Set the SMTP server to send through
        $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
        $mail->Username   = $smtp_username;                     // SMTP username
        $mail->Password   = $smtp_password;                               // SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;         // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` also accepted
        $mail->Port       = $smtp_port;                                    // TCP port to connect to

        //Recipients
        $mail->setFrom($smtp_username, trim($display_username));
        $mail->addAddress($recipient_email);     // Add a recipient

        // Content
        // Set email format to HTML
        $mail->isHTML(true);
        if($category == 'alert-user'){
          $mail->Subject = 'Please confirm your login on '.date('d/m/Y h:i:s A', time());
          $body = $email_header;
          $body .= '<br><b> (You)'.$recipient_name.'</b> has just looged in our portal';
          $body .= '<br><p><a style="color: #FF9800;" href ="'.$reset_url.'" target="_blank">Reset Your Password if you have not Logged In</a></p>';
          $body .= $email_footer;
          $mail->Body    = '<div style="border-radius:5px;border-top-style:solid;border-top-color:#ee6600;border-top-width:5px;color: #fff;border-bottom-left-radius:0px;border-bottom-right-radius:0px;background: linear-gradient(45deg, rgba(2,0,36,1) 0%, rgba(9,121,118,1) 35%, rgba(0,212,255,1) 100%);margin: 0 25%;padding:20px">'.$body.'<div>';
          $mail->AltBody = $recipient_name.'has just looged in';
        }
        else{
          $mail->Subject = 'Here is the subject';
          $mail->Body    = 'This is the HTML message body <b>in bold!</b> Now';
          $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';
        }

        $mail->send();
        $message = 'Mail sent on '.date('d/m/Y h:i:s A', time());
        \Drupal::logger('notify-mail')->info($message);
        return TRUE;
    } catch (Exception $e) {
        \Drupal::logger('notify-mail')->error("Mail not Sent");
        return $e->getMessage();
    }
  }
  public function maskInput(string $account_number){
    $mask_number =  str_repeat("X", strlen($account_number)-4) . substr($account_number, -4);
    return $mask_number;
  }
}
