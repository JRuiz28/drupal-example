<?php

namespace Drupal\inec_migration\Plugin\migrate\source;

use Drupal\node\Plugin\migrate\source\d7\Node as d7_node;
use Drupal\Core\Database\Database;

/**
 * Published publications nodes from the d7 database.
 *
 * @MigrateSource(
 *   id = "inec_publication_node",
 *   source_module = "node"
 * )
 */
class InecPublicationNode extends d7_node {

  const TID_PUBLICATION = [37, 341];

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = parent::query();

    // Get connection DB for subquery.
    Database::setActiveConnection('migrate');
    $db = Database::getConnection();

    // Create a subquery.
    $subquery = $db->select('field_data_field_area_tematica_documento', 'fat');
    $subquery->addExpression('entity_id');
    $subquery->condition('fat.field_area_tematica_documento_tid', InecNode::TID_THEMATIC_AREA_YEARBOOK, 'NOT IN');

    // Join table Node and field_data_field_tipo_de_documento.
    $query->leftJoin('field_data_field_tipo_de_documento', 'ftd', 'ftd.entity_id = n.nid');

    // Only migrate published records and specific taxonomy.
    $query->condition('n.status', 1)
      ->condition('ftd.field_tipo_de_documento_tid', InecPublicationNode::TID_PUBLICATION, "IN")
      ->condition('n.nid', $subquery, 'IN');

    return $query;
  }

}
