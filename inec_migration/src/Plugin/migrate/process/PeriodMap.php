<?php

namespace Drupal\inec_migration\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\migrate\Row;
use Drupal\Core\Database\Database;

/**
 * Get the period terms and assign to new order.
 *
 * @MigrateProcessPlugin(
 *   id = "period_map"
 * )
 *
 * Map the taxonomy term of Period:
 *
 * @code
 * field_text:
 *   plugin: period_map
 *   source: text
 * @endcode
 */
class PeriodMap extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  const PERIOD_MAP = [
    'Mes' => 'Mes',
    'Enero' => 'Enero',
    'Febrero' => 'Febrero',
    'Marzo' => 'Marzo',
    'Abril' => 'Abril',
    'Mayo' => 'Mayo',
    'Junio' => 'Junio',
    'Julio' => 'Julio',
    'Agosto' => 'Agosto',
    'Setiembre' => 'Setiembre',
    'Octubre' => 'Octubre',
    'Noviembre' => 'Noviembre',
    'Diciembre' => 'Diciembre',
    'Trimestre' => 'Trimestre',
    'I Trimestre' => 'I Trimestre',
    'II Trimestre' => 'II Trimestre',
    'III Trimestre' => 'III Trimestre',
    'IV Trimestre' => 'IV Trimestre',
    'Semestre' => 'Semestre',
    'I Semestre' => 'I Semestre',
    'II Semestre' => 'II Semestre',
  ];

  /**
   * The termStorage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $termStorage;

  /**
   * Class constructor.
   *
   * @param array $configuration
   *   The configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entityTypeManager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->termStorage = $entityTypeManager->getStorage('taxonomy_term');
  }

  /**
   * Create the Thematic Area Map Plugin.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container interface.
   * @param array $configuration
   *   The configuration data.
   * @param string $plugin_id
   *   The Plugin id.
   * @param mixed $plugin_definition
   *   The plugin definition.
   *
   * @return static
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // Set the connection with D7 migration database.
    Database::setActiveConnection('migrate');

    // Get a connection with the D7 migration database.
    $db = Database::getConnection();

    // Make a query to get the source name of D7.
    $query = $db->select('taxonomy_term_data', 'td');
    $query->fields('td', ['name'])
      ->condition('vid', 24)
      ->condition('tid', $value);
    $term = $query->execute()->fetchField();

    // Switch back.
    Database::setActiveConnection();

    // Map the items of the constant to the new terms in D7.
    foreach (PeriodMap::PERIOD_MAP as $key => $name) {
      if ($term == $key) {
        $results = $this->termStorage->loadByProperties([
          'vid' => 'time_frame',
          'name' => $name,
        ]);
        $results = array_values($results);
        if (isset($results[0])) {
          return $results[0]->id();
        }
      }
    }
  }

}
