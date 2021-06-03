<?php

namespace Drupal\triage\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Entity\Vocabulary;


class triage_admin_actions_form extends FormBase
{
  /**
   * {@inheritdoc}
   */
  public function getFormId()
  {
    return 'triage_admin_actions_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state)
  {
    $tempstore = \Drupal::service('tempstore.private')->get('triage');
    $config =  \Drupal::config('triage.admin_voc');
    $default = "vt_triage";
    if($config) {
      $default = $config->get('admin_voc');
    }
//    $vocs = Vocabulary::loadMultiple();
//    $voc_options = array('none'=>"-None-");
//    foreach ($vocs as $vid => $voc) {
//      $voc_options[$vid] = $voc->get('name');
//    }
    $voc_options = triage_vocs();

    $form['triage_actions_admin_voc'] = array(
      '#type' => 'select',
      '#options' => $voc_options,
      '#default_value' => $default,
    );
    $form['submit'] = array(
      '#type' => 'submit',
      '#attributes' => array(
        'class' => array('triage-action-button')),
      '#value' => t('Filter')
    );
    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $bds = "";
    $values = $form_state->getValues();
    $admin_voc = $values['triage_actions_admin_voc'];
    $config = \Drupal::service('config.factory')->getEditable('triage.admin_voc');
    $tempstore = \Drupal::service('tempstore.private')->get('triage');
    $tempstore->set('triage_actions_admin_voc', $admin_voc);
    $config
      ->set('triage', 'triage')
      ->set('admin_voc', $admin_voc)
      ->save();
    $form_state->setRedirect('triage.triage_actions_admin');
  }
}
