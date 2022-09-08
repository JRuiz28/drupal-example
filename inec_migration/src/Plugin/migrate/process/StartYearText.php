<?php

namespace Drupal\inec_migration\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Get the start year and save field text.
 *
 * @MigrateProcessPlugin(
 *   id = "start_year_text"
 * )
 *
 * Map the start year document of Node:
 *
 * @code
 * field_text:
 *   plugin: start_year_text
 *   source: text
 * @endcode
 */
class StartYearText extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (isset($value) && preg_match_all('/\d{4}/', $value, $matches)) {
      $year = array_shift($matches)[0];
      return $year;
    }
  }

}
