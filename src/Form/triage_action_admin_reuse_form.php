<?php

namespace Drupal\triage\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;


class triage_action_admin_reuse_form extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'triage_action_admin_reuse_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $term = NULL) {
    $con = Database::getConnection();
    $tempstore = \Drupal::service('tempstore.private')->get('triage');
    $options = $choices = triage_vocs();
    $form['triage_actions_admin_voc'] = array(
      '#type' => 'select',
      '#options' => $options,
      '#attributes' => array(
        'class' => array('triage-action-filter')),
      '#default_value' => $tempstore->get('triage_actions_admin_voc'),
    );
    $form['triage_actions_admin_keyword'] = array(
      '#type' => 'textfield',
      '#default_value' => $tempstore->get('triage_reusable_filter'),
      '#attributes' => array(
        'class' => array('triage-action-filter'),
        'placeholder' => t('Enter filter keyword')),
    );
    $form['reusable_text'] = array(
      '#type' => 'checkbox',
      '#title' => t('Reusable Text Only'),
      '#default_value' => $tempstore->get('triage_reusable_text_only'),
    );
    $form['submit'] = array(
      '#type' => 'submit',
      '#attributes' => array(
        'class' => array('triage-action-button', 'triage-action-filter')),
      '#value' => t('Filter')
    );
    $form['clear'] = array(
      '#type' => 'submit',
      '#name' => 'clear',
      '#attributes' => array(
        'class' => array('triage-action-button')),
      '#value' => t('Clear')
    );
    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $tempstore = \Drupal::service('tempstore.private')->get('triage');
    $values = $form_state->getValues();
    $tempstore->set('triage_actions_admin_voc', $values['triage_actions_admin_voc']);
    $tempstore->set('triage_reusable_filter', $values['triage_actions_admin_keyword']);
    $tempstore->set('triage_reusable_text_only', $values['reusable_text']);
    $triggering_element = $form_state->getTriggeringElement();
    $button_name = $triggering_element['#name'];
    if ($button_name == "clear") {
      $tempstore->set('triage_reusable_filter', '');
      $tempstore->set('triage_reusable_text_only', 0);
    }
    $form_state->setRedirect('triage.reuse_admin');
  }


}