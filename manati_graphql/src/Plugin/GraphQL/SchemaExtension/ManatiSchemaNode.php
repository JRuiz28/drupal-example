<?php

namespace Drupal\manati_graphql\Plugin\GraphQL\SchemaExtension;

use Drupal\graphql\GraphQL\ResolverBuilder;
use Drupal\graphql\GraphQL\ResolverRegistryInterface;
use Drupal\graphql\Plugin\GraphQL\SchemaExtension\SdlSchemaExtensionPluginBase;
use Drupal\node\NodeInterface;
use GraphQL\Error\Error;

/**
 * Schema extesion file for nodes.
 *
 * @SchemaExtension(
 *   id = "manati_node",
 *   name = "Manati Schema Node",
 *   description = "Exposes nodes fields.",
 *   schema = "manati"
 * )
 */
class ManatiSchemaNode extends SdlSchemaExtensionPluginBase {

  /**
   * {@inheritdoc}
   */
  public function registerResolvers(ResolverRegistryInterface $registry) : void {
    $builder = new ResolverBuilder();

    $this->addNodeSkillFields($registry, $builder);
  }

  /**
   * Add NodeInterface type resolver.
   *
   * @param \Drupal\graphql\GraphQL\ResolverRegistryInterface $registry
   *   The registry interface.
   */
  protected function addNodeInterfaceTypeResolver(ResolverRegistryInterface $registry) : void {
    // Tell GraphQL how to resolve types of a common interface.
    $registry->addTypeResolver('NodeInterface', function ($value) {
      if ($value instanceof NodeInterface) {
        switch ($value->bundle()) {

          case 'skill':
            return 'NodeSkill';
        }
      }
      throw new Error('Could not resolve content type.');
    });
  }

  /**
   * Add the Node base fields resolvers.
   *
   * @param string $type
   *   The Node type to add base fields.
   * @param \Drupal\graphql\GraphQL\ResolverRegistryInterface $registry
   *   The registry interface.
   * @param \Drupal\graphql\GraphQL\ResolverBuilder $builder
   *   The builder.
   */
  protected function addNodeBaseFields(string $type, ResolverRegistryInterface $registry, ResolverBuilder $builder): void {

    $registry->addFieldResolver(
      $type,
      'id',
      $builder->produce('entity_id')
      ->map('entity', $builder->fromParent())
    );

    $registry->addFieldResolver(
      $type,
      'title',
      $builder->produce('entity_label')
      ->map('entity', $builder->fromParent())
    );

    $registry->addFieldResolver(
      $type,
      'url',
      $builder->compose(
        $builder->produce('entity_url')
        ->map('entity', $builder->fromParent()),
        $builder->produce('url_path')
        ->map('url', $builder->fromParent())
      )
    );

    $registry->addFieldResolver(
      $type,
      'metatags',
      $builder->produce('entity_metatags')
      ->map('entity', $builder->fromParent())
    );
  }

  /**
   * Add Skill fields resolvers.
   *
   * @param \Drupal\graphql\GraphQL\ResolverRegistryInterface $registry
   *   The registry interface.
   * @param \Drupal\graphql\GraphQL\ResolverBuilder $builder
   *   The builder.
   */
  protected function addNodeSkillFields(ResolverRegistryInterface $registry, ResolverBuilder $builder): void {
    $this->addNodeBaseFields('NodeSkill', $registry, $builder);

    $registry->addFieldResolver('NodeSkill', 'summary',
      $builder->produce('property_path')
        ->map('type', $builder->fromValue('entity:node'))
        ->map('value', $builder->fromParent())
        ->map('path', $builder->fromValue('body.summary'))
    );

    $registry->addFieldResolver('NodeSkill', 'description',
      $builder->produce('property_path')
        ->map('type', $builder->fromValue('entity:node'))
        ->map('value', $builder->fromParent())
        ->map('path', $builder->fromValue('body.processed'))
    );

    $registry->addFieldResolver('NodeSkill', 'icon',
      $builder->produce('property_path')
        ->map('type', $builder->fromValue('entity:node'))
        ->map('value', $builder->fromParent())
        ->map('path', $builder->fromValue('field_icon.entity'))
    );

    $registry->addFieldResolver('NodeSkill', 'image',
      $builder->produce('property_path')
        ->map('type', $builder->fromValue('entity:node'))
        ->map('value', $builder->fromParent())
        ->map('path', $builder->fromValue('field_image.entity'))
    );

    $registry->addFieldResolver('NodeSkill', 'video',
      $builder->produce('property_path')
        ->map('type', $builder->fromValue('entity:node'))
        ->map('value', $builder->fromParent())
        ->map('path', $builder->fromValue('field_video.entity'))
    );

    $registry->addFieldResolver('NodeSkill', 'careers',
      $builder->produce('entity_reference')
        ->map('entity', $builder->fromParent())
        ->map('field', $builder->fromValue('field_related_content_unlimited'))
    );

    $registry->addFieldResolver('NodeSkill', 'keywords',
      $builder->produce('entity_reference')
        ->map('entity', $builder->fromParent())
        ->map('field', $builder->fromValue('field_keywords_unlimited'))
    );

    $registry->addFieldResolver('NodeSkill', 'relatedContent',
      $builder->produce('query_related_content')
        ->map('entity', $builder->fromParent())
        ->map('field', $builder->fromValue('field_keywords_unlimited'))
    );
  }

}
