<?php

namespace Drupal\inec_migration\Plugin\migrate\source;

use Drupal\node\Plugin\migrate\source\d7\Node as d7_node;

/**
 * Published news nodes from the d7 database.
 *
 * @MigrateSource(
 *   id = "inec_node",
 *   source_module = "node"
 * )
 */
class InecNode extends d7_node {

  // Get all ID taxonomy thematic area "Anuario".
  const TID_THEMATIC_AREA_YEARBOOK = [
    561, 897, 562, 749, 563, 750, 564, 751, 565, 566, 567,
    752, 568, 569, 754, 570, 571, 572, 573, 756, 906,
  ];

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = parent::query();

    // Only migrate published records.
    $query->condition('n.status', 1);

    return $query;

  }

}
