<?php

namespace Drupal\triage\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Core\Database\Database;


class triage_summary_form extends FormBase
{
  /**
   * {@inheritdoc}
   */
  public function getFormId()
  {
    return 'triage_summary_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state)
  {
    $con = Database::getConnection();
    $tempstore = \Drupal::service('tempstore.private')->get('triage');
    $config =  \Drupal::config('triage.admin_voc');
    $default = "vt_triage";
    if($config) {
      $default = $config->get('admin_voc');
    }
    $choices = triage_vocs();
    $form = array();
    $thisvoc = $tempstore->get('triage_summary_voc');
    if ( !isset( $thisvoc ) || is_null($thisvoc)) {
      $thisvoc = $con->query("select distinct vid from triage_log where vid > ''")->fetchColumn();
      $tempstore->set('triage_summary_voc', $thisvoc);
    }
    $sdate = $tempstore->get('triage_summary_report_start');
    if ( !isset( $sdate ) || is_null($sdate)) {
      $year = date("Y");
      $sdate = date("Y-m-d", mktime(0, 0, 0, 1, 1, $year));
      $tempstore->set('triage_summary_report_start', $sdate);
    }
//    $startdate = array(
//      'year'=> intval(date('Y',$sdate)),
//      'month'=> intval(date('m',$sdate)),
//      'day' => intval(date('d', $sdate)),
//    );
    $edate = $tempstore->get('triage_summary_report_end');
    if ( !isset( $edate ) || is_null($edate)) {
      $edate = date("Y-m-d");
      $tempstore->set('triage_summary_report_end', $edate);
    }
//    $enddate = array(
//      'year'=>date('Y',$edate),
//      'month'=>date('m',$edate),
//      'day' => date('d', $edate)
//    );
    $show_summary = $tempstore->get('triage_summary_report_summ_only');
    if ( !isset( $show_summary ) || is_null($show_summary)) {
      $show_summary = true;
      $tempstore->set('triage_summary_report_summ_only', $show_summary);
    }
    $author = $tempstore->get('triage_summary_report_author');
    $form['filter'] = array(
      '#type' => 'fieldset',
      '#title' => t('Filter Results'),
      '#collapsible' => TRUE, // Added
      '#prefix' => '<div class="date_inline">',
      '#suffix' => '</div">',
    );
    $form['filter']['start'] = array(
      '#type' => 'date',
      '#title' => t('Start'),
      '#default_value' => $sdate,
      '#prefix' => '<div class="filter-wrap filter_wrap1">',
    );
    $form['filter']['end'] = array(
      '#type' => 'date',
      '#title' => t('End'),
      '#default_value' => $edate,
    );
    $form['filter']['summary'] = array(
      '#type' => 'checkbox',
      '#default_value' => $show_summary,
      '#title' => t('Show Summary Only'),
      '#attributes' => array('class' => 'summary_checkbox'),
      '#suffix' => '</div">',
    );
    $form['filter']['summary_voc'] = array(
      '#type' => 'select',
      '#title' => 'Triage Vocabulary',
      '#options' => $choices,
      //'#title' => t('Do any of these statements apply to you?'),
      '#default_value' => $thisvoc,
      '#attributes' => array(
        'class' => array('summary_voc'),
      ),
      '#prefix' => '<div class="filter-wrap filter_wrap2">',
    );
//    $form['filter']['summary_author'] = array(
//      '#type' => 'select',
//      '#title' => 'Triage Seeker',
//      '#options' => array('0'=>'Self','1'=>'Other','2'=>'No Filter'),
//      //'#title' => t('Do any of these statements apply to you?'),
//      '#default_value' => $author,
//      '#attributes' => array(
//        'class' => array('summary_voc'),
//      ),
//      '#suffix' => '</div">',
//    );
    $form['filter']['filter'] = array(
      '#type' => 'submit',
      '#name' => 'filter',
      '#value' => t('Filter'),
      '#prefix' => '<div class="filter-wrap filter_wrap3">',
    );
    $form['filter']['reset'] = array(
      '#type' => 'submit',
      '#name' => 'reset',
      '#value' => t('Reset'),
    );
    $form['filter']['excel'] = array(
      '#type' => 'submit',
      '#name' => 'excel',
      '#value' => t('Excel'),
      '#suffix' => '</div></div></div>',
    );
    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $con = Database::getConnection();
    $values = $form_state->getValues('filter');
    $admin_voc = $values['summary_voc'];
    $config = \Drupal::service('config.factory')->getEditable('triage.admin_voc');
    $tempstore = \Drupal::service('tempstore.private')->get('triage');
    /*
     * Store form info into session variables
     */
    //dsm($form_state);
    $op = $form_state->getTriggeringElement()['#name'];
    switch ($op) {
      case 'filter':
        $dstart = $values['start'];
        $dend = $values['end'] ;
        $summary = $values['summary'];
        $thisvoc = $values['summary_voc'];
        //$author = $values['summary_author'];
        break;
      case 'reset':
        $year = date("Y");
        $dstart = date("Y-m-d H:i:s",mktime(0,0,0,1,1,$year));
        $dend = date("Y-m-d H:i:s");
        $summary = false;
        $thisvoc = $con->query('select distinct vid from triage_log where vid > 0')->fetchColumn();
        $tempstore->set('triage_summary_voc',$thisvoc);
        break;
      case t('excel'):
        drupal_goto('triage_summary_excel');
        break;
    }
    $tempstore->set('triage_summary_report_start', $dstart);
    $tempstore->set('triage_summary_report_end', $dend);
    $tempstore->set('triage_summary_report_summ_only', $summary);
    //$tempstore->set('triage_summary_report_author', $author);
    $tempstore->set('triage_summary_voc', $thisvoc);
    $form_state->setRedirect('triage.summary');
  }
}
