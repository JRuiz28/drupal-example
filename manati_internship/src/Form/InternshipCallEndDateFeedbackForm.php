<?php

namespace Drupal\manati_internship\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeInterface;
use Drupal\node\Entity\Node;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Provides a Manati Internship form.
 */
class InternshipCallEndDateFeedbackForm extends InterestedInternshipFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'manati_internship_internship_call_end_date_feedback_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, NodeInterface $node = NULL) {
    $uid = $this->currentUser()->id();
    $today = new DrupalDateTime('today', DateTimeItemInterface::STORAGE_TIMEZONE);
    $today->format(DateTimeItemInterface::DATE_STORAGE_FORMAT);

    if ($node && $node->getOwnerId() === $uid && $node->field_call_end_date->value < $today->format(DateTimeItemInterface::DATE_STORAGE_FORMAT) && !$node->field_call_end_date_completed->value) {

      $form['user_id'] = [
        '#type' => 'value',
        '#value' => $uid,
      ];

      $form['node_id'] = [
        '#type' => 'value',
        '#value' => $node->id(),
      ];

      $form['title'] = [
        '#type' => 'markup',
        '#markup' => '<h2> Pasantía: ' . $node->label() . '</h2>',
      ];

      $form['call_end_date_information'] = [
        '#title' => $this->t('¿Cuál es el estado de la pasantía?'),
        '#type' => 'select',
        '#options' => [
          1 => $this->t('Quedó vacante'),
          2 => $this->t('La llené con alguien externo'),
          3 => $this->t('La llené con usuarios de este sitio'),
          4 => $this->t('Se canceló la pasantía'),
        ],
        '#description' => $this->t('Indique cuál es el estado de la pasantía ahora que finalizó la convocatoria.'),
        '#required' => TRUE,
        '#default_value' => NULL,
      ];

      $form['option_vacant'] = [
        '#title' => $this->t('¿Por qué quedó vacante?'),
        '#type' => 'textfield',
        '#description' => $this->t('Por favor indique la razón por la que la pasantía quedó vacante.'),
        '#states' => [
          'visible' => [
            ':input[name="call_end_date_information"]' => ['value' => '1'],
          ],
        ],
      ];

      $available_interns = $this->getAvailableInterns($node);
      // Load option interns.
      $interns_options = [];
      foreach ($available_interns as $intern) {
        $interns_options[$intern->id()] = $intern->field_name->value . ' ' . $intern->field_first_name->value . ' ' . $intern->field_last_name->value;

        // Get info need to API.
        $form['cid_' . $intern->id()] = [
          '#type' => 'value',
          '#value' => $intern->field_id_cinde->value,
        ];
        $form['tid_hours_available_' . $intern->id()] = [
          '#type' => 'value',
          '#value' => $intern->field_number_hours_available->target_id,
        ];
        $form['tid_current_situation_' . $intern->id()] = [
          '#type' => 'value',
          '#value' => $intern->field_current_situation->target_id,
        ];
        $form['tid_period_year_' . $intern->id()] = [
          '#type' => 'value',
          '#value' => $intern->field_period_year->target_id,
        ];
      }

      $form['option_user_selected'] = [
        '#title' => $this->t('¿Cuáles pasantes fueron seleccionados?'),
        '#type' => 'checkboxes',
        '#options' => $interns_options,
        '#description' => $this->t('Indique los pasantes que fueron escogidos para esta pasantía. Puede seleccionar más de uno.'),
        '#default_value' => [],
        '#multiple' => TRUE,
        '#states' => [
          'visible' => [
            ':input[name="call_end_date_information"]' => ['value' => '3'],
          ],
        ],
      ];

      $form['actions'] = [
        '#type' => 'actions',
      ];

      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Guardar'),
        '#button_type' => 'primary',
      ];
    }
    else {
      // Redirect to the front page.
      $response = new RedirectResponse('<front>', 302);
      return $response;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Get option the call end date info.
    $call_end_info_option = $form_state->getValue("call_end_date_information");

    // Validate when option is 1.
    if ($call_end_info_option === '1' && (empty($form_state->getValue("option_vacant")) || trim($form_state->getValue('option_vacant')) === '')) {
      $form_state->setErrorByName('option_vacant', $this->t('Debe indicar la razón por la cuál no se llenó la pasantía.'));
    }

    // Validate when option is 3.
    if ($call_end_info_option === '3' && !array_filter($form_state->getValue('option_user_selected'))) {
      // Check if any user was selected.
      $form_state->setErrorByName('option_user_selected', $this->t('Debe seleccionar al menos un pasante.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get ID node internship.
    $nid = $form_state->getValue('node_id');
    // Get ID user company.
    $uid = $this->currentUser()->id();
    /** @var \Drupal\node\NodeInterface $node */
    $node = $this->entityTypeManager->getStorage('node')->load($nid);
    /** @var \Drupal\user\UserInterface $user */
    $user = $this->entityTypeManager->getStorage('user')->load($uid);

    // Notification when cancelled internship.
    $call_end_info_option = $form_state->getValue("call_end_date_information");
    if ($call_end_info_option === '4') {
      $available_interns = $this->getAvailableInterns($node);
      foreach ($available_interns as $intern) {
        $this->notificationCancelledInternship($node, $uid, $intern->getEmail());
      }
    }

    // Save question why it is not filled.
    if ($form_state->getValue("call_end_date_information") === '1' && $form_state->getValue("option_vacant")) {
      $node->set('field_question_why_not_filled', $form_state->getValue("option_vacant"));
    }

    if ($form_state->getValue("call_end_date_information") === '3' && $form_state->getValue("option_user_selected")) {
      // Get users ID selected.
      foreach ($form_state->getValue("option_user_selected") as $key => $value) {
        if ($value != 0) {
          $node->field_interns_selected[] = [
            'target_id' => $value,
          ];
        }
        else {
          $node->field_interns_rejected[] = [
            'target_id' => $key,
          ];
          if ($call_end_info_option !== '4') {
            $this->notificationRejectedIntern($node, $key);
          }

          // Send information to API Cinde. Only user not selected.
          $cid_user = $form_state->getValue('cid_' . $key);
          $cid_internship = $node->field_id_cinde->value;
          $id_event = $this->config('manati_cinde_backend_api.settings')->get('id_event');
          $tid_hours_available = $form_state->getValue('tid_hours_available_' . $key);
          $tid_current_situation = $form_state->getValue('tid_current_situation_' . $key);
          $tid_period_year = $form_state->getValue('tid_period_year_' . $key);
          $result = 'descartado';

          $this->interestedInternshipReplace($cid_user, $cid_internship, $id_event, $tid_hours_available, $tid_current_situation, $result, $tid_period_year);
        }
      }
    }
    // Update API. All interns have been rejected.
    elseif ($call_end_info_option !== '3') {
      foreach ($form_state->getValue("option_user_selected") as $key => $value) {
        if ($value == 0) {
          $cid_user = $form_state->getValue('cid_' . $key);
          $cid_internship = $node->field_id_cinde->value;
          $id_event = $this->config('manati_cinde_backend_api.settings')->get('id_event');
          $tid_hours_available = $form_state->getValue('tid_hours_available_' . $key);
          $tid_current_situation = $form_state->getValue('tid_current_situation_' . $key);
          $tid_period_year = $form_state->getValue('tid_period_year_' . $key);
          $result = 'descartado';

          $this->interestedInternshipReplace($cid_user, $cid_internship, $id_event, $tid_hours_available, $tid_current_situation, $result, $tid_period_year);
        }
      }
    }

    // Remove internship locked.
    if ($uid && !$user->field_call_end_date_interships->isEmpty()) {
      // Get internship locked.
      $node_locked = [];
      foreach ($user->field_call_end_date_interships as $internship) {
        // Unlocked internship current, get locked.
        if ($internship->target_id !== $nid) {
          $node_locked[] = $internship->target_id;
        }
      }
      $user->set('field_call_end_date_interships', $node_locked);
      $user->save();
    }
    $node->set('field_call_end_date_info', (int) $call_end_info_option);
    $node->set('field_call_end_date_completed', 1);
    // Disable the internships if it wasn't filled with site users.
    if ($call_end_info_option !== '3') {
      $node->set('field_enabled', 0);
    }
    $node->save();

    // Notification.
    $this->messenger()->addStatus('La información de ' . $node->label() . ' ha sido guardada.');

    // Redirect to home or view locked.
    $form_state->setRedirect('<front>');
  }

  /**
   * Function of sending mail to rejected users.
   *
   * @param \Drupal\node\Entity\Node $node
   *   The node internship.
   *
   * @return array
   *   All available interns.
   */
  private function getAvailableInterns(Node $node) {
    // Get all interns.
    $all_interns = $node->get('field_interns_all')->referencedEntities();

    // Get interns rejected.
    $rejected_interns = $node->get('field_interns_rejected')->referencedEntities();

    // Exclude interns rejected into interns all.
    $available_interns = [];
    foreach ($all_interns as $intern) {
      if (!in_array($intern, $rejected_interns, TRUE)) {
        $available_interns[] = $intern;
      }
    }

    return $available_interns;
  }

  /**
   * Function of sending mail to cancelled internship.
   *
   * @param \Drupal\node\Entity\Node $node
   *   The node internship.
   * @param string $company_id
   *   The user id.
   * @param string $mail_intern
   *   The mail intern.
   */
  private function notificationCancelledInternship(Node $node, string $company_id, string $mail_intern) {
    /** @var \Drupal\user\Entity\User $company */
    $company = $this->entityTypeManager->getStorage('user')->load($company_id);

    $module = 'manati_notification';
    $key = 'cancelled_internship';
    $to = $mail_intern;
    $params['company_name'] = $company->field_name->value;
    $params['company_nid'] = $company_id;
    $params['internship_name'] = $node->label();
    $params['internship_nid'] = $node->id();
    $langcode = $this->currentUser()->getPreferredLangcode();
    $send = TRUE;

    $this->mailManager->mail($module, $key, $to, $langcode, $params, NULL, $send);
  }

  /**
   * Function of sending mail to rejected users.
   *
   * @param \Drupal\node\Entity\Node $node
   *   The node internship.
   * @param string $uid
   *   The user id.
   */
  private function notificationRejectedIntern(Node $node, string $uid) {
    /** @var \Drupal\user\Entity\User $user */
    $user = $this->entityTypeManager->getStorage('user')->load($uid);

    $module = 'manati_notification';
    $key = 'rejected_intern';
    $to = $user->getEmail();
    $params['intern_name'] = $user->field_name->value . ' ' . $user->field_first_name->value . ' ' . $user->field_last_name->value;
    $params['internship_name'] = $node->label();
    $params['internship_nid'] = $node->id();
    $langcode = $this->currentUser()->getPreferredLangcode();
    $send = TRUE;

    $this->mailManager->mail($module, $key, $to, $langcode, $params, NULL, $send);
  }

}
