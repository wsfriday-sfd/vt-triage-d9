<?php

namespace Drupal\triage\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\taxonomy\Entity\Term;
use Drupal\image\Entity\ImageStyle;

/**
 * Provides a 'Triage Zipcode Block' Block.
 *
 * @Block(
 *   id = "triage_zip_block",
 *   admin_label = @Translation("Triage Zipcode Block"),
 *   category = @Translation("Triage"),
 * )
 */
class triage_zip_block extends BlockBase
{

  /**
   * {@inheritdoc}
   */
  public function build()
  {
    $form = \Drupal::formBuilder()->getForm('Drupal\\triage\Form\\triage_zip_form');
    return $form;

  }

}