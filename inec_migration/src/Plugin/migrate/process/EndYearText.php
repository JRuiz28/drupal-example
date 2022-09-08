<?php

namespace Drupal\inec_migration\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Get the end year and save field text.
 *
 * @MigrateProcessPlugin(
 *   id = "end_year_text"
 * )
 *
 * Map the end year document of Node:
 *
 * @code
 * field_text:
 *   plugin: end_year_text
 *   source: text
 * @endcode
 */
class EndYearText extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $startYear = $value['value'];
    $endYear = $value['value2'];

    if (isset($endYear) && $startYear != $endYear) {
      if (preg_match_all('/\d{4}/', $endYear, $matches)) {
        $year = array_shift($matches)[0];
        return $year;
      }
    }
  }

}
