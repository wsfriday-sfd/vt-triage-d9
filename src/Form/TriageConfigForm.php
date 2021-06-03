<?php

namespace Drupal\triage\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\node\NodeInterface;

class TriageConfigForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'triageconfigform';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $node = NULL) {
    $tempstore = \Drupal::service('tempstore.private')->get('triage');
    triage_set_variables();
    global $base_url;
    $factor_default_text = "Check all that apply so we can find the best resources for you.<br /><hr>";
    $ta_nid = $node;
    $tempstore->set('triage_page_nid', $ta_nid);
    $one_state_text = ta_variable_get("triage_one_state_text","",$ta_nid)['value'];
    $one_state_format = ta_variable_get("triage_one_state_text","",$ta_nid)['format'];
    $vocs = Vocabulary::loadMultiple();
    $voc_options = ['none' => "-None-"];
    foreach ($vocs as $vid => $voc) {
      $voc_options[$vid] = $voc->get('name');
    }
    $logictext = "Ordering logic for display groups in triage action output. Intended to have instructions 
                  as to how to arrange display wrappers in the triage output.";

    $form['triage_setup'] = [
      '#type' => 'vertical_tabs',
    ];
    $form['triage_setup_default'] = [
      '#type' => 'details',
      '#title' => t('Triage Vocabulary and Path'),
      '#collapsible' => TRUE,
      '#group' => 'triage_setup',
    ];
    // Text field for the e-mail subject.
    $form['triage_setup_default']['triage_page_nid'] = [
      '#type' => 'hidden',
      '#size' => 15,
      '#default_value' => $ta_nid,
    ];
    $form['triage_setup_default']['triage_vocabulary'] = [
      '#type' => 'select',
      '#options' => $voc_options,
      '#default_value' => ta_variable_get('triage_vocabulary', '', $ta_nid),
      '#title' => t('Triage Taxonomy Tree'),
      '#description' => t('Taxonomy vocabulary to use for this set of triage questions'),
      '#prefix' => "<div class='two-panel'>",
    ];
    $form['triage_setup_default']['triage_libtable'] = [
      '#type' => 'select',
      '#options' => $voc_options,
      '#description' => t("Table that you're currently using to hold primary content categorization data"),
      '#default_value' => ta_variable_get('triage_libtable', "", $ta_nid),
      '#title' => t('Taxonomy table used for categorization'),
      '#suffix' => "</div>",
    ];

    $form['triage_setup_default']['triage_path'] = [
      '#type' => 'textfield',
      '#size' => 15,
      '#default_value' => ta_variable_get('triage_path', '', $ta_nid),
      '#title' => t('Path'),
      '#description' => t('URL alias that will access page.  
                            triagepage, e.g. would allow access to this page from http://vtlegal.org/triagepage 
                            if your home url is vtlegal.org'),
      '#prefix' => "<div class='two-panel'>",
      '#suffix' => "</div>",
    ];
    $form['separation1'] = [
      '#markup' => "<div class='clear-both'></div>",
    ];
    $form['triage_location'] = [
      '#type' => 'details',
      '#title' => t('Triage Location Info'),
      '#collapsible' => TRUE,
      '#group' => 'triage_setup',
    ];
    $form['triage_location']['triage_state'] = [
      '#type' => 'select',
      '#options' => triage_get_state_options(),
      '#default_value' => ta_variable_get('triage_state', '', $ta_nid),
      '#title' => t('Triage State'),
      '#description' => t('State (location) in which triage services apply'),
    ];
    $form['triage_location']['triage_not_in_state'] = [
      '#type' => 'textfield',
      '#default_value' => ta_variable_get('triage_not_in_state', '', $ta_nid),
      '#size' => 30,
      '#title' => t('Triage Out of State Resource URL'),
      '#description' => t('URL to goto if out-of-state'),
    ];
    $form['triage_location']['triage_one_state_text'] = [
      '#type' => 'text_format',
      '#title' => t('One State Only Text'),
      '#default_value' => $one_state_text,
      '#format' => $one_state_format,
      '#rows' => 3,
      '#description' => t('Text in this field will inform the city\zipcode finder 
                          to only search for items within the Triage Page State. This text will be 
                          display when the zipcode or city is not found within the state.'),
    ];
    $form['triage_status'] = [
      '#type' => 'details',
      '#title' => t('Triage User Status Settings'),
      '#collapsible' => TRUE,
      '#group' => 'triage_setup',
    ];
    $form['triage_status']['triage_status_voc'] = [
      '#type' => 'select',
      '#options' => $voc_options,
      '#default_value' => ta_variable_get('triage_status_voc', '', $ta_nid),
      '#title' => t('Triage Status Vocabulary'),
      '#description' => t('Taxonomy vocabulary to use for user status dropdown'),
    ];
    $form['triage_income'] = [
      '#type' => 'details',
      '#title' => t('Triage Income Taxonomy'),
      '#collapsible' => TRUE,
      '#group' => 'triage_setup',
    ];
    $form['triage_income']['triage_income_eligibility'] = [
      '#type' => 'select',
      '#options' => $voc_options,
      '#default_value' => ta_variable_get('triage_income_eligibility', 'triage_income_eligibility', $ta_nid),
      '#title' => t('Triage Income Vocabulary'),
      '#description' => t('Taxonomy vocabulary to use for income ranges, used to filter results'),
    ];
    $form['triage_categories'] = [
      '#type' => 'details',
      '#title' => t('Legal Category Taxonomy'),
      '#collapsible' => TRUE,
      '#group' => 'triage_setup',
    ];
    $form['triage_categories']['triage_legal_categories'] = [
      '#type' => 'select',
      '#options' => $voc_options,
      '#default_value' => ta_variable_get('triage_legal_categories', '', $ta_nid),
      '#title' => t('Triage Legal Categories Vocabulary'),
      '#description' => t('Legal category by organizations to identify areas of legal services; <br />
                            will be added to triage taxonomy so that end points can match to facilitate organizational serarch'),
    ];
    $form['triage_public_benefits'] = [
      '#type' => 'details',
      '#title' => t('Public Benefits Taxonomy'),
      '#collapsible' => TRUE,
      '#group' => 'triage_setup',
    ];
    $form['triage_public_benefits']['triage_public_benefits'] = [
      '#type' => 'select',
      '#options' => $voc_options,
      '#default_value' => ta_variable_get('triage_public_benefits', '', $ta_nid),
      '#title' => t('List of additional financial assistance benefits'),
      '#description' => t('Select taxonomy that holds additional financial benefits'),
    ];
    $form['triage_factors'] = [
      '#type' => 'details',
      '#title' => t('Additional Lists and Options'),
      '#collapsible' => TRUE,
      '#group' => 'triage_setup',
    ];
    $form['triage_factors']['triage_top_text'] = [
      '#type' => 'textfield',
      '#default_value' => ta_variable_get('triage_top_text', 'Please choose the issue you need help with:', $ta_nid),
      '#size' => 125,
      '#title' => t('Text to display at the beginning of Triage'),
    ];
    $form['triage_factors']['triage_factos_text'] = [
      '#type' => 'textfield',
      '#default_value' => ta_variable_get('triage_factos_text', $factor_default_text, $ta_nid),
      '#size' => 125,
      '#title' => t('Text to display above factor checkboxes'),
    ];
    $form['triage_factors']['triage_order_action_divs'] = [
      '#type' => 'checkbox',
      '#default_value' => ta_variable_get('triage_order_action_divs', FALSE, $ta_nid),
      '#title' => t('Allow additional output ordering logic'),
      '#description' => t('Turn on to use other factors to order triage output display wrappers<br />this also adds a "kind of help" field to divs to use in ordering logic'),
      '#prefix' => "<div class='two-panel'>",
    ];
    $form['triage_factors']['triage_kind_of_help'] = [
      '#type' => 'select',
      '#options' => $voc_options,
      '#default_value' => ta_variable_get('triage_kind_of_help', '', $ta_nid),
      '#title' => t('Kind of Help Being Sought'),
      '#description' => t('Select taxonomy that holds kind of help options'),
    ];
    $form['triage_factors']['triage_factors'] = [
      '#type' => 'select',
      '#options' => $voc_options,
      '#default_value' => ta_variable_get('triage_factors', '', $ta_nid),
      '#title' => t('List of other factors that might help direct kind of help'),
      '#description' => t('Select taxonomy that holds the list of other factors'),
      '#suffix' => "</div>",
    ];
    $form['triage_factors']['triage_order_cmds'] = [
      '#type' => 'textarea',
      '#rows' => 10,
      '#description' => $logictext,
      '#default_value' => ta_variable_get('triage_order_cmds', '', $ta_nid),
      '#title' => t('Ordering logic for display groups'),
      '#prefix' => "<div class='two-panel'>",
      '#suffix' => "</div>",
    ];
    $form['triage_button_text'] = array(
      '#type' => 'details',
      '#title' => t('Triage Button Text'),
      '#collapsible' => TRUE, // Added
      '#group' => 'triage_setup',
    );
    $form['triage_button_text']['triage_submit_text'] = array(
      '#type' => 'textfield',
      '#size' => 15,
      '#default_value' => ta_variable_get('triage_submit_text', 'Submit', $ta_nid),
      '#title' => t('Text for Submit button'),
      '#description' => t('Text for submit button, on finishing navigation of questionnaire'),
    );
    $form['triage_button_text']['triage_reset_text'] = array(
      '#type' => 'textfield',
      '#size' => 15,
      '#default_value' => ta_variable_get('triage_reset_text', 'Back', $ta_nid),
      '#title' => t('Text for Back button'),
    );
    $form['triage_button_text']['triage_next_text'] = array(
      '#type' => 'textfield',
      '#size' => 15,
      '#default_value' => ta_variable_get('triage_next_text', 'Continue', $ta_nid),
      '#title' => t('Text for Continue button'),
    );
    $form['triage_button_text']['triage_restart_text'] = array(
      '#type' => 'textfield',
      '#size' => 15,
      '#default_value' => ta_variable_get('triage_restart_text', 'Start Again', $ta_nid),
      '#title' => t('Text for Start Again button'),
    );
    $form['triage_button_text']['triage_restart_nav_bar'] = array(
      '#type' => 'checkbox',
      '#description' => "If checked, re-Start button will be on nav bar between back and submit buttons",
      '#default_value' => ta_variable_get('triage_restart_nav_bar', FALSE, $ta_nid),
      '#title' => t('Include Start Again Button on Nav Bar'),
    );
    $form['triage_own_words'] = [
      '#type' => 'details',
      '#title' => t('Own Words Search'),
      '#collapsible' => TRUE,
      '#group' => 'triage_setup',
    ];
    $form['triage_own_words']['triage_use_own_word'] = [
      '#type' => 'checkbox',
      '#default_value' => ta_variable_get('triage_use_own_word', FALSE, $ta_nid),
      '#title' => t('Include Houston AI In Your Own Words search box'),
      '#description' => t('If checked, adds a search box to the top category page where users can type in problem in their own words'),
      '#prefix' => "<div class='two-panel'>",
    ];
    $form['triage_own_words']['triage_own_words_title'] = [
      '#type' => 'textfield',
      '#default_value' => ta_variable_get('triage_own_words_title', 'Describe your problem in your own words...', $ta_nid),
      '#size' => 30,
      '#title' => t('Title for In Your Own Words search box'),
      '#suffix' => "</div>",
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#id' => 'triage_config_submit',
      '#value' => t('Save Configuration'),
    ];
    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {

    $values = $form_state->getValues();
    $ta_nid = $values['triage_page_nid'];
    foreach ($values as $key => $val) {
      if (strpos($key, 'triage_') !== FALSE) {
        ta_variable_set($key, $val, $ta_nid);
      }
    }
    //        $statvid = ta_variable_get('triage_status_voc', '', $ta_nid);
    //        if ($statvid > 0) {
    //            $statvoc = \Drupal\taxonomy\Entity\Vocabulary::load($statvid);
    //            $recs = db_query("select * from field_config where deleted = 0");
    //            foreach ($recs as $rec) {
    //                $ray = unserialize($rec->data);
    //                if (isset($ray['settings']['allowed_values'])
    //                    && isset($ray['settings']['allowed_values']['0'])
    //                    && isset($ray['settings']['allowed_values']['0']['vocabulary'])
    //                ) {
    //                    $voc = $ray['settings']['allowed_values'][0]['vocabulary'];
    //                    if ($voc == $statvoc) {
    //                        ta_variable_set('triage_status_table', $rec->field_name, $ta_nid);
    //                    }
    //                }
    //            }
    //        }
    //$node = node_load($ta_nid);
    //triage_node_update($node);
  }
}
