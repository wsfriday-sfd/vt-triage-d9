<?php

namespace Drupal\triage\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class triage_zip_form extends FormBase
{
    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'triage_zip_form';
    }

    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $tempstore = \Drupal::service('tempstore.private')->get('triage');
        $cinfo = '';
        $state = $tempstore->get('triage_state_id');
        $zipmsg = 'Enter zipcode or town';
        if (trim($state) == '') {
            $zipmsg = "Enter Zip";
        }
        $cZip = $tempstore->get('my_zip');
        if ($cZip > '') {
          $cinfo .= triage_build_zip($cZip);
        }
        $form['triage_real'] = array(
            '#type' => 'hidden',
            '#default_value' => 0,
        );
        $form['triage_zip'] = array(
            '#title' => t("Enter zipcode where your legal issue is"),
            '#type' => 'textfield',
            '#size' => 20,
            '#maxlength' => 5,
            '#default_value' => $cZip,
            '#attributes' => array(
                'placeholder' => t($zipmsg),
                'class' => array('triage-input')),
            '#ajax' => array(
                'callback' => 'triage_get_zip',
                'progress' => 'throbber',
                'event' => 'blur',
            )
        );
        $form['zip_text'] = array(
            '#type' => 'markup',
            '#markup' => "<div id='triage_city'>" . $cinfo . "</div>",
        );
        return $form;
    }
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $values = $form_state->getValues();
    }
}
