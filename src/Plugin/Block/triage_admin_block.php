<?php

namespace Drupal\triage\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Triage Admin Block' Block.
 *
 * @Block(
 *   id = "triage_admin_block",
 *   admin_label = @Translation("Triage Admin Block"),
 *   category = @Translation("Triage"),
 * )
 */
class triage_admin_block extends BlockBase
{
  /**
   * {@inheritdoc}
   */
  public function build()
  {
    $out = triage_admin();
    return array(
      '#markup' => $out,
    );
  }
}