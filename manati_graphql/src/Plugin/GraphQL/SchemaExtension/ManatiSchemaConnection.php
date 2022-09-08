<?php

namespace Drupal\manati_graphql\Plugin\GraphQL\SchemaExtension;

use Drupal\graphql\GraphQL\ResolverBuilder;
use Drupal\graphql\GraphQL\ResolverRegistryInterface;
use Drupal\graphql\Plugin\GraphQL\SchemaExtension\SdlSchemaExtensionPluginBase;
use Drupal\manati_graphql\Wrappers\QueryNodeConnection;
use Drupal\manati_graphql\Wrappers\QuerySearchConnection;
use Drupal\manati_graphql\Wrappers\QueryUserConnection;

/**
 * Schema extesion file for connections.
 *
 * @SchemaExtension(
 *   id = "manati_connection",
 *   name = "Manati Schema Connection",
 *   description = "Exposes the connection fields.",
 *   schema = "manati"
 * )
 */
class ManatiSchemaConnection extends SdlSchemaExtensionPluginBase {

  /**
   * {@inheritdoc}
   */
  public function registerResolvers(ResolverRegistryInterface $registry) : void {
    $builder = new ResolverBuilder();

    $this->addNodeConnectionFields('NodeSkillConnection', $registry, $builder);
  }

  /**
   * Add NodeConnection fields.
   *
   * @param string $type
   *   The type of connection.
   * @param \Drupal\graphql\GraphQL\ResolverRegistryInterface $registry
   *   The registry interface.
   * @param \Drupal\graphql\GraphQL\ResolverBuilder $builder
   *   The builder.
   */
  protected function addNodeConnectionFields($type, ResolverRegistryInterface $registry, ResolverBuilder $builder) : void {
    $registry->addFieldResolver($type, 'total',
      $builder->callback(function (QueryNodeConnection $connection) {
        return $connection->total();
      })
    );

    $registry->addFieldResolver($type, 'items',
      $builder->callback(function (QueryNodeConnection $connection) {
        return $connection->items();
      })
    );
  }


}
