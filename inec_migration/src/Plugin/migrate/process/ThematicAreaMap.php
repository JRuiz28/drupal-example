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
 * Get the thematic area tems and assign to new order.
 *
 * @MigrateProcessPlugin(
 *   id = "thematic_area_map"
 * )
 *
 * Map the taxonomy term of Thematic Area:
 *
 * @code
 * field_text:
 *   plugin: thematic_area_map
 *   source: field_area_tematica
 * @endcode
 */
class ThematicAreaMap extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  const THEMATIC_AREA_MAP = [
    'Agropecuario' => 'Agropecuario',
    'Actividad Agrícola' => 'Actividad Agrícola',
    'Actividad Pecuaria' => 'Actividad Pecuaria',
    'Área y Producción Agrícola' => 'Área y Producción Agrícola',
    'Características de las fincas' => 'Características de las fincas',
    'Temas especiales agropecuarios' => 'Temas especiales agropecuarios',
    'Pobreza y Desigualdad' => 'Pobreza y desigualdad',
    'Desigualdad' => 'Desigualdad',
    'Otros métodos de pobreza' => 'Otros métodos de pobreza',
    'Pobreza por línea de ingreso' => 'Pobreza por línea de ingreso',
    'Ciencias y Tecnologías' => 'Ciencias y Tecnologías',
    'Tecnologías de Información y comunicación en hogares' => 'Tecnologías de Información y comunicación en hogares',
    'Tecnologías de Información y comunicación en empresas' => 'Tecnologías de Información y comunicación en empresas',
    'Otros temas Ciencia y Tecnología' => 'Otros temas Ciencia y Tecnología',
    'Economía' => 'Economía',
    'Comercio exterior' => 'Comercio exterior',
    'Costo Canasta Básica Alimentaria' => 'Costo Canasta Básica Alimentaria',
    'Directorio de Empresas y Establecimientos' => 'Directorio de Empresas y Establecimientos',
    'Índice de precios al consumidor' => 'Índice de precios al consumidor',
    'Estructura de empleo y remuneraciones en empresas' => 'Estructura de empleo y remuneraciones en empresas',
    'Índice de precios de la construcción' => 'Índice de precios de la construcción',
    'Sector Informal' => 'Sector Informal',
    'Temas especiales de Economía' => 'Temas especiales de Economía',
    'Estadísticas de la construcción' => 'Estadísticas de la construcción',
    'Educación' => 'Educación',
    'Empleo' => 'Empleo',
    'Temas especiales de empleo' => 'Temas especiales de empleo',
    'Género' => 'Género',
    'Género en ámbitos específicos' => 'Género en ámbitos específicos',
    'Uso del tiempo' => 'Uso del tiempo',
    'Violencia de género' => 'Violencia de género',
    'Medio Ambiente' => 'Medio Ambiente',
    'Otros temas de Medio Ambiente' => 'Otros temas de Medio Ambiente',
    'Prácticas mediambientales en sector agropecuario' => 'Prácticas mediambientales en sector agropecuario',
    'Prácticas mediambientales en los hogares' => 'Prácticas mediambientales en los hogares',
    'Indicadores ambientales' => 'Indicadores ambientales',
    'Población' => 'Población',
    'Defunciones' => 'Defunciones',
    'Migración' => 'Migración',
    'Matrimonios' => 'Matrimonios',
    'Estimaciones y Proyecciones de población' => 'Estimaciones y Proyecciones de población',
    'Nacimientos' => 'Nacimientos',
    'Temas especiales de Población' => 'Temas especiales de Población',
    'Ingresos y Gastos de Hogares' => 'Ingresos y Gastos de Hogares',
    'Gastos de los hogares' => 'Gastos de los hogares',
    'Ingresos de los hogares' => 'Ingresos de los hogares',
    'Pobreza' => 'Pobreza',
    'Social' => 'Social',
    'Victimización' => 'Victimización',
    'Salud' => 'Salud',
    'Población Adulta Mayor' => 'Población Adulta Mayor',
    'Otros temas sociales' => 'Otros temas sociales',
    'Población con discapacidad' => 'Población con discapacidad',
    'Población joven y adulta' => 'Población joven y adulta',
    'Programas sociales' => 'Programas sociales',
    'Niñez y Adolescencia' => 'Niñez y Adolescencia',
    'Cultura' => 'Cultura',
    'Grupos étnicos - raciales' => 'Grupos étnicos - raciales',
    'Vivienda' => 'Vivienda',
    'Anuario Estadístico' => '',
    'Anuario Estadístico - Agropecuario' => 'Agropecuario',
    'Anuario Estadístico - Género' => 'Género',
    'Anuario Estadístico - Ambiente, Energía y Telecomunicaciones' => 'Ambiente, Energía y Telecomunicaciones',
    'Anuario Estadístico - Ciencias y Tecnologías' => 'Ciencias y Tecnologías',
    'Anuario Estadístico - Construcción' => 'Construcción',
    'Anuario Estadístico - Educación' => 'Educación',
    'Anuario Estadístico - Economía' => 'Economía',
    'Anuario Estadístico - Empleo' => 'Empleo',
    'Anuario Estadístico - Geografía' => 'Geografía',
    'Anuario Estadístico - Indicadores' => 'Indicadores',
    'Anuario Estadístico - Índices' => 'Índices',
    'Anuario Estadístico - Medio Ambiente' => 'Medio Ambiente',
    'Anuario Estadístico - Mercado Laboral' => 'Mercado Laboral',
    'Anuario Estadístico - Población' => 'Población',
    'Anuario Estadístico - Pobreza' => 'Pobreza',
    'Anuario Estadístico - Salud y Seguridad Social' => 'Salud y Seguridad Social',
    'Anuario Estadístico - Seguridad Ciudadana y Justicia' => 'Seguridad Ciudadana y Justicia',
    'Anuario Estadístico - Social' => 'Social',
    'Anuario Estadístico - Turismo' => 'Turismo',
    'Anuario Estadístico - Vivienda' => 'Vivienda',
    'Anuario Estadístico - COVID-19' => 'COVID-19',
    'INEC Institucional' => 'INEC Institucional',
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

    // Make a query to get the thematic name of D7.
    $query = $db->select('taxonomy_term_data', 'td');
    $query->fields('td', ['name'])
      ->condition('vid', 8)
      ->condition('tid', $value);
    $term = $query->execute()->fetchField();

    // Switch back.
    Database::setActiveConnection();

    // Map the items of the constant to the new terms in D7.
    foreach (ThematicAreaMap::THEMATIC_AREA_MAP as $key => $name) {
      if ($term == $key) {
        $results = $this->termStorage->loadByProperties([
          'vid' => str_contains($term, 'Anuario Estadístico -') ? 'statistical_yearbook_categories' : 'thematic_area',
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
