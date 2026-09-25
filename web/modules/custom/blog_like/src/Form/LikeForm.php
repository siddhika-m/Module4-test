<?php

declare(strict_types=1);

namespace Drupal\blog_like\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a like button for blog posts.
 */
final class LikeForm extends FormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Constructs a LikeForm object.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'blog_like_form';
  }

  /**
   * Builds the like form.
   */
  public function buildForm(
    array $form,
    FormStateInterface $form_state,
    ?NodeInterface $node = NULL,
  ): array {
    $like_count = (int) ($node->get('field_like_count')->value ?? 0);

    $form['node_id'] = [
      '#type' => 'hidden',
      '#value' => $node->id(),
    ];

    $form['like_button'] = [
      '#type' => 'submit',
      '#value' => 'Like',
      '#ajax' => [
        'callback' => '::ajaxLike',
        'wrapper' => 'like-count-' . $node->id(),
      ],
    ];

    $form['like_count'] = [
      '#type' => 'markup',
      '#markup' => '<div id="like-count-' . $node->id() . '">' . $like_count . '</div>',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $node_id = $form_state->getValue('node_id');

    $node = $this->entityTypeManager
      ->getStorage('node')
      ->load($node_id);

    $like_count = (int) $node->get('field_like_count')->value;

    $node->set('field_like_count', $like_count + 1);
    $node->save();
  }

  /**
   * AJAX callback.
   */
  public function ajaxLike(array &$form, FormStateInterface $form_state): array {
    return $form['like_count'];
  }

}
