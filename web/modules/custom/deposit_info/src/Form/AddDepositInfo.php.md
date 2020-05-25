<?php
namespace Drupal\deposit_info\Form;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\deposit_info\Controller\MailHandler;
use Drupal\Core\Session\AccountProxy;
/**
 * Implements InputDemo form controller.
 * This example demonstrates the different input elements that are used to
 * collect data in a form.
 */
class AddDepositInfo extends FormBase
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
   * The current user.
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
     *   Send Mail Service.
	 * @param \Drupal\Core\Session\AccountProxy $currentUser
	 *   Current account details service.
     */
  public function __construct(DateFormatterInterface $date_formatter, EntityTypeManagerInterface $entity_manager, MailHandler $mailHandler, AccountProxy $currentUser)
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
  public function buildForm(array $form, FormStateInterface $form_state)
  {
    $form['#attached']['library'][] = 'deposit_info/deposit_info.deposit_form';
    $form['account_details'] = [
      '#type' => 'details',
      '#title' => $this->t('Deposit Details')
    ];
    $form['account_details']['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Enter Account Number'),
      '#size' => 60,
      '#maxlength' => 128,
    ];
    $form['account_details']['deposit_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Enter Deposit Date'),
      '#date_date_format' => 'm/d/Y',
      '#required' => TRUE,
    ];
    $form['account_details']['maturity_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Enter Maturity Date'),
      '#date_date_format' => 'm/d/Y',
      '#required' => TRUE,
    ];
    $form['account_details']['interest'] = [
      '#type' => 'number',
      '#title' => $this->t('Enter Rate of Interest'),
      '#placeholder' => $this->t('Interest must lie between 0 and 100'),
      '#min' => 0,
      '#step' => 0.01,
      '#required' => TRUE,
    ];
    $form['finance_info'] = [
      '#type' => 'details',
      '#title' => $this->t('Financial Information')
    ];
    $form['finance_info']['deposit_amount'] = [
      '#type' => 'number',
      '#title' => $this->t('Enter Deposit'),
      '#description' => $this->t('Deposit must be greater than 0'),
      '#min' => 0,
      '#step' => 0.01,
      '#required' => TRUE,
    ];
    $form['finance_info']['maturity_amount'] = [
      '#type' => 'number',
      '#title' => $this->t('Enter Maturity Deposit'),
      '#description' => $this->t('Maturity Deposit must be greater than 0'),
      '#min' => 0,
      '#step' => 0.01,
      '#required' => TRUE,
    ];
    $options = [
      'bank' => $this->t('Bank'),
      'post' => $this->t('Post')
    ];
    $form['finance_info']['deposit_select'] = [
      '#type' => 'radios',
      '#title' => $this->t('Select Deposit Location'),
      '#options' => $options,
      '#default_value' => 'post',
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
      '#value' => 'post',
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
      '#description' => $this->t('Submit, #type = submit'),
    ];
        // Add a reset button that handles the submission of the form.
    $form['actions']['reset'] = [
      '#type' => 'button',
      '#button_type' => 'reset',
      '#value' => $this->t('Reset'),
      '#description' => $this->t('Submit, #type = button, #button_type = reset, #attributes = this.form.reset();return false'),
      '#attributes' => [
        'id' => 'reset_buuton',
        'onclick' => 'this.form.reset(); return false;',
      ],
    ];

    $form['#theme'] = ['add_deposit_form'];
    return $form;
  }
  /**
     * {@inheritdoc}
     */
  public function getFormId()
  {
    return 'deposit_info_add_deposit_form';
  }
  /**
     * {@inheritdoc}
     */
  public function validateForm(array &$form, FormStateInterface $form_state)
  {
    $deposit_date = $form_state->getUserInput()['deposit_date'];
    $maturity_date = $form_state->getUserInput()['maturity_date'];
    $deposit_amount = $form_state->getUserInput()['deposit_amount'];
    $maturity_amount = $form_state->getUserInput()['maturity_amount'];
    if ($deposit_amount >= $maturity_amount) {
      $form_state->setErrorByName('maturity_amount', $this->t('Maturity Amount must be greater than Deposit Amount'));
    }
    if (strtotime($deposit_date) >= strtotime($maturity_date)) {
      $form_state->setErrorByName('maturity_date', $this->t('Maturity Date must be greater than Deposit Date'));
    }
    $deposit_select = $form_state->getUserInput()['selected_type'];
    $bank_scheme = $form_state->getUserInput()['field_bank_deposit'];
    $post_office_scheme = $form_state->getUserInput()['field_post_office_deposit'];
    $bank_neme = $form_state->getUserInput()['bank_neme'];
    if ($deposit_select == 'bank' && empty($bank_scheme)) {
      $form_state->setErrorByName('field_bank_deposit', $this->t('You must select any scheme offered by bank'));
    }
    if ($deposit_select == 'bank' && empty($bank_neme)) {
      $form_state->setErrorByName('bank_neme', $this->t('You must enter Bank Name'));
    }
    if ($deposit_select == 'post' && empty($post_office_scheme)) {
      $form_state->setErrorByName('field_post_office_deposit', $this->t('You must select any scheme offered by Post Office'));
    }
  }
  /**
     * {@inheritdoc}
     */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $deposit_date = $form_state->getUserInput()['deposit_date'];
    $maturity_date = $form_state->getUserInput()['maturity_date'];
    $duration = $this->durationCount($deposit_date, $maturity_date);
    $formatted_duration = $duration['formatted_value'];
    $duration_days = $duration['days'];
        // echo $duration_days;
        // exit;
    $calc_annual_rate = $this->calculateAnnualInterest(
      $form_state->getUserInput()['deposit_amount'],
      $form_state->getUserInput()['maturity_amount'],
      $duration_days,
      $form_state->getUserInput()['interest']
    );
        // $deposit_select = $form_state->getValue('deposit_select');
    $deposit_select = $form_state->getUserInput()['selected_type'];
    if ($deposit_select == 'bank') {
      $deposit_select = 'bank';
    } elseif ($deposit_select == 'post') {
      $deposit_select = 'post';
    }
        // echo $deposit_select;
    $deposit_id = 'Deposit ID-' . (time() + mt_rand(0, 100000));
    $bank_scheme = $form_state->getUserInput()['field_bank_deposit'];
    $post_office_scheme = $form_state->getUserInput()['field_post_office_deposit'];
	$loggedin_user_id = $this->currentUser->id();
        // echo $bank_scheme;
        // echo $post_office_scheme;
        // exit;
    $deposit_creation_data = Node::create(
      [
        'type' => 'deposit',
        'title' => $deposit_id,
        'field_account_number' => [0 => ['value' => $form_state->getUserInput()['name']]],
        'field_deposit_date' => [0 => ['value' => $form_state->getUserInput()['deposit_date']]],
        'field_maturity_date' => [0 => ['value' => $form_state->getUserInput()['maturity_date']]],
        'field_rate_of_interest' => [0 => ['value' => $form_state->getUserInput()['interest']]],
        'field_deposit_methods' => [0 => ['value' => $deposit_select]],
        'field_deposit_amount' => [0 => ['value' => $form_state->getUserInput()['deposit_amount']]],
        'field_maturity_amount' => [0 => ['value' => $form_state->getUserInput()['maturity_amount']]],
        'field_duration' => [0 => ['value' => $formatted_duration]],
        'field_annual_interest_rate' => [0 => ['value' => $calc_annual_rate]],
		'field_created_by' => [0 => ['target_id' => $loggedin_user_id]],
        'field_status' => [0 => ['value' => 1]]
      ]
    );
    if (!empty($bank_scheme)) {
      $deposit_creation_data->set('field_deposit_type', $bank_scheme);
      $deposit_creation_data->set('field_bank_name', $form_state->getUserInput()['bank_neme']);
    }
    if (!empty($post_office_scheme)) {
      $deposit_creation_data->set('field_deposit_type', $post_office_scheme);
    }
    $deposit_creation_data->save();
    /* Sending Mail */
    $this->mailHandler->sendMail('new', $deposit_creation_data->id());
    $message = $this->t(
      'Data Submitted Successfully to System for Account Number - %account',
      [
        '%account' => $form_state->getUserInput()['name']
      ]
    );
    $this->messenger()->addMessage($message);
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
