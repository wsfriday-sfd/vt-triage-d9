<?php

namespace Drupal\triage\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;

/**
 * Settings form for the module.
 */
class triage_settings_form extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'triage_settings_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $con = Database::getConnection();
    $config = \Drupal::service('config.factory')->getEditable('triage.config');
    $configvals = $config->get('config');
    if(!$configvals){
      $configvals['triage_custom_classes'] = "";
    }
    $form = [];
    if (isset($configvals)) {
      $tempstore = \Drupal::service('tempstore.private')->get('triage');
      $names = node_type_get_names();
      $popup_msg = $configvals['triage_popup_basemsg'];
      if (!isset($popup_msg['value'])) {
        $popup_basemsg_text = '';
      }
      else {
        $popup_basemsg_text = $popup_msg['value'];
      }
      if (!isset($popup_msg['format'])) {
        $popup_basemsg_format = 2;
      }
      else {
        $popup_basemsg_format = $popup_msg['format'];
      }
      $fld_opts = array();
      $fld_opts[] = "None";
      $opts = triage_ref_fields();
      foreach ($opts as $opt) {
        $fld_opts[$opt] = $opt;
      }
      $cnty_opts = array();
      $form['triage_general'] = array(
        '#type' => 'details',
        '#title' => t('Triage General Options'),
        '#collapsible' => TRUE, // Added
      );
      $form['triage_general']['triage_google_key'] = array(
        '#type' => 'textfield',
        '#name' => 'triage_google_key',
        '#default_value' => $configvals['triage_google_key'],
        '#size' => 125,
        '#title' => t('Key for Google API'),
      );
      $form['triage_general']['triage_use_sms'] = array(
        '#type' => 'checkbox',
        '#default_value' => $configvals['triage_use_sms'],
        '#title' => t('Use Messaging Service, if available, as Results Option'),
      );
      $form['triage_general']['triage_sms_block'] = array(
        '#type' => 'textfield',
        '#name' => 'triage_sms_block',
        '#default_value' => $configvals['triage_sms_block'],
        '#size' => 125,
        '#title' => t('ID of Webform for Text Cell Phone #'),
      );
      $form['triage_general']['triage_use_search_views'] = array(
        '#type' => 'checkbox',
        '#default_value' => $configvals['triage_use_search_views'],
        '#title' => t('Use Triage Info in various search views'),
      );
      $form['triage_css_option'] = array(
        '#type' => 'details',
        '#collapsible' => TRUE, // Added
        '#collapsed' => TRUE,  // Added
        '#title' => t('Triage Custom CSS options'),
      );
      $form['triage_css_option']['triage_custom_css'] = array(
        '#type' => 'textfield',
        '#name' => 'triage_custom_css',
        '#size' => 50,
        '#default_value' => $configvals['triage_custom_css'],
        '#title' => t('Path and file name of custom css file'),
        '#description' => t('file name of custom css file for Triage, assumed to be in root of the theme, e.g. <em>/my_triage_custom.css</em>'),
      );
      $form['triage_css_option']['triage_css_dropdown'] = array(
        '#type' => 'details',
        '#title' => t('Triage Custom Class Dropdown Items'),
        '#collapsible' => TRUE, // Added
        '#collapsed' => TRUE,  // Added
      );
      $form['triage_css_option']['triage_css_dropdown']['triage_custom_classes'] = array(
        '#type' => 'textarea',
        '#title' => t('Custom Classes for Class Dropdowns'),
        '#default_value' => $configvals['triage_custom_classes'],
        '#description' => 'Enter custom classes in the format classname~Class Name, e.g. 
                            <em>ta-contrast-background~Contrasting Background</em><br />
                            One class per line.  Use an * in front of the entry for wrapper-only classes',
        '#rows' => 5,
        '#suffix' => "<div style='margin-bottom:10px;'></div>",
      );
      $form['content_types'] = array(
        '#type' => 'details',
        '#title' => t('Triage Search'),
        '#collapsible' => TRUE, // Added
        '#collapsed' => TRUE,  // Added
      );
      $form['content_types']['triage_search'] = array(
        '#type' => 'details',
        '#title' => t('Triage Search Configuration'),
        '#collapsible' => TRUE, // Added
        '#collapsed' => TRUE,  // Added
      );
      $form['content_types']['triage_search']['triage_search_field'] = array(
        '#type' => 'select',
        '#options' => $fld_opts,
        '#default_value' => $configvals['triage_search_field'],
        '#title' => t('Triage Search Tag Table'),
        '#description' => t('Table that holds triage tags for various content'),
      );
      $form['content_types']['triage_search']['triage_search_types'] = array(
        '#type' => 'checkboxes',
        '#title' => t('Content Type to Include in Search'),
        '#options' => $names,
        '#default_value' => $configvals['triage_search_types'],
      );
      $form['content_types']['triage_orgsearch'] = array(
        '#type' => 'details',
        '#title' => t('Triage Organization Search Configuration'),
        '#collapsible' => TRUE, // Added
        '#collapsed' => TRUE,  // Added
      );
      $form['content_types']['triage_orgsearch']['triage_orgtype_field'] = array(
        '#type' => 'select',
        '#options' => $fld_opts,
        '#default_value' => $configvals['triage_orgtype_field'],
        '#title' => t('Triage Organizational Type Table'),
        '#description' => t('Table that holds organizational type info'),
      );
      $form['content_types']['triage_orgsearch']['triage_orgcounty_field'] = array(
        '#type' => 'select',
        '#options' => $cnty_opts,
        '#default_value' => $configvals['triage_orgcounty_field'],
        '#title' => t('Triage County Table'),
        '#description' => t('Table that holds county info'),

      );
      $form['content_types']['triage_orgsearch']['triage_orgsearch_field'] = array(
        '#type' => 'select',
        '#options' => $fld_opts,
        '#default_value' => $configvals['triage_orgsearch_field'],
        '#title' => t('Triage Organization Search Tag Table'),
        '#description' => t('Table that holds triage tags for various content'),
      );
      $form['content_types']['triage_orgsearch']['triage_orgincome_field'] = array(
        '#type' => 'select',
        '#options' => $fld_opts,
        '#default_value' => $configvals['triage_orgincome_field'],
        '#title' => t('Triage Income Visibility Table'),
        '#description' => t('Table that holds triage income filters'),
      );
      $form['content_types']['triage_orgsearch']['triage_status_field'] = array(
        '#type' => 'select',
        '#options' => $fld_opts,
        '#default_value' => $configvals['triage_status_field'],
        '#title' => t('Triage Status Table'),
        '#description' => t('Table that holds status info'),
      );
      $form['content_types']['triage_orgsearch']['triage_search_func'] = array(
        '#type' => 'textfield',
        '#name' => 'triage_search_func',
        '#size' => 50,
        '#default_value' => $configvals['triage_search_func'],
        '#title' => t('Name of custom organizational search function'),
        '#description' => t('Name of custom organizational search function, found in triage.orgsearch.inc'),
      );
      $form['content_types']['triage_orgsearch']['triage_use_multiple_org_filters'] = array(
        '#type' => 'checkbox',
        '#default_value' => $configvals['triage_use_multiple_org_filters'],
        '#title' => t('Use Multiple Organization Filter Capability'),
        '#description' => t('When checked, the advanced, multiple organizational filtering function is turned on, allowing a finer grained eligibility criteria'),
      );
      $form['content_types']['triage_orgsearch']['triage_orgsearch_types'] = array(
        '#type' => 'checkboxes',
        '#title' => t('Content Type to Include in Organization Search'),
        '#options' => $names,
        '#default_value' => $configvals['triage_orgsearch_types'],
      );

      $form['reports'] = array(
        '#type' => 'details',
        '#title' => t('Triage Report Settings'),
        '#collapsible' => TRUE, // Added
        '#collapsed' => TRUE,  // Added
      );
      $form['reports']['triage_nonservice'] = array(
        '#type' => 'checkbox',
        '#default_value' => $configvals['triage_nonservice'],
        '#title' => t('Include Out of Service Area Stats'),
      );
      $form['reports']['triage_use_county'] = array(
        '#type' => 'checkbox',
        '#default_value' => $configvals['triage_use_county'],
        '#title' => t('Include County Information'),
        '#description' => 'If you are collecting town/zipcode info, turn on to display County data',
      );
      $form['reports']['triage_use_status'] = array(
        '#type' => 'checkbox',
        '#default_value' => $configvals['triage_use_status'],
        '#title' => t('Include Status Info'),
      );

      $form['reports']['triage_use_state'] = array(
        '#type' => 'checkbox',
        '#default_value' => $configvals['triage_use_state'],
        '#title' => t('Include State Stats'),
      );
      $form['reports']['triage_use_intake'] = array(
        '#type' => 'checkbox',
        '#default_value' => $configvals['triage_use_intake'],
        '#title' => t('Include Intake Referral Stats'),
      );
      $form['reports']['triage_use_problems'] = array(
        '#type' => 'checkbox',
        '#default_value' => $configvals['triage_use_problems'],
        '#title' => t('Include Legal Problem Info'),
      );
      $form['reports']['triage_use_categories'] = array(
        '#type' => 'checkbox',
        '#default_value' => $configvals['triage_use_categories'],
        '#title' => t('Include Legal Category Info'),
      );
      $form['reports']['triage_use_income'] = array(
        '#type' => 'checkbox',
        '#default_value' => $configvals['triage_use_income'],
        '#title' => t('Include Income Information'),
        '#description' => 'If you are collecting income info, turn on to display Income data',
      );
      $form['popup'] = array(
        '#type' => 'details',
        '#title' => t('Triage Popup Settings'),
        '#collapsible' => TRUE, // Added
        '#collapsed' => TRUE,  // Added
      );
      $form['popup']['triage_use_popup'] = array(
        '#type' => 'checkbox',
        '#title' => 'Enable Popup for Triage',
        '#description' => t("If checked, a popup will display tagged content to suggest triage"),
        '#default_value' => $configvals['triage_use_popup'],
      );
      $form['popup']['triage_pop_min_width'] = array(
        '#type' => 'textfield',
        '#default_value' => $configvals['triage_pop_min_width'],
        '#title' => t('Popup screen width threshhold'),
        '#description' => 'Mimimum screen width before triage popup is activated',
      );
      $form['popup']['triage_pop_max_width'] = array(
        '#type' => 'textfield',
        '#default_value' => $configvals['triage_pop_max_width'],
        '#title' => t('Popup screen width turn-off'),
        '#description' => 'Maximum screen width before triage popup is de-activated',
      );
      $form['popup']['triage_use_parents_in_popup'] = array(
        '#type' => 'checkbox',
        '#title' => 'Include checked parent categories in popup search',
        '#description' => t("If checked, a popup will be flagged for parent categories as well as specific"),
        '#default_value' => $configvals['triage_use_parents_in_popup'],
      );
      $form['popup']['triage_use_alt_url'] = array(
        '#type' => 'checkbox',
        '#title' => 'Use Alt Base URL',
        '#description' => t("If checked, an alternate base url can be used for triage"),
        '#default_value' => $configvals['triage_use_alt_url'],
      );
      $form['popup']['triage_alt_base_url'] = array(
        '#type' => 'textfield',
        '#default_value' => $configvals['triage_alt_base_url'],
        '#title' => t('Alternate base_url for triage'),
        '#description' => 'Fill in base url for triage testing, if different than content/popup testing site',
      );
      $form['popup']['triage_default_voc'] = array(
        '#type' => 'textfield',
        '#default_value' => $configvals['triage_default_voc'],
        '#title' => t('Triage path'),
      );
      $form['popup']['triage_popup_title'] = array(
        '#type' => 'textfield',
        '#default_value' => $configvals['triage_popup_title'],
        '#title' => t('Popup Header'),
      );
      $form['popup']['triage_yes_text'] = array(
        '#type' => 'textfield',
        '#default_value' => $configvals['triage_yes_text'],
        '#title' => t('Popup Yes Text'),
      );
      $form['popup']['triage_gen_text'] = array(
        '#type' => 'textfield',
        '#default_value' => $configvals['triage_gen_text'],
        '#title' => t('Popup General Help Text'),
      );
      $form['popup']['triage_no_text'] = array(
        '#type' => 'textfield',
        '#default_value' => $configvals['triage_no_text'],
        '#title' => t('Popup No Text'),
      );
      $form['popup']['triage_popup_speed'] = array(
        '#type' => 'textfield',
        '#default_value' => $configvals['triage_popup_speed'],
        '#title' => t('Popup Delay'),
      );
      $form['popup']['triage_popup_norepeat'] = array(
        '#type' => 'textfield',
        '#description' => 'To minimize confusion, it can be helpful to skip popup for several iterations',
        '#default_value' => $configvals['triage_popup_norepeat'],
        '#title' => t('#popups to skip after reaching an endpoint'),
      );
      $form['popup']['triage_popup_basemsg'] = array(
        '#type' => 'text_format',
        '#title' => t('Popup Base Message'),
        '#default_value' => $popup_basemsg_text,
        '#format' => 'full_html',
        '#rows' => 3,
        '#description' => t('Text used at the bottom of the popup, just ahead of the buttons'),
      );
      $form['popup']['triage_use_popup_taxonomy_language'] = array(
        '#type' => 'checkbox',
        '#title' => t('Add taxonomy reference text'),
        '#description' => t("If checked, a taxonomy reference title will be added to popup message - You seem to be interested in..."),
        '#default_value' => $configvals['triage_use_popup_taxonomy_language'],
      );
      $form['popup']['triage_libtable'] = array(
        '#type' => 'select',
        '#options' => $fld_opts,
        '#description' => t("Table that you're currently using to hold primary content categorization data"),
        '#default_value' => $configvals['triage_libtable'],
        '#title' => t('Taxonomy table used for categorization'),
      );
      $form['popup']['triage_libtid'] = array(
        '#type' => 'textfield',
        '#description' => t("Taxonomy id field (tid) from the above taxonomy data table"),
        '#default_value' => $configvals['triage_libtid'],
        '#title' => t('Taxonomy id field used for categorization'),
      );
      $form['popup']['triage_ref_field'] = array(
        '#type' => 'textfield',
        '#description' => t("Taxonomy reference field that points to triage question/issue, for popop navigation"),
        '#default_value' => $configvals['triage_ref_field'],
        '#title' => t('Taxonomy reference field from above taxonomy that points to triage question'),
      );
      $form['popup']['triage_popup_types'] = array(
        '#type' => 'checkboxes',
        '#title' => t('Content Types that will activate Popup'),
        '#options' => $names,
        '#default_value' => $configvals['triage_popup_types'],
      );

      $form['ips'] = array(
        '#type' => 'details',
        '#title' => t('IPs to exclude from Report '),
        '#collapsible' => TRUE, // Added
        '#collapsed' => TRUE,  // Added
      );
      $form['ips']['triage_exclude_ips'] = array(
        '#type' => 'textarea',
        '#description' => t("One entry per line"),
        '#default_value' => $configvals['triage_exclude_ips'],
        '#title' => t('IPs to exclude from Summary Report'),
      );
      $form['submit'] = array(
        '#type' => 'submit',
        '#value' => t('Submit'),
      );
    }

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::service('config.factory')->getEditable('triage.config');
    $vals = $form_state->getValues();
    $savevals = [];
    foreach($vals as $key => $value){
      $savevals[$key] = $value;
    }
    $config
      ->set('triage', 'triage')
      ->set('config', $savevals)
      ->save();
    // Clear the cache
    //    \Drupal::cache()->delete(STATECODE_CACHE_CID);
  }

}