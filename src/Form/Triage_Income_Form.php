<?php

namespace Drupal\triage\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;

class Triage_Income_Form extends FormBase
{
    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'triage_income_form';
    }

    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $con = Database::getConnection();
        $tempstore = \Drupal::service('tempstore.private')->get('triage');
        $size = $tempstore->get('my_house_size');
        $numpeople = array();
        for( $i= 0 ; $i <= 15 ; $i++ ){
            $numpeople[$i] = $i;
        }
        if ($size > 0){$size = $size - 1;}
        $status = $tempstore->get('triage_live_alone');
        $my_benefits = $tempstore->get('my_benefits');
        //watchdog('bds','s: '. $status);
        $text =  "Include your spouse/partner (unless you are seeking help with a divorce), 
            your children, and any other adults who live with you that you support.";
        $inc_text = "Include income from all people in your household. If you are seeking help with a divorce or separation, 
              don’t include that person’s income.";
        if($size > 1){$status = false;}
        $income = $tempstore->get('my_house_income');
        $nid = $tempstore->get('triage_page_nid');
        $vid = ta_variable_get('triage_public_benefits','',$nid);
        $benefits = $con->query("SELECT tid, name FROM taxonomy_term_field_data WHERE vid = :vid", array(':vid' => $vid))->fetchAllKeyed();
        $period = $tempstore->get('triage_income_period');
        $inctitle = trim($period . " Income");
        $opts = array();
        $opts['Weekly'] = 'Week';
        $opts['Bi-Weekly'] = 'Two Weeks';
        $opts['Monthly'] = 'Month';
        $opts['Annual'] = 'Year';
        $form['triage_real'] = array(
            '#type' => 'hidden',
            '#default_value' => 0,
        );
        $form['triage_live_alone'] = array(
            '#type' => 'radios',
            '#options' => array(
                '0' => 'I live alone',
                '1' => 'I live with other people',
            ),
            '#name' => 'triage_live_alone',
            '#default_value' => $status,
            '#attributes' => array(
                'class' => array('triage-input who-live'),
                'rel' => array('triage_status'),
            ),
        );
        $form['house_cont'] = array(
            '#type' => 'container',
            '#states' => array(
                'visible' => array(
                    ':input[name="triage_live_alone"]' => array('value' => '1'),
                )),
        );
        $form['house_cont']['triage_household'] = array(
            '#type' => 'select',
            '#options' => $numpeople,
            '#title' => '',
            '#prefix' => "<div class='triage-household f-left'><div class='f-left'>I live with </div>",
            '#attributes' => array(
                'placeholder' => '#',
                'class' => array('triage-input f-left')),
            '#suffix' => "<div class='f-left'> other person(s)</div><div class='house_text'>" . $text .  "</div></div>",
            '#default_value' => $size,
        );
        $form['triage_clear']= array(
            '#type' => 'markup',
            '#markup' => "<div class='clear-both'> </div>",
        );
        $form['triage_income'] = array(
            '#type' => 'textfield',
            '#title' => t('My Income is '),
            '#name' => 'triage_income',
            '#size' => 6,
            '#field_prefix' => t('$'),
            '#prefix' => "<div class='triage-household-income'>",
            '#suffix' => "</div>",
            '#attributes' => array(
                'class' => array('triage-input','triage-income'),
                'placeholder' => '$$$'),
            '#default_value' => $tempstore->get('my_house_income'),
        );
        $form['triage_income_period'] = array(
            '#type' => 'radios',
            '#title' => 'every',
            '#name' => 'triage_income_period',
            '#attributes' => array(
                'class' => array('triage-input')),
            '#default_value' => $period,
            '#options' => $opts,
            '#prefix' => "<div class='triage-income_period'>",
            '#suffix' => "</div>",
            '#states' => array(
                'invisible' => array(
                    ':input[name="triage_income"]' => array('value' => ''),
                )),
        );
        $form['income_cont'] = array(
            '#type' => 'container',
            '#states' => array(
                'visible' => array(
                    ':input[name="triage_income_period"]' => array(
                        array('value' => 'Weekly'),
                        array('value' => 'Bi-Weekly'),
                        array('value' =>'Monthly'),
                        array('value' =>'Annual' ),
                    ))),
        );
        $form['triage_clear2']= array(
            '#type' => 'markup',
            '#markup' => "<div class='clear-both'> </div>",
        );
        $form['triage_extra'] = array(
            '#type' => 'container',
            '#states' => array(
                'visible' => array(
                    ':input[name="triage_live_alone"]' => array('value' => '1'),
                )),
        );
        $form['triage_extra']['triage_clear']= array(
            '#type' => 'markup',
            '#markup' => "<div class='house_text'>" . $inc_text . "</div>",
        );
        $form['triage_last_info'] = array(
            '#type' => 'container',
            '#states' => array(
                'visible' => array(
                    ':input[name="triage_live_alone"]' => array('value' => '3'),
                )),
        );
        $form['triage_last_info']['triage_benefits'] = array(
            '#type' => 'checkboxes',
            '#options' => $benefits,
            '#title' => t('Do you receive any of the following? Check all that apply.'),
            '#default_value' => $my_benefits,
            '#attributes' => array(
                'class' => array('triage-input'),
                'rel' => array('triage_status'),
            ),
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
