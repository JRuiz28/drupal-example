<?php

namespace Drupal\manati_internship\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\RedirectCommand;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Manati Internship form.
 */
class SnoozeCompanyForm extends FormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Current form step.
   *
   * @var int
   */
  protected $step = 1;

  /**
   * Class constructor.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
      // Load the service required to construct this class.
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'manati_internship_snooze_company_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get ID user company.
    $uid = $this->currentUser()->id();
    /** @var \Drupal\user\UserInterface $user */
    $user = $this->entityTypeManager->getStorage('user')->load($uid);

    if ($user->field_snooze_date_end->isEmpty()) {
      $form['#id'] = $this->getFormId();
      // Generate a unique wrapper HTML ID.
      $form['#prefix'] = '<div id="' . $this->getFormId() . '">';
      $form['#suffix'] = '</div>';

      if ($this->step === 1) {
        $form['actions']['submit'] = [
          '#type' => 'submit',
          '#value' => $this->t('Posponer respuesta'),
          '#button_type' => 'primary',
          '#ajax' => [
            'wrapper' => $this->getFormId(),
            'callback' => '::ajaxCallback',
            'event' => 'click',
            'disable-refocus' => TRUE,
          ],
        ];
      }
      // Confirm to snooze block.
      elseif ($this->step === 2) {
        $form['message'] = [
          '#type' => 'markup',
          '#markup' => '<p class="result_message"><strong>¿Confirma que quiere posponer la respuesta?</strong><br /> Al confirmar, se desbloquea la cuenta por un mes. Sin embargo, una vez cumplido este tiempo,<br /> la cuenta se bloquea nuevamente y la única manera de desbloquearla es completando estos formularios.</p>',
        ];
        $form['actions']['confirm'] = [
          '#type' => 'submit',
          '#value' => $this->t('Confirmar'),
          '#ajax' => [
            'wrapper' => $this->getFormId(),
            'callback' => '::saveSubmitForm',
            'event' => 'click',
            'disable-refocus' => TRUE,
          ],
        ];

        $form['actions']['cancel'] = [
          '#type' => 'submit',
          '#value' => $this->t('Cancelar'),
          '#submit' => ['::cancelSubmitForm'],
          '#limit_validation_errors' => [],
          '#ajax' => [
            'wrapper' => $this->getFormId(),
            'callback' => '::ajaxCallback',
            'event' => 'click',
            'disable-refocus' => TRUE,
          ],
        ];
      }
    }
    else {
      $form['actions']['submit'] = [
        '#value' => $this->t('Posponer respuesta'),
        '#type' => 'submit',
        '#disabled' => TRUE,
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild();
    $this->step++;
  }

  /**
   * Cancel submit form.
   */
  public function cancelSubmitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild();
    $this->step--;
  }

  /**
   * Save submit form.
   */
  public function saveSubmitForm(array &$form, FormStateInterface $form_state) {
    // Get ID user company.
    $uid = $this->currentUser()->id();
    /** @var \Drupal\user\UserInterface $user */
    $user = $this->entityTypeManager->getStorage('user')->load($uid);

    $today = new DrupalDateTime('+1 month');
    $user->set('field_snooze_date_end', $today->format(DateTimeItemInterface::DATE_STORAGE_FORMAT));
    $user->save();

    $response = new AjaxResponse();
    $command = new RedirectCommand('/');
    return $response->addCommand($command);
  }

  /**
   * Ajax callback.
   */
  public function ajaxCallback(array &$form, FormStateInterface $form_state) {
    return $form;
  }

}
