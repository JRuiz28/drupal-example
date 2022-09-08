<?php

namespace Drupal\manati_internship\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\manati_cinde_backend_api\Api\PersonaInteresPasantia;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Provides a Manati Internship form.
 */
class InterestedInternshipFormBase extends FormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * The connection API CINDE services.
   *
   * @var \Drupal\manati_cinde_backend_api\Api\PersonaInteresPasantia
   */
  protected $personInterestedInternship;

  /**
   * The Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * Class constructor.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, MailManagerInterface $mail_manager, PersonaInteresPasantia $personInterestedInternship, ConfigFactoryInterface $config) {
    $this->entityTypeManager = $entity_type_manager;
    $this->mailManager = $mail_manager;
    $this->personInterestedInternship = $personInterestedInternship;
    $this->config = $config;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
      // Load the service required to construct this class.
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.mail'),
      $container->get('manati_cinde_backend_api.persona_interes_pasantia'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'manati_internship_custom_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

  /**
   * Sending information to API CINDE when replace person interested internship.
   *
   * @param string $cid_user
   *   The cinde user id.
   * @param string $cid_internship
   *   The cinde node id.
   * @param string $id_event
   *   The id event.
   * @param string $tid_hours_available
   *   The term id of hours available.
   * @param string $tid_current_situation
   *   The term id of current situation.
   * @param string $result
   *   The result in API.
   * @param string $tid_period_year
   *   The term id of period year.
   */
  public function interestedInternshipNew(
    string $cid_user,
    string $cid_internship,
    string $id_event,
    string $tid_hours_available,
    string $tid_current_situation,
    string $result,
    string $tid_period_year
  ) {
    // Allowed values in API.
    $values_hours_available = ['A', 'B', 'C', 'D', 'X'];
    $values_current_situation = [
      'ganar_experiencia',
      'practica_profesional',
      'sin_informacion',
    ];
    $values_period_year = [];

    $hours_available = $this->getValueTaxonomyApi($tid_hours_available, 'hours_available', $values_hours_available, 'X');
    $current_situation = $this->getValueTaxonomyApi($tid_current_situation, 'current_situation', $values_current_situation, 'sin_informacion');
    $period_year = $this->getValueTaxonomyApi($tid_period_year, 'period_year', $values_period_year, 'I semestre');

    return $this->personInterestedInternship->new(
      $cid_user,
      $cid_internship,
      $id_event,
      $hours_available,
      $current_situation,
      $result,
      $period_year,
    );
  }

  /**
   * Sending information to API CINDE when new person interested internship.
   *
   * @param string $cid_user
   *   The cinde user id.
   * @param string $cid_internship
   *   The cinde node id.
   * @param string $id_event
   *   The id event.
   * @param string $tid_hours_available
   *   The term id of hours available.
   * @param string $tid_current_situation
   *   The term id of current situation.
   * @param string $result
   *   The result in API.
   * @param string $tid_period_year
   *   The term id of period year.
   */
  public function interestedInternshipReplace(
    string $cid_user,
    string $cid_internship,
    string $id_event,
    string $tid_hours_available,
    string $tid_current_situation,
    string $result,
    string $tid_period_year
  ) {
    // Allowed values in API.
    $values_hours_available = ['A', 'B', 'C', 'D', 'X'];
    $values_current_situation = [
      'ganar_experiencia',
      'practica_profesional',
      'sin_informacion',
    ];
    $values_period_year = [];

    $hours_available = $this->getValueTaxonomyApi($tid_hours_available, 'hours_available', $values_hours_available, 'X');
    $current_situation = $this->getValueTaxonomyApi($tid_current_situation, 'current_situation', $values_current_situation, 'sin_informacion');
    $period_year = $this->getValueTaxonomyApi($tid_period_year, 'period_year', $values_period_year, 'I semestre');

    return $this->personInterestedInternship->replace(
      $cid_user,
      $cid_internship,
      $id_event,
      $hours_available,
      $current_situation,
      $result,
      $period_year,
    );
  }

  /**
   * Search the field_id in taxonomy for send to API CINDE.
   *
   * @param string $tid
   *   The term id for search.
   * @param string $vid
   *   The vocabulary id for search.
   *
   * @return string
   *   Returns the value needed to be sent.
   */
  public function searchTermFieldId(string $tid, string $vid) {
    /** @var Drupal\Core\Entity\EntityInterface[] terms */
    $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadByProperties([
      'tid' => $tid,
      'vid' => $vid,
    ]);
    $result = array_shift($terms);
    if (isset($result->field_id)) {
      return $result->field_id->value;
    }
  }

  /**
   * Function for get ID necessary to API with taxonomy id.
   *
   * @param string $tid
   *   The term id.
   * @param string $term_name
   *   The term name.
   * @param array $allowed_value
   *   Get all allowed value for validate.
   * @param string $default_value
   *   If it do not find id, return default value.
   */
  public function getValueTaxonomyApi(
    string $tid,
    string $term_name,
    array $allowed_value,
    string $default_value,
  ) {
    // Get ID API from terms.
    $value = $this->searchTermFieldId($tid, $term_name);

    // If it don't find ID, assign default value.
    $value = ($value)
    ? (!empty($allowed_value)
      ? (in_array($value, $allowed_value) ? $value : $default_value)
      : $value)
    : $default_value;

    return $value;
  }

}
