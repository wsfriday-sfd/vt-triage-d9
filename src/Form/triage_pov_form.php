<?php

namespace Drupal\triage\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class triage_pov_form extends FormBase
{
    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'triage_pov_form';
    }

    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $tempstore = \Drupal::service('tempstore.private')->get('triage');
        $size = $tempstore->get('my_house_size');
        $income = $tempstore->get('my_house_income');
        $form['triage_real'] = array(
            '#type' => 'hidden',
            '#default_value' => 0,
        );
        $form['triage_household'] = array(
            '#type' => 'textfield',
            '#title' => t('# People in Household'),
            '#size' => 2,
            '#prefix' => "<div class='triage-house-size'>",
            '#attributes' => array(
                'class' => array('triage-input')),
            '#suffix' => "</div>",
            '#default_value' => $size,
        );
        $form['triage_income'] = array(
            '#type' => 'textfield',
            '#title' => t('MONTHLY Income'),
            '#size' => 6,
            '#field_prefix' => t('$'),
            '#prefix' => "<div class='triage-house-income'>",
            '#suffix' => "</div>",
            '#attributes' => array(
                'class' => array('triage-input','triage-income')),
            '#default_value' => $tempstore->get('my_house_income'),
        );
        return $form;

    }

    public function submitForm(array &$form, FormStateInterface $form_state)
    {
//        global $base_url;
//        if ($form_state['triggering_element']['#value'] == 'Email') {
//            $cp = arg(0) . "/" . arg(1) . "/3";
//            $form_state['redirect'] = $cp;
//        } else {
//            $cp = "print/" . $cp = arg(0) . "/" . arg(1) . "/4";;
//            $form_state['redirect'] = $cp;
//        }

    }
}
