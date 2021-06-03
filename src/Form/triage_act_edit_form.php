<?php

namespace Drupal\triage\Form;

use Drupal\Core\Form;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Language\Language;
use Drupal;

class triage_act_edit_form extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'triage_act_edit_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $id = 0, $type = "text", $reuseit = 0, $lang = "en") {
    $con = Database::getConnection();
    $action = $con->query("select title, 
                        bundle,
                        pid,
                        entity_id,
                        display_header, 
                        reusable_text, 
                        action_text,
                        action_text_format,
                        node_view_opt,
                        node_ref_nid,
                        trim_length,
                        extra,
                        classes,
                        region,
                        intake_elig,
                        php_show
                        from triage_actions 
                        where id=:id
                        and language = :lang",
      [
        ":id" => $id,
        ":lang" => $lang,
      ]
    )->fetchAssoc();
    if(! $action){
      $action = $con->query("select title, 
                        bundle,
                        pid,
                        entity_id,
                        display_header, 
                        reusable_text, 
                        action_text,
                        action_text_format,
                        node_view_opt,
                        node_ref_nid,
                        trim_length,
                        extra,
                        classes,
                        region,
                        intake_elig,
                        php_show
                        from triage_actions 
                        where id=:id
                        and language = :lang",
        [
          ":id" => $id,
          ":lang" => 'en',
        ]
      )->fetchAssoc();
    }
    $tempstore = \Drupal::service('tempstore.private')->get('triage');
    $tempstore->set('my_bundle',$action['bundle']);
    $bundle = $tempstore->get('my_bundle');
    $language = \Drupal::languageManager()->getCurrentLanguage();
    $currlang = $language->getId();
    if ($type == "srch") {
      $type_vals = $tempstore->get('triage_search_types');
      $type_opts = [];
      foreach ($type_vals as $key => $val) {
        if ($val) {
          $type_opts[$key] = $key;
        }
      }
    }
    if (is_null($tempstore->get('triage_reusable_filter'))) {
      $tempstore->set('triage_reusable_filter', '');
    }
    $skey = $tempstore->get('triage_reusable_filter');
    $config = \Drupal::service('config.factory')->getEditable('triage.config');
    $configvals = $config->get('config');
    //$classlist = $tempstore->get('triage_custom_classes');
    $classlist = $configvals['triage_custom_classes'];
    $class_list = explode("\n", $classlist);
    $class_opts = [];
    $act_title = $action['title'];
    foreach ($class_list as $class) {
      if (substr($class, 0, 1) == "*") {
        $class = substr($class, 1);
        if($type != "div") {
          continue;
        }
      }
      $break = strpos($class, "~");
      if ($break == FALSE) {
        continue;
      }
      $key = substr($class, 0, $break);
      $display = substr($class, $break + 1);
      $class_opts[$key] = $display;
    }
    if ($bundle == 'quests') {
      $region_opts = [
        'none' => 'None',
        'ta-message-panel' => 'Top Message Panel',
        'ta-help-panel' => 'Help Panel',
        'ta-bottom-panel' => 'Bottom Panel',
      ];
    }
    else {
      $region_opts = [
        'none' => 'None',
        'ta-message-panel' => 'Top Message Panel',
        'ta-main-panel' => 'Main Panel',
        'ta-help-panel' => 'Help Panel',
        'ta-bottom-panel' => 'Bottom Panel',
      ];
    }
    $view_opts = [
      'trim' => 'Trimmed',
    ];
    $view_modes = Drupal::entityQuery('entity_view_mode')
      ->condition('targetEntityType', 'node')
      ->execute();
    foreach ($view_modes as $key => $val) {
      $val = ucwords(str_replace("node.", "", $val));
      $view_opts[$key] = $val;
    }
    $print_opts = [
      '' => 'Show on all',
      'print_only' => 'Print Only',
      'non_print' => 'Exclude from Print',
    ];
    if($bundle == "taxonomy"){
      $tid = $action['entity_id'];
      $vid = $con->query("select vid from taxonomy_term_field_data where tid = :tid",[":tid" => $tid])->fetchColumn();
      if(! $vid){
        $vid = $tempstore->get('triage_actions_admin_voc');
      }
    $node = triage_get_page($vid);
    $nid = $node->id();
    }
    else {
      $nid = $action['entity_id'];
    }
    $tempstore->set('triage_page_nid', $nid);
    // Public Benefits options
    $benvid = ta_variable_get('triage_public_benefits', '', $nid);
    $ben_opts = $con->query('select tid, name from taxonomy_term_field_data where vid = :vid', [':vid' => $benvid])
      ->fetchAllKeyed();

    $vid = ta_variable_get('triage_status_voc', '', $nid);
    $sql = "select tid,name from taxonomy_term_field_data where vid = :vid";
    $sTypes = $con->query($sql, [':vid' => $vid]);
    $status_opts = [];
    $vals = $con->query('select show_status from triage_actions where id = :id and language = :lang',
      [':id' => $id, ':lang' => $lang])->fetchColumn();
    $status_info_show = explode(",", $vals);
    $vals = $con->query('select hide_status from triage_actions where id = :id and language = :lang',
      [':id' => $id, ':lang' => $lang])->fetchColumn();
    $status_info_hide = explode(",", $vals);
    foreach ($sTypes as $vt) {
      $status_opts[$vt->tid] = $vt->name;
    }
    // eof status visibility section
    // Get income eligibility taxonomy for this visibility
    $vid = ta_variable_get('triage_income_eligibility', 'triage_income_eligibility', $nid);
    //  $voc = taxonomy_vocabulary_machine_name_load($voc_name);
    //  $vid=$voc->vid;
    $sql = "select tid,name from taxonomy_term_field_data where vid = :vid";
    $results = $con->query($sql, [':vid' => $vid]);
    $vTypes = $con->query($sql, [':vid' => $vid]);
    // Income visibility
    $income_opts = [];
    $vals = $con->query('select show_income from triage_actions where id = :id and language = :lang',
      [':id' => $id, ':lang' => $lang])->fetchColumn();
    $income_info_show = explode(",", $vals);
    $vals = $con->query('select hide_income from triage_actions where id = :id and language = :lang',
      [':id' => $id, ':lang' => $lang])->fetchColumn();
    $income_info_hide = explode(",", $vals);
    foreach ($vTypes as $vt) {
      $income_opts[$vt->tid] = $vt->name;
    }
    // eof income visibility section
    // County visibility section
    //$county_opts = triage_county_data();
    $county_opts = ['one' => 'One', 'two' => 'Two'];
    $vals = $con->query('select show_county from triage_actions where id = :id and language = :lang',
      [':id' => $id, ':lang' => $lang])->fetchColumn();
    $county_info_show = explode(",", $vals);
    $vals = $con->query('select hide_county from triage_actions where id = :id and language = :lang',
      [':id' => $id, ':lang' => $lang])->fetchColumn();
    $county_info_hide = explode(",", $vals);
    // eof county visibility section
    // Get status taxonomy for this visibility
    $vid = $tempstore->get('triage_actions_admin_voc');
    $sql = 'select tid, name 
                from taxonomy_term_field_data 
                where vid = :vid
                and tid in 
                (select entity_id 
                  from taxonomy_term__parent 
                  where parent_target_id = 0)
                order by 2';
    $vals = $con->query('select show_tax from triage_actions where id = :id and language = :lang',
      [':id' => $id, ':lang' => $lang])->fetchColumn();
    $tax_show = explode(",", $vals);
    $vals = $con->query('select hide_tax from triage_actions where id = :id and language = :lang',
      [':id' => $id, ':lang' => $lang])->fetchColumn();
    $tax_hide = explode(",", $vals);
    $tax_opts = [];
    $taxes = $con->query($sql, [':vid' => $vid]);
    foreach ($taxes as $tax) {
      $tax_opts[$tax->tid] = $tax->name;
    }
    if ($benvid) {
      $vals = $con->query('select show_benefits from triage_actions where id = :id',
        [':id' => $id])->fetchColumn();
      $benefits_info_show = explode(",", $vals);
      $vals = $con->query('select hide_benefits from triage_actions where id = :id',
        [':id' => $id])->fetchColumn();
      $benefits_info_hide = explode(",", $vals);
    }

    // eof status visibility section
    $bundle = $tempstore->get('my_bundle');
    if ($type == "form") {
      $form_opts = [
        'none' => 'None',
        'triage_zip_form' => 'Zip Code or Town',
        'triage_pov_form' => 'Income Info',
        'triage_income_form' => 'Income Info with Period',
        'triage_bank_form' => 'Bank Account',
        'triage_status_form' => 'Status or Demographic Group',
        'triage_whois_form' => 'Who is seeking help',
        'triage_followup_form' => 'Additional Factors',
      ];
      if ($bundle == 'help') {
        $form_opts['triage_print_form'] = 'Print and Email Options';
        $form_opts['triage_suggestion_form'] = 'I Have Another Problem';
        $form_opts['triage_problem_form'] = 'In my own words...';
      }
      if ($bundle == 'node') {
        $form_opts['triage_in_service_area_form'] = 'Service Area';
        $form_opts['triage_location_form'] = 'Service Area and County';
      }
      if ($bundle == 'quests') {
        $form_opts['triage_suggestion_form'] = 'Suggestion Form';
        $form_opts['triage_problem_form'] = 'In my own words...';
      }
    }
    if ($type == "func") {
      if ($bundle == 'help') {
        $form_opts = [
          'none' => 'None',
          'triagepath' => 'Legal Problem Answers',
          'triagequests' => 'Answers to Preliminary Questions',
          'triagedesc' => 'Question description, help and info',
          'triage_language' => 'Translation Block',
        ];
        $region_opts = [
          'none' => 'None',
          'ta-message-panel' => 'Top Message Panel',
          'ta-main-panel' => 'Main Panel',
          'ta-help-panel' => 'Help Panel',
          'ta-bottom-panel' => 'Bottom Panel',
        ];
      }
      if ($bundle == 'quests') {
        $form_opts = [
          'none' => 'None',
          'triagequests' => 'Answers to Preliminary Questions',
          'triage_restart' => 'Restart Button',
          'triagedesc' => 'Question description, help and info',
          'triage_language' => 'Translation Block',
        ];
        $region_opts = [
          'none' => 'None',
          'ta-message-panel' => 'Top Message Panel',
          'ta-help-panel' => 'Help Panel',
          'ta-bottom-panel' => 'Bottom Panel',
        ];
      }
    }
    $mytitle = $this->triage_get_title($action['node_ref_nid']);
    $form = [];
    $form = [
      '#prefix' => '<div id="triage-action-edit">',
      '#suffix' => '</div>',
    ];
    $form['action_title'] = [
      '#markup' => "<div class='triage-action-title'>" . $act_title . "</div>",
    ];
    if ($type == "text") {
      $form['language'] = [
        '#markup' => "<h3'>" . $language->getName() . "</h3>",
      ];
    }
      $form['lang'] = [
        '#type' => 'hidden',
        '#default_value' => $lang,
      ];
    $form['entity_id'] = [
      '#type' => 'hidden',
      '#default_value' => $action['entity_id'],
    ];
    $form['pid'] = [
      '#type' => 'hidden',
      '#default_value' => $action['pid'],
    ];
    $form['id'] = [
      '#type' => 'hidden',
      '#default_value' => $id,
    ];
    $form['bundle'] = [
      '#type' => 'hidden',
      '#default_value' => $bundle,
    ];
    $form['type'] = [
      '#type' => 'hidden',
      '#default_value' => $type,
    ];
    $form['mytitle'] = [
      '#type' => 'hidden',
      '#default_value' => $mytitle,
    ];
    $form['reuseit'] = [
      '#type' => 'hidden',
      '#default_value' => $reuseit,
    ];
    $form['title'] = [
      '#type' => 'textfield',
      '#title' => t('Title'),
      '#description' => 'Title can be administrative or used as the display title',
      '#default_value' => $action['title'],
      '#prefix' => "<div class='two-panel'>",
      '#suffix' => "</div>",
      '#required' => TRUE,
    ];
    if ($type == "group") {
      $form['max_chars'] = [
        '#type' => 'textfield',
        '#title' => t('Length'),
        '#description' => 'Maximum characters allowed',
        '#default_value' => $action['trim_length'],
        '#prefix' => "<div class='two-panel'>",
        '#suffix' => "</div></div>",
        '#states' => [
          'visible' => [
            ':input[name=action_text]' => [
              'value' => 'triage_suggestion_form',
            ],
          ],
        ],
      ];
    }

    $form['display_header'] = [
      '#type' => 'textfield',
      '#title' => t('Display Header'),
      '#description' => 'Display header takes precedence over title in display.  Enter &#60;none&#62; for no title/display header',
      '#default_value' => $action['display_header'],
      '#prefix' => "<div class='two-panel'>",
      '#suffix' => "</div>",
    ];
    $form['separation0'] = [
      '#markup' => "<div class='clear-both'></div>",
    ];
    if ($type == "text") {
      if( empty($action['action_text_format']) ){
        $action['action_text_format'] = "full_html";
      }
      $form['main_text'] = [
        '#type' => 'text_format',
        '#title' => t('Text'),
        '#default_value' => $action['action_text'],
        '#rows' => 3,
        '#format' => $action['action_text_format'],
        '#suffix' => "<div style='margin-bottom:10px;'></div>",
      ];
    }
    $form['region'] = [
      '#type' => 'select',
      '#title' => t('Region where item should be display'),
      '#options' => $region_opts,
      '#default_value' => $action['region'],
      '#prefix' => "<div class='two-panel'>",
      '#suffix' => "</div>",
    ];
    $form['classes'] = [
      '#type' => 'select',
      '#title' => t('Classes'),
      '#options' => $class_opts,
      '#default_value' => $action['classes'],
      '#prefix' => "<div class='two-panel'>",
      '#suffix' => "</div>",
    ];
    $form['separation1'] = [
      '#markup' => "<div class='clear-both'></div>",
    ];
    if ($type == "text") {
      $form['reusable_text'] = [
        '#type' => 'checkbox',
        '#title' => t('Include in Reusable Text Lists'),
        '#default_value' => $action['reusable_text'],
        '#prefix' => "<div class='two-panel'>",
        '#suffix' => "</div>",
      ];
      $form['intake_elig'] = [
        '#type' => 'checkbox',
        '#title' => t('Shown to Intake Eligible Only'),
        '#default_value' => $action['intake_elig'],
        '#prefix' => "<div class='two-panel'>",
        '#suffix' => "</div>",
      ];
      $form['language'] = [
        '#title' => $this->t('Language'),
        '#type' => 'language_select',
        '#languages' => Language::STATE_ALL,
        '#default_value' => $lang,
      ];
    }
    if ($type == "node") {
      $form['node_ref_nid'] = [
        '#type' => 'textfield',
        '#title' => t('Node Title'),
        '#autocomplete_route_name' => 'triage.autocomplete',
        '#autocomplete_route_parameters' => ['field_name' => 'node_ref_nid'],
        '#default_value' => $action['node_ref_nid'],
        '#prefix' => "<div class='two-panel'>",
        '#suffix' => '<div class="triage-form-node-title">' . $mytitle . '</div></div>',
      ];
    }
    if ($type == "group") {
      $form['progress_bar_text'] = [
        '#type' => 'textfield',
        '#description' => 'Step text (short) to display in progress bar for this group',
        '#title' => t('Progress Bar Text'),
        '#default_value' => $action['extra'],
        '#prefix' => "<div class='two-panel'>",
        '#suffix' => "</div>",
      ];
    }
    $form['separation2'] = [
      '#markup' => "<div class='clear-both'></div>",
    ];
    if ($bundle == 'help') {
      $form['print_visibility'] = [
        '#type' => 'select',
        '#title' => t('Print Visibility'),
        '#options' => $print_opts,
        '#default_value' => $action['extra'],
        '#prefix' => "<div class='two-panel'>",
        '#suffix' => "</div>",
      ];
    }
    if ($type == "reuse") {
      $form['keyword_label'] = [
        '#markup' => '<label>Enter keyword to filter list of reusable text items</label>',
        '#prefix' => "<div class='two-panel'>",
      ];
      $form['triage_reusable_keyword'] = [
        '#type' => 'textfield',
        '#default_value' => $skey,
        '#attributes' => [
          'class' => ['triage-action-filter'],
          'placeholder' => t('Enter filter keyword'),
        ],
        '#prefix' => "<div id='keyword'>",
        '#suffix' => "</div>",
      ];
      $form['keyword_filter'] = [
        '#type' => 'button',
        '#attributes' => [
          'class' => ['triage-action-button2', 'triage-action-filter2'],
        ],
        '#value' => t('Filter'),
        '#ajax' => [
          'callback' => '::triage_get_reusable_opts',
          'wrapper' => 'triage-reuse-opts',
          'progress' => 'throbber',
          'event' => 'click',
        ],
      ];
      $form['keyword_clear'] = [
        '#type' => 'button',
        '#attributes' => [
          'class' => ['triage-action-button2'],
        ],
        '#value' => t('Clear Keyword'),
        '#suffix' => "</div>",
        '#ajax' => [
          'callback' => '::triage_clear_keyword',
          'wrapper' => 'triage-reuse-opts',
          'progress' => 'throbber',
          'event' => 'click',
        ],
      ];
      $form['node_ref_nid'] = [
        '#type' => 'select',
        '#options' => $this->triage_reuse_opts($form_state->getValue('triage_reusable_keyword')),
        '#title' => t('Re-usable text to include'),
        '#default_value' => $action['node_ref_nid'],
        '#prefix' => "<div class='two-panel'>",
        '#suffix' => "</div>",
      ];
    }
    if ($type == "form") {
      $form['action_text'] = [
        '#name' => 'action_text',
        '#type' => 'select',
        '#title' => t('Form'),
        '#options' => $form_opts,
        '#default_value' => $action['action_text'],
        '#prefix' => "<div class='two-panel'>",
      ];
      $form['action_extra'] = [
        '#type' => 'textfield',
        '#title' => t('Outcome URL'),
        '#description' => t('If the user is not served by this service area, direct them to the URL listed above (e.g. "helpful-info" or "node/1521")'),
        '#default_value' => $action['extra'],
        '#states' => [
          'visible' => [
            ':input[name="action_text"]' => ['value' => 'triage_in_service_area_form'],
          ],
        ],
        '#suffix' => "</div>",
      ];
      $form['reusable_text'] = [
        '#type' => 'checkbox',
        '#title' => t('Mandatory'),
        '#description' => t('Check to make entries on this form required'),
        '#default_value' => $action['reusable_text'],
        '#prefix' => "<div class='two-panel'>",
        '#suffix' => "</div>",
      ];
    }
    if ($type == "func") {
      $form['action_text'] = [
        '#type' => 'select',
        '#title' => t('Function or Form'),
        '#options' => $form_opts,
        '#default_value' => $action['action_text'],
        '#prefix' => "<div class='two-panel'>",
        '#suffix' => "</div>",
      ];
      $form['extra'] = [
        '#type' => 'textfield',
        '#description' => 'Text, if any, to introduce list of answers, e.g. <em>You Said:</em>',
        '#title' => t('Introductory Phrase'),
        '#default_value' => $action['extra'],
        '#states' => [
          'visible' => [
            ':input[name="action_text"]' => ['value' => 'triagepath'],
          ],
        ],
        '#prefix' => "<div class='two-panel'>",
        '#suffix' => "</div>",
      ];
    }
    if ($type == "srch") {
      $form['action_text'] = [
        '#type' => 'checkboxes',
        '#title' => t('Content Type to Include in Search'),
        '#options' => $type_opts,
        '#default_value' => explode(",", $action['action_text']),
        '#prefix' => "<div class='two-panel'>",
        '#suffix' => "</div>",
      ];
    }
    $form['separation3'] = [
      '#markup' => "<div class='clear-both'></div>",
    ];
    if ($type == "node") {
      $form['node_view_opt'] = [
        '#type' => 'select',
        '#title' => t('Embedded Node View'),
        '#options' => $view_opts,
        '#default_value' => $action['node_view_opt'],
        '#prefix' => "<div class='two-panel'>",
        '#suffix' => "</div>",
      ];
      $form['trim_length'] = [
        '#type' => 'textfield',
        '#title' => t('Length in characters to trim embedded node content'),
        '#default_value' => $action['trim_length'],
        '#prefix' => "<div class='two-panel'>",
        '#suffix' => "</div>",
      ];
    }
    $form['separation4'] = [
      '#markup' => "<div class='clear-both'></div>",
    ];
    $form['triage_actions_visibility'] = [
      '#type' => 'details',
      '#title' => t('Restrict action visibility'),
      '#open' => FALSE,
      '#weight' => 5,
      '#description' => t('Check to know whether the user has the characteristics to see this action item. Note that both checks are always performed.'),
      '#tree' => TRUE,
    ];
    $form['triage_actions_visibility']['triage_actions_phpshow_set'] = [
      '#type' => 'details',
      '#title' => t('PHP code for visibility'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      '#weight' => 5,
      '#description' => t('Custom PHP code to determine visibility.'),
      '#tree' => TRUE,
    ];
    $form['triage_actions_visibility']['triage_actions_phpshow_set']['php_show'] = [
      '#type' => 'textarea',
      '#rows' => 5,
      '#default_value' => $action['php_show'],
      '#description' => "Do not use <?php ?>",
    ];
    $form['triage_actions_visibility']['triage_actions_income_set'] = [
      '#type' => 'details',
      '#title' => t('Income visibility'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      '#weight' => 5,
      '#description' => t('Check to know whether the user has the characteristics to see this action item. Note that both checks are always performed.'),
      '#tree' => TRUE,
    ];
    $form['triage_actions_visibility']['triage_actions_income_set']['income_qualifications_show'] = [
      '#type' => 'checkboxes',
      '#title' => t('Show action item to users with checked income qualifications'),
      '#options' => $income_opts,
      '#default_value' => $income_info_show,
      '#prefix' => "<div class='two-panel'>",
      '#suffix' => "</div>",
    ];
    $form['triage_actions_visibility']['triage_actions_income_set']['income_qualifications_hide'] = [
      '#type' => 'checkboxes',
      '#title' => t('Hide action item from users with checked income qualifications'),
      '#options' => $income_opts,
      '#default_value' => $income_info_hide,
      '#prefix' => "<div class='two-panel'>",
      '#suffix' => "</div>",
    ];
    $form['triage_actions_visibility']['triage_actions_status_set'] = [
      '#type' => 'details',
      '#title' => t('Status visibility'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      '#weight' => 6,
      '#description' => t('Check to know whether the user has the characteristics to see this action item. Note that both checks are always performed.'),
      '#tree' => TRUE,
    ];
    $form['triage_actions_visibility']['triage_actions_status_set']['status_qualifications_show'] = [
      '#type' => 'checkboxes',
      '#title' => t('Show action item to users with group status qualifications'),
      '#options' => $status_opts,
      '#default_value' => $status_info_show,
      '#prefix' => "<div class='two-panel'>",
      '#suffix' => "</div>",
    ];
    $form['triage_actions_visibility']['triage_actions_status_set']['status_qualifications_hide'] = [
      '#type' => 'checkboxes',
      '#title' => t('Hide action item from users with checked income qualifications'),
      '#options' => $status_opts,
      '#default_value' => $status_info_hide,
      '#prefix' => "<div class='two-panel'>",
      '#suffix' => "</div>",
    ];
    $form['triage_actions_visibility']['triage_actions_tax_set'] = [
      '#type' => 'details',
      '#title' => t('Taxonomy visibility'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      '#weight' => 6,
      '#description' => t('Check to know whether the user resides in the county to see this action item. Note that both checks are always performed.'),
      '#tree' => TRUE,
    ];
    $form['triage_actions_visibility']['triage_actions_tax_set']['tax_show'] = [
      '#type' => 'checkboxes',
      '#title' => t('Show action item for taxonomies'),
      '#options' => $tax_opts,
      '#default_value' => $tax_show,
      '#prefix' => "<div class='two-panel'>",
      '#suffix' => "</div>",
    ];
    $form['triage_actions_visibility']['triage_actions_tax_set']['tax_hide'] = [
      '#type' => 'checkboxes',
      '#title' => t('Hide action item for taxonomies'),
      '#options' => $tax_opts,
      '#default_value' => $tax_hide,
      '#prefix' => "<div class='two-panel'>",
      '#suffix' => "</div>",
    ];
    $form['triage_actions_visibility']['triage_actions_county_set'] = [
      '#type' => 'details',
      '#title' => t('County visibility'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      '#weight' => 6,
      '#description' => t('Check to know whether the user resides in the county to see this action item. Note that both checks are always performed.'),
      '#tree' => TRUE,
    ];
    $form['triage_actions_visibility']['triage_actions_county_set']['county_qualifications_show'] = [
      '#type' => 'checkboxes',
      '#title' => t('Show action item to users in checked counties'),
      '#options' => $county_opts,
      '#default_value' => $county_info_show,
      '#prefix' => "<div class='two-panel'>",
      '#suffix' => "</div>",
    ];
    $form['triage_actions_visibility']['triage_actions_county_set']['county_qualifications_hide'] = [
      '#type' => 'checkboxes',
      '#title' => t('Hide action item from users in checked counties'),
      '#options' => $county_opts,
      '#default_value' => $county_info_hide,
      '#prefix' => "<div class='two-panel'>",
      '#suffix' => "</div>",
    ];
    // Public Benefits Visibility
    if ($benvid) {
      $form['triage_actions_visibility']['triage_actions_benefits_set'] = [
        '#type' => 'details',
        '#title' => t('Public Benefits visibility'),
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
        '#weight' => 6,
        '#description' => t('Public Benefits Visibility. Note that both checks are always performed.'),
        '#tree' => TRUE,
      ];
      $form['triage_actions_visibility']['triage_actions_benefits_set']['benefits_qualifications_show'] = [
        '#type' => 'checkboxes',
        '#title' => t('Show action item to users with checked benefits'),
        '#options' => $ben_opts,
        '#default_value' => $benefits_info_show,
        '#prefix' => "<div class='two-panel'>",
        '#suffix' => "</div>",
      ];
      $form['triage_actions_visibility']['triage_actions_benefits_set']['benefits_qualifications_hide'] = [
        '#type' => 'checkboxes',
        '#title' => t('Hide action item from users with checked benefits'),
        '#options' => $ben_opts,
        '#default_value' => $benefits_info_hide,
        '#prefix' => "<div class='two-panel'>",
        '#suffix' => "</div>",
      ];
    }
    $form['submit'] = [
      '#type' => 'submit',
      '#weight' => 7,
      '#value' => t('Save changes'),
    ];
    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $con = Database::getConnection();
    $tempstore = \Drupal::service('tempstore.private')->get('triage');
    $vals = $form_state->getValues();
    $lang = $vals['language'];
    $entity_id = $vals['entity_id'];
    $pid = $vals['pid'];
    $bundle = $vals['bundle'];
    $type = $vals['type'];
    $id = $vals['id'];
      $nid = $vals['node_ref_nid'];
      $nid = intval($nid);
    $php_show = $vals['triage_actions_visibility']['triage_actions_phpshow_set']['php_show'];
    if (!is_int($nid)) {
      $nid = 0;
    }
    if (isset($vals['node_view_opt'])) {
       $nvo = $vals['node_view_opt'];
    }
    if(isset($vals['trim_length'])) {
      $len = $vals['trim_length'];
    }
    if (is_null($len)) {
      $len = 0;
    }
    if(isset($vals['main_text']['value'])) {
      $text = $vals['main_text']['value'];
    }
    if ($type == "form" || $type == "func") {
      $text = $vals['action_text'];
    }
    if( isset($vals['main_text']['format'])) {
      $format = $vals['main_text']['format'];
    }
    if (is_null($format)) {
      $format = "";
    }
    $title = $vals['title'];
    $header = $vals['display_header'];
    if( isset($vals['reusable_text'])) {
      $reuse = $vals['reusable_text'];
    }
    if (is_null($reuse)) {
      $reuse = 0;
    }
    if(isset($vals['intake_elig'])) {
      $intake = $vals['intake_elig'];
    }
    if ($bundle == "help") {
      $extra = $vals['print_visibility'];
    }
    if ($type == "group") {
      $extra = $vals['progress_bar_text'];
    }
    if ($type == "func") {
      $extra = $vals['extra'];
      $nvo = $vals['print_visibility'];
    }
    $lang = $vals['lang'];
    $region = $vals['region'];
    $classes = $vals['classes'];
    $show_income = $this->_triage_actions_serialize_tids($vals
    ['triage_actions_visibility']['triage_actions_income_set']['income_qualifications_show']);
    $hide_income = $this->_triage_actions_serialize_tids($vals
    ['triage_actions_visibility']['triage_actions_income_set']['income_qualifications_hide']);
    $show_status = $this->_triage_actions_serialize_tids($vals
    ['triage_actions_visibility']['triage_actions_status_set']['status_qualifications_show']);
    $hide_status = $this->_triage_actions_serialize_tids($vals
    ['triage_actions_visibility']['triage_actions_status_set']['status_qualifications_hide']);
    $show_county = $this->_triage_actions_serialize_tids($vals
    ['triage_actions_visibility']['triage_actions_county_set']['county_qualifications_show']);
    $hide_county = $this->_triage_actions_serialize_tids($vals
    ['triage_actions_visibility']['triage_actions_county_set']['county_qualifications_hide']);
    $show_tax = $this->_triage_actions_serialize_tids($vals
    ['triage_actions_visibility']['triage_actions_tax_set']['tax_show']);
    $hide_tax = $this->_triage_actions_serialize_tids($vals
    ['triage_actions_visibility']['triage_actions_tax_set']['tax_hide']);
    $show_ben = $this->_triage_actions_serialize_tids($vals
    ['triage_actions_visibility']['triage_actions_benefits_set']['benefits_qualifications_show']);
    $hide_ben = $this->_triage_actions_serialize_tids($vals
    ['triage_actions_visibility']['triage_actions_benefits_set']['benefits_qualifications_hide']);
    $find = $con->query('select id from triage_actions where id=:id and language=:lang',[":id"=> $id, ":lang"=>$lang])->fetchColumn();
    if($find) {
    $con->update('triage_actions')
      ->fields([
        'action_text' => $text,
        'node_ref_nid' => $nid,
        'node_view_opt' => $nvo,
        'trim_length' => $len,
        'action_text_format' => $format,
        'title' => $title,
        'display_header' => $header,
        'reusable_text' => $reuse,
        'intake_elig' => $intake,
        'show_income' => $show_income,
        'hide_income' => $hide_income,
        'show_status' => $show_status,
        'hide_status' => $hide_status,
        'show_county' => $show_county,
        'hide_county' => $hide_county,
        'show_tax' => $show_tax,
        'hide_tax' => $hide_tax,
        'show_benefits' => $show_ben,
        'hide_benefits' => $hide_ben,
        'extra' => $extra,
        'bundle' => $bundle,
        'language' => $lang,
        'region' => $region,
        'classes' => $classes,
          'php_show' => $php_show,
      ])
      ->condition('id', $id)
      ->condition('language', $lang)
      ->execute();
    }
    else{
      $con->insert('triage_actions')
        ->fields([
          'entity_id' => $entity_id,
          'pid' => $pid,
          'type' => $type,
          'action_text' => $text,
          'node_ref_nid' => $nid,
          'node_view_opt' => $nvo,
          'trim_length' => $len,
          'action_text_format' => $format,
          'title' => $title,
          'display_header' => $header,
          'reusable_text' => $reuse,
          'intake_elig' => $intake,
          'show_income' => $show_income,
          'hide_income' => $hide_income,
          'show_status' => $show_status,
          'hide_status' => $hide_status,
          'show_county' => $show_county,
          'hide_county' => $hide_county,
          'show_tax' => $show_tax,
          'hide_tax' => $hide_tax,
          'show_benefits' => $show_ben,
          'hide_benefits' => $hide_ben,
          'extra' => $extra,
          'bundle' => $bundle,
          'language' => $lang,
          'region' => $region,
          'classes' => $classes,
          'php_show' => $php_show,
          'language' => $lang,
          'id' => $id,
        ])->execute();
    }
    switch ($bundle) {
      case 'taxonomy':
        $tid = $tempstore->get('edit_tid');
        if ($tid) {
          $routename = 'triage.actions';
          $routeparams = ['term' => $tid];
        }
        break;
      case 'node':
        $tid = $tempstore->get('edit_tid');
        if ($tid) {
          $routename = 'triage.node.actions';
          $routeparams = ['node' => $tid];
        }
        break;
      case 'help':
        $tid = $tempstore->get('edit_tid');
        if ($tid) {
          $routename = 'triage.node.help';
          $routeparams = ['node' => $tid];
        }
        break;
      case 'quests':
        $tid = $tempstore->get('edit_tid');
        if ($tid) {
          $routename = 'triage.node.quest';
          $routeparams = ['node' => $tid];
        }
        break;
    }
    if($vals['reuseit']){
      $routename = 'triage.reuse_admin';
      $routeparams = [];
    }
    $form_state->setRedirect($routename, $routeparams);
  }

  public function _triage_actions_serialize_tids($checks) {
    $tids = [];
    foreach ($checks as $tid) {
      if (!$tid == 0) {
        $tids[] = $tid;
      }
    }
    return implode(',', $tids);
  }

  public function triage_get_title($nid = 0, $js = FALSE) {
    $con = Database::getConnection();
    if ($nid == 0) {
      return '';
    }
    if ($js) {
      return drupal_json_output($con->query('select title from node_field_data where nid = :nid', [":nid" => $nid])
        ->fetchColumn());
    }
    else {
      return $con->query('select title from node_field_data where nid = :nid', [":nid" => $nid])
        ->fetchColumn();
    }
  }

  public function triage_get_reusable_opts($form, &$form_state) {
    $tempstore = \Drupal::service('tempstore.private')->get('triage');
    $tempstore->set('triage_reusable_filter', $form_state->getUserInput()['triage_reusable_keyword']);
    return $form;
  }

  public function triage_clear_keyword($form, &$form_state) {
    $tempstore = \Drupal::service('tempstore.private')->get('triage');
    $tempstore->set('triage_reusable_filter', "");

    $input = $form_state->getUserInput();
    // We should not clear the system items from the user input.
    //$clean_keys = $form_state->getCleanValueKeys();
    $clean_keys[] = 'triage_reusable_keyword';
    foreach ($input as $key => $item) {
      if (in_array($key, $clean_keys)) {
        unset($input[$key]);
      }
    }
    $form_state->setValue('triage_reusable_keyword', '');
    $form_state->setValue('key', '');
    //$form_state->setUserInput($input);
    //    unset ($form_state['input']['triage_reusable_keyword']);
    $form['triage_reusable_keyword']['#default_value'] = "";
    $form['triage_reusable_keyword']['#value'] = '';
    $form['key']['#value'] = '';
    $this->triage_reuse_opts("");
    return $form;
  }

  public function triage_reuse_opts($key = '') {
    $con = Database::getConnection();
    $language = \Drupal::languageManager()->getCurrentLanguage();
    $lang = $language->getId();
    $default_lang = 'en';
    $query = $con->select('triage_actions', 'ta');
    $query->fields('ta', ['id', 'title']);
    $query->condition('type', 'text')
      ->condition('language', $default_lang)
      ->condition('reusable_text', 1)
      ->orderBy('title');
    if (trim($key) > '') {
      // $db_or2 = db_or();
      // $db_or2->condition('action_text', "%" . $key . "%", 'LIKE');
      // $db_or2->condition('title', "%" . $key . "%", 'LIKE');
      $db_or2 = $query->orConditionGroup()
        ->condition('action_text', "%" . $key . "%", 'LIKE')
        ->condition('title', "%" . $key . "%", 'LIKE');
      $query->condition($db_or2);
    }
    //  $bds = dpq2($query,true);
    //  watchdog('bds', $bds);
    $reusables = $query->execute();
    $reuseTypes = [];
    foreach ($reusables as $re) {
      $reuseTypes[$re->id] = $re->title;
    }
    return $reuseTypes;
  }
}
