<?php

declare(strict_types=1);

namespace Drupal\blog_like\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Provides a Related Blogs block.
 *
 * @Block(
 *   id = "related_blogs",
 *   admin_label = @Translation("Related Blogs"),
 * )
 */
final class RelatedBlogsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected RouteMatchInterface $routeMatch;

  /**
   * Constructs a RelatedBlogsBlock object.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    RouteMatchInterface $route_match,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition,
  ): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('current_route_match'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $node = $this->routeMatch->getParameter('node');
    if (!$node instanceof NodeInterface || $node->bundle() !== 'blog') {
      return [];
    }

    $author_id = $node->getOwnerId();

    $nids = $this->entityTypeManager
      ->getStorage('node')
      ->getQuery()
      ->accessCheck(TRUE)
      ->condition('type', 'blog')
      ->condition('uid', $author_id)
      ->condition('nid', $node->id(), '<>')
      ->sort('field_like_count', 'DESC')
      ->range(0, 3)
      ->execute();

    if (!$nids) {
      return [];
    }

    $blogs = $this->entityTypeManager
      ->getStorage('node')
      ->loadMultiple($nids);

    $items = [];

    foreach ($blogs as $blog) {
      $items[] = [
        '#type' => 'link',
        '#title' => $blog->label(),
        '#url' => $blog->toUrl(),
      ];
    }

    return [
      '#theme' => 'item_list',
      '#items' => $items,
      '#title' => $this->t('Related Blogs'),
      '#cache' => [
        'contexts' => ['route'],
      ],
    ];
  }

}
