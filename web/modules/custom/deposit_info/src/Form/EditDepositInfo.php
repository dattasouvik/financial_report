<?php
namespace Drupal\deposit_info\Form;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\deposit_info\Controller\MailHandler;
use Drupal\Core\Session\AccountProxy;
/**
 * Implements InputDemo form controller.
 * This example demonstrates the different input elements that are used to
 * collect data in a form.
 */
class EditDepositInfo extends FormBase
{
  /**
     * The date formatter service.
     *
     * @var \Drupal\Core\Datetime\DateFormatterInterface
     */
  protected $dateFormatter;
  /**
     * The entity type manager.
     *
     * @var \Drupal\Core\Entity\EntityTypeManagerInterface
     */
  protected $entityTypeManager;
  /**
   * Send Mail Service
   *
   * @var \Drupal\deposit_info\Controller\MailHandler
   */
  protected $mailHandler;
  /**
   * Current account details service.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;
  /**
     * Constructs a new MenuDeleteForm.
     *
     * @param \Drupal\Core\Datetime\DateFormatterInterface   $date_formatter
     *   The date formatter service.
     * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
     *   The date formatter service.
     * @param \Drupal\deposit_info\Controller\MailHandler $mailHandler
     *   Send Mail Service.     *
     * @param \Drupal\Core\Session\AccountProxy $currentUser
     *   Current account details service.	 
     */
  public function __construct(DateFormatterInterface $date_formatter, EntityTypeManagerInterface $entity_manager,MailHandler $mailHandler,AccountProxy $currentUser)
  {
    $this->dateFormatter = $date_formatter;
    $this->entityTypeManager = $entity_manager;
    $this->mailHandler = $mailHandler;
    $this->currentUser = $currentUser;	
  }
  /**
     * {@inheritdoc}
     */
  public static function create(ContainerInterface $container)
  {
    return new static(
      $container->get('date.formatter'),
      $container->get('entity.manager'),
      $container->get('deposit_info.mail_handler'),
	  $container->get('current_user')
    );
  }
  /**
     * {@inheritdoc}
     */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL, $node_id = NULL)
  {
    $form['#attached']['library'][] = 'deposit_info/deposit_info.deposit_form';
    if (is_numeric($node_id)) {
      $deposit_data = $this->entityTypeManager->getStorage('node')->load($node_id);
    } else {
      // Redirection with drupal set message.
    }
    if (empty($deposit_data)) {
      // Redirection with drupal set message.
    }
    $form['errMsg'] = [
      '#type' => 'item',
      '#markup' => '<div id="errMsgStepOneDiv" class="errMsgStepOneDiv"></div>',
    ];
    $form['deposit_id'] = [
      '#type' => 'hidden',
      '#value' => $node_id,
    ];
    $form['account_details'] = [
      '#type' => 'details',
      '#title' => $this->t('Deposit Details'),
      '#open' => TRUE
    ];
    $form['account_details']['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Enter Account Number'),
      '#size' => 60,
      '#maxlength' => 128,
      '#value' => trim($deposit_data->field_account_number->value),
    ];
    $form['account_details']['deposit_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Enter Deposit Date'),
      '#date_date_format' => 'm/d/Y',
      '#default_value' => $deposit_data->field_deposit_date->value,
    ];
    $form['account_details']['maturity_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Enter Maturity Date'),
      '#date_date_format' => 'm/d/Y',
      '#default_value' => $deposit_data->field_maturity_date->value,
    ];
    $form['account_details']['interest'] = [
      '#type' => 'number',
      '#title' => $this->t('Enter Rate of Interest (%)'),
      '#description' => $this->t('Interest must lie between 0 and 100'),
      '#min' => 0,
      '#step' => 0.01,
      '#value' => $deposit_data->field_rate_of_interest->value,
    ];
    $form['finance_info'] = [
      '#type' => 'details',
      '#title' => $this->t('Financial Information')
    ];
    $form['finance_info']['deposit_amount'] = [
      '#type' => 'number',
      '#title' => $this->t('Enter Deposit Amount'),
      '#description' => $this->t('Deposit must be greater than 0'),
      '#min' => 0,
      '#step' => 0.01,
      '#value' => $deposit_data->field_deposit_amount->value,
    ];
    $form['finance_info']['maturity_amount'] = [
      '#type' => 'number',
      '#title' => $this->t('Enter Maturity Deposit'),
      '#description' => $this->t('Maturity Deposit must be greater than 0'),
      '#min' => 0,
      '#step' => 0.01,
      '#value' => $deposit_data->field_maturity_amount->value,
    ];
    $options = [
      'bank' => $this->t('Bank'),
      'post' => $this->t('Post')
    ];
    $selected_option = strtolower($deposit_data->field_deposit_methods->value);
    $form['finance_info']['deposit_select'] = [
      '#type' => 'radios',
      '#title' => $this->t('Select Deposit Location'),
      '#options' => $options,
      '#default_value' => $selected_option,
        // '#attributes' => [
        //define static name and id so we can easier select it
        // 'id' => 'select-colour',
        // 'name' => 'deposit_select',
        // ],
        //add the #states property to the radios
      '#states' => [
        'enabled' => [
            //enable the radios only if the custom color textbox is empty
          ':input[name="field_bank_deposit"]' => ['value' => ''],
          ':input[name="field_post_office_deposit"]' => ['value' => '']
        ],
      ],
    ];
    $form['finance_info']['selected_type'] = [
      '#type' => 'hidden',
      '#value' => $selected_option,
      '#attributes' => ['class' => ['deposit_selected_type']]
    ];
    $form['finance_info']['bank_scheme'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'taxonomy_term',
      '#selection_settings' => [
        'target_bundles' => [
          'bank_deposit_types'
        ],
      ],
      '#title' => t('Bank Deposit Types'),
      '#size' => 60,
      '#placeholder' => 'Enter Bank Deposit Type',
      '#attributes' => [
        'id' => 'bank-deposit',
        'name' => 'field_bank_deposit',
      ],
      '#states' => [
        //show this textfield only if the radio 'other' is selected above
        'visible' => [
          ':input[name="deposit_select"]' => ['value' => 'bank'],
        ],
      ],
    ];
    $form['finance_info']['bank_neme'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Enter Bank Name'),
      '#size' => 60,
      '#maxlength' => 128,
      '#value' => trim($deposit_data->field_bank_name->value),
      '#states' => [
        'visible' => [
          ':input[name="deposit_select"]' => ['value' => 'bank'],
        ],
      ]
    ];
    $form['finance_info']['post_office_scheme'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'taxonomy_term',
      '#selection_settings' => [
        'target_bundles' => [
          'post_office_deposit_types'
        ],
      ],
      '#title' => t('Post Office Deposit Types'),
      '#size' => '60',
      '#placeholder' => 'Enter Post Office Deposit Type',
      '#attributes' => [
        'id' => 'post-office-deposit',
        'name' => 'field_post_office_deposit',
      ],
      '#states' => [
        //show this textfield only if the radio 'other' is selected above
        'visible' => [
          ':input[name="deposit_select"]' => ['value' => 'post'],
        ],
      ],
    ];
    if ($selected_option == 'bank') {
      $form['finance_info']['bank_scheme']['#value'] = trim($deposit_data->field_deposit_type->value);
    } elseif ($selected_option == 'post') {
      $form['finance_info']['post_office_scheme']['#value'] = trim($deposit_data->field_deposit_type->value);
    }
        // Group submit handlers in an actions element with a key of "actions" so
        // that it gets styled correctly, and so that other modules may add actions
        // to the form.
    $form['actions'] = [
      '#type' => 'actions',
    ];
        // Add a submit button that handles the submission of the form.
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#attributes' => ['class' => ['btn btn-success']],
      '#ajax' => [
        'callback' => '::ajaxSubmitForm',
        'event' => 'click',
      ],
    ];

    $form['#theme'] = ['edit_deposit_form'];
    return $form;
  }
  /**
  * {@inheritdoc}
  */
  public function getFormId()
  {
    return 'deposit_info_edit_deposit_form';
  }
  /**
  * {@inheritdoc}
  */
  public function submitForm(array &$form, FormStateInterface $form_state) {}
  /**
  * Submit handler for ajax submit.
  */
  public function ajaxSubmitForm(array &$form, FormStateInterface $form_state, Request $request = NULL)
  {
    $error_msg = "";
    $response = new AjaxResponse();
    $deposit_date = $form_state->getUserInput()['deposit_date'];
    $maturity_date = $form_state->getUserInput()['maturity_date'];
    $deposit_amount = $form_state->getUserInput()['deposit_amount'];
    $maturity_amount = $form_state->getUserInput()['maturity_amount'];
    $deposit_select = $form_state->getUserInput()['selected_type'];
    $bank_scheme = $form_state->getUserInput()['field_bank_deposit'];
    $post_office_scheme = $form_state->getUserInput()['field_post_office_deposit'];
    $bank_neme = $form_state->getUserInput()['bank_neme'];
	$currentUserRoles = $this->currentUser->getRoles();
    // Validation.
    if (empty($form_state->getUserInput()['name'])) {
      $error_msg .= '<li>Please enter Account Number</li>';
    }
    if (empty($form_state->getUserInput()['deposit_date'])) {
      $error_msg .= '<li>Please enter Deposit Date</li>';
    }
    if (empty($form_state->getUserInput()['maturity_date'])) {
      $error_msg .= '<li>Please enter Maturity Date</li>';
    }
    if (empty($form_state->getUserInput()['interest'])) {
      $error_msg .= '<li>Please enter Rate of Interest</li>';
    }
    if (empty($form_state->getUserInput()['deposit_amount'])) {
      $error_msg .= '<li>Please enter deposit amount</li>';
    }
    if (empty($form_state->getUserInput()['maturity_amount'])) {
      $error_msg .= '<li>Please enter maturity amount</li>';
    }
    if ($deposit_amount >= $maturity_amount) {
      $error_msg .= '<li>Maturity Amount must be greater than Deposit Amount</li>';
    }
    if (strtotime($deposit_date) >= strtotime($maturity_date)) {
      $error_msg .= '<li>Maturity Date must be greater than Deposit Date</li>';
    }
    if ($deposit_select == 'bank' && empty($bank_scheme)) {
      $error_msg .= '<li>You must select any scheme offered by bank</li>';
    }
    if ($deposit_select == 'bank' && empty($bank_neme)) {
      $error_msg .= '<li>You must enter Bank Name</li>';
    }
    if ($deposit_select == 'post' && empty($post_office_scheme)) {
      $error_msg .= '<li>You must select any scheme offered by Post Office</li>';
    }
    $deposit_node = $this->entityTypeManager->getStorage('node')->load($form_state->getUserInput()['deposit_id']);
    if (empty($deposit_node)){
      $error_msg .= '<li>Deposit does not exists. Please refresh the page.</li>';
    }
    if (!empty($error_msg)) {
      $response->addCommand(new HtmlCommand('#errMsgStepOneDiv', '<ol class="msg-error messages messages--error alert alert-danger alert-dismissible fade show" role="alert">' . $error_msg . '</ol>'));
    } else {
        $duration = $this->durationCount($deposit_date, $maturity_date);
        $formatted_duration = $duration['formatted_value'];
        $duration_days = $duration['days'];
        $calc_annual_rate = $this->calculateAnnualInterest(
          $form_state->getUserInput()['deposit_amount'],
          $form_state->getUserInput()['maturity_amount'],
          $duration_days,
          $form_state->getUserInput()['interest']
        );
        $deposit_node->set("field_account_number", $form_state->getUserInput()['name']);
        $deposit_node->set("field_deposit_date", $form_state->getUserInput()['deposit_date']);
        $deposit_node->set("field_maturity_date", $form_state->getUserInput()['maturity_date']);
        $deposit_node->set("field_rate_of_interest", $form_state->getUserInput()['interest']);
        $deposit_node->set("field_deposit_methods", $deposit_select);
        $deposit_node->set("field_deposit_amount", $form_state->getUserInput()['deposit_amount']);
        $deposit_node->set("field_maturity_amount", $form_state->getUserInput()['maturity_amount']);
        $deposit_node->set("field_duration", $formatted_duration);
        $deposit_node->set("field_annual_interest_rate", $calc_annual_rate);
        // $deposit_node->set("field_status", 1);
        if (!empty($bank_scheme)) {
          $deposit_node->set('field_deposit_type', $bank_scheme);
          $deposit_node->set('field_bank_name', $form_state->getUserInput()['bank_neme']);
        }
        if (!empty($post_office_scheme)) {
          $deposit_node->set('field_deposit_type', $post_office_scheme);
        }
        $deposit_node->save();
        drupal_set_message($this->t('Deposit Id %number successfully updated',
        array('%number' => $deposit_node->field_account_number->value)));
		if (in_array('administrator', $currentUserRoles)) {
            $dest_url = $request->getBaseUrl() . "/admin-dashboard";
        }else{
			$dest_url = $request->getBaseUrl() . "/dashboard";
		}
        $response->addCommand(new RedirectCommand($dest_url));
        /* Sending Mail */
        $this->mailHandler->sendMail('edit', $deposit_node->id() );
    }
    return $response;
  }
  /*
  *Duration Count
  */
  public function durationCount($deposit_date, $maturity_date) : array
  {
    $duration = [];
    $formatted_deposit_date = $this->dateFormatter->format(strtotime($deposit_date), 'custom', 'Y-m-d');
    $formatted_maturity_date = $this->dateFormatter->format(strtotime($maturity_date), 'custom', 'Y-m-d');
    $date1 = date_create($formatted_deposit_date);
    $date2 = date_create($formatted_maturity_date);
    $diff = date_diff($date1, $date2);
    $duration['formatted_value'] = $diff->y . ' Years ' . $diff->m . ' Month ' . $diff->d . ' Days';
    $duration['days'] = $diff->days;
    return $duration;
  }
  /*
    *Calculate Annual Interest rate
    */
  public function calculateAnnualInterest(int $deposit, int $maturity, int $days, int $interest)
  {
      // Using SI = P * R * T / 100
    $annual_rate = (($maturity - $deposit) * 100) / ($deposit * ($days / 365));
    return $annual_rate;
  }
}
