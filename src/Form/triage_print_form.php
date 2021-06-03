<?php

namespace Drupal\triage\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class triage_print_form extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'triage_print_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    //    $form['print_text'] = [
    //      '#type' => 'markup',
    //      '#markup' => "<h3>" . t('Print or email this to yourself') . "</h3>",
    //      '#prefix' => "<div class='triage-print-options'>",
    //    ];
    $form['triage_print_email'] = [
      '#type' => 'textfield',
      '#size' => 70,
      '#prefix' => "<div class='triage-print-email'>",
      '#attributes' => [
        'class' => ['triage-input'],
        'placeholder' => t('Email Address'),
        'title' => t('Email Address'),
      ],

    ];
    $form['email'] = [
      '#type' => 'submit',
      '#suffix' => "<div class='triage-filler'></div>",
      '#value' => t('Email this page'),
    ];
    $form['print'] = [
      '#type' => 'submit',
      '#suffix' => "</div>",
      '#value' => t('Print this page'),
      '#attributes' => [
        'class' => ['triage-print-button'],
      ],
    ];
    return $form;

  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $tempstore = \Drupal::service('tempstore.private')->get('triage');
    $values = $form_state->getValues();
    if ( trim($values['triage_print_email']) > "") {
      $tempstore->set('triage_send_to', $values['triage_print_email']);
      return TRUE;
    }
    else {
      $form_state->setErrorByName('triage_print_email', t('Please fill in a valid email address'));;
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $path = \Drupal::request()->getpathInfo();
    $arg = explode('/', $path);
    $values = $form_state->getValues();
    $trigger = $form_state->getTriggeringElement();
    if ($trigger['#value']->render() == 'Email this page') {
      $tid = $arg[2];
      $preview = 3;
      $url = \Drupal\Core\Url::fromRoute('triage.triage_actions_process')
        ->setRouteParameters(['tid' => $tid, 'preview' => $preview]);
      $form_state->setRedirectUrl($url);
      $this->messenger()->addStatus($this->t('The information has been emailed to @email', ['@email' => $form_state->getValue('triage_print_email')]));
    }
    else {
      //            $cp = "/print/" . $arg[0] . "/" . $arg[1] . "/4";;
      //            $form_state->setRedirect($cp);
    }
  }
}
