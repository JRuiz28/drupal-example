<?php

namespace Drupal\inec_migration\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Database;

/**
 * Get the documents category term D7 and assign to new term.
 *
 * @MigrateProcessPlugin(
 *   id = "documents_categories_map"
 * )
 *
 * Map the taxonomy term of Documents categories:
 *
 * @code
 * field_text:
 *   plugin: documents_categories_map
 *   source: text
 * @endcode
 */
class DocumentsCategoriesMap extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  const DOCUMENT_CATEGORIES_MAP = [
    'Publicaciones' => 'Publicaciones',
    'Catálogos' => 'Catálogos',
    'Cuestionarios' => 'Cuestionarios',
    'Documentos Metodológicos' => 'Documentos metodológicos',
    'Manuales' => 'Manuales',
    'Bienes inmuebles' => 'Bienes inmuebles',
    'Archivo central' => 'Archivo central',
    'Circulares internas' => 'Despacho Gerencial: Circulares Internas',
    'Encuesta satisfacción usuarios' => 'Encuesta satisfacción usuarios',
    'Encuesta satisfacción web' => 'Encuesta satisfacción web',
    'Informes' => 'Informes',
    'Normativa' => 'Normativa',
    'Compras Proyecto Banco Mundial' => 'Compras Proyecto Banco Mundial',
    'Plan de compras anual e informes de ejecución' => 'Plan de compras anual e informes de ejecución',
    'Audiencias públicas' => 'Audiencias públicas',
    'Marco Legal' => 'Marco Legal',
    'Políticas Institucionales' => 'Políticas Institucionales',
    'Ética Institucional' => 'Ética Institucional',
    'Sistema de Control Interno y SEVRI' => 'Sistema de Control Interno y SEVRI',
    'Planes Institucionales' => 'Planes Institucionales',
    'Procesos Institucionales' => 'Procesos Institucionales',
    'Informes Institucionales' => 'Informes Institucionales',
    'Actas y Agendas Consejo Directivo' => 'Actas y Agendas Consejo Directivo',
    'Informes anuales' => 'Informes anuales',
    'Planes de mejora y avances' => 'Planes de mejora y avances',
    'Catálogo de trámites institucionales' => 'Catálogo de trámites institucionales',
    'Presupuesto' => 'Presupuestos',
    'Asesorias externas' => 'Asesorias externas',
    'Categorías salariales y perfiles de puestos' => 'Categorías salariales y perfiles de puestos',
    'Informes de calificación del personal' => 'Informes de calificación del personal',
    'Informes de fin de gestión' => 'Informes de fin de gestión',
    'Informes de viajes' => 'Informes de viajes',
    'Procesos de contratación del personal' => 'Procesos de contratación del personal',
    'Sobre el personal del INEC' => 'Sobre el personal del INEC',
    'Lineamientos internos' => 'Lineamientos internos',
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
   * Create the Publication Categories Map Plugin.
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
    $value = array_values($value);
    $value = $value[0];
    $vid = ($value === '37' || $value === '341') ? 'publication_categories' : (($value === '287' || $value === '288' || $value === '289') ? 'methodology' : 'transparency');

    // Set the connection with D7 migration database.
    Database::setActiveConnection('migrate');

    // Get a connection with the D7 migration database.
    $db = Database::getConnection();

    // Make a query to get the source name of D7.
    $query = $db->select('taxonomy_term_data', 'td');
    $query->fields('td', ['name'])
      ->condition('vid', 9)
      ->condition('tid', $value);
    $term = $query->execute()->fetchField();

    // Switch back.
    Database::setActiveConnection();

    // Map the items of the constant to the new terms in D7.
    foreach (DocumentsCategoriesMap::DOCUMENT_CATEGORIES_MAP as $key => $name) {
      if ($term == $key) {
        // There are two 'Normativa', Assign which parent belongs.
        if ($term == 'Normativa') {
          $parent_name = $value === '852' ? 'Auditoría' : 'Institucional';

          // Search parent.
          $terms_parent = $this->termStorage->loadByProperties([
            'name' => $parent_name,
            'vid' => $vid,
          ]);
          $term_parent = array_shift($terms_parent);

          $results = $this->termStorage->loadByProperties([
            'vid' => $vid,
            'name' => $name,
            'parent' => $term_parent->id(),
          ]);
        }
        else {
          $results = $this->termStorage->loadByProperties([
            'vid' => $vid,
            'name' => $name,
          ]);
        }
        $results = array_values($results);
        if (isset($results[0])) {
          return $results[0]->id();
        }
      }
    }
  }

}
