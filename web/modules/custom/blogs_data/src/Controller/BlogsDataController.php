<?php

namespace Drupal\blogs_data\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * A controller for exposing blog data.
 */
final class BlogsDataController extends ControllerBase {

  /**
   * This is used to fetch and return blog data based on filter type and value.
   */
  public function getBlogsData(string $filter_type, string $filter_value): JsonResponse {

    $storage = $this->entityTypeManager()->getStorage('node');

    $query = $storage->getQuery()
      ->condition('type', 'blog')
      ->accessCheck(FALSE);

    if ($filter_type === 'author') {
      $user_storage = $this->entityTypeManager()->getStorage('user');

      $users = $user_storage->loadByProperties([
        'name' => $filter_value,
      ]);

      $user = reset($users);

      if (!$user) {
        return new JsonResponse([
          'message' => 'Author not found.',
        ], 404);
      }

      $query->condition('uid', $user->id());
    }

    elseif ($filter_type === 'tag') {
      $term_storage = $this->entityTypeManager()->getStorage('taxonomy_term');

      $terms = $term_storage->loadByProperties([
        'name' => $filter_value,
      ]);

      $term = reset($terms);

      if (!$term) {
        return new JsonResponse([
          'message' => 'Tag not found.',
        ], 404);
      }

      $query->condition('field_tags.target_id', $term->id());
    }

    else {
      return new JsonResponse([
        'message' => 'Invalid filter type.',
      ], 400);
    }

    $nids = $query->execute();

    $data = [];

    foreach ($nids as $nid) {
      $node = $storage->load($nid);

      $data[] = [
        'Blog Title' => $node->label(),
        'Blog Body' => $node->get('field_blog_body')->value,
        'Author' => $node->getOwner()->label(),
        'Tag' => $node->get('field_tags')->entity->label(),
        'Publish Date' => date('d-m-Y', $node->getCreatedTime()),
      ];
    }

    return new JsonResponse($data);
  }

  /**
   * This is used to fetch data for a specific range.
   */
  public function getBlogsDate(string $start_date, string $end_date): JsonResponse {

    $storage = $this->entityTypeManager()->getStorage('node');

    $start = strtotime($start_date . ' 00:00:00');
    $end = strtotime($end_date . ' 23:59:59');

    $nids = $storage->getQuery()
      ->condition('type', 'blog')
      ->condition('created', $start, '>=')
      ->condition('created', $end, '<=')
      ->accessCheck(FALSE)
      ->execute();

    $data = [];

    foreach ($nids as $nid) {
      $node = $storage->load($nid);

      $data[] = [
        'Blog Title' => $node->label(),
        'Blog Body' => $node->get('field_blog_body')->value,
        'Author' => $node->getOwner()->label(),
        'Publish Date' => date('d-m-Y', $node->getCreatedTime()),
      ];
    }

    return new JsonResponse($data);
  }

}
