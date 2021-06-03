<?php

use Drupal\Core\Database\Database;

function triage_summary() {
  $con = Database::getConnection();
  $tempstore = \Drupal::service('tempstore.private')->get('triage');
  $config = \Drupal::service('config.factory')->getEditable('triage.config');
  $configvals = $config->get('config');
  //get ips to exclude from report - testing, admin, etc
  $use_ip = $tempstore->get('triage_exclude_ips');
  $noips = [];
  if ($use_ip) {
    $noips = explode("\n", $use_ip);
  }
  $spacer = "";
  $form = \Drupal::formBuilder()
    ->getForm('\Drupal\\triage\Form\\triage_summary_form');
  $out = \Drupal::service('renderer')->render($form);
  $dmystart = strtotime($tempstore->get('triage_summary_report_start'));
  $dmyend = strtotime($tempstore->get('triage_summary_report_end')) + 86399;
  $summary_only = $tempstore->get('triage_summary_report_summ_only');
  $thisvid = $tempstore->get('triage_summary_voc');
  $vocname = $thisvid;
  $tp_node = triage_get_page($thisvid);
  $nid = $tp_node->nid;
  $extra = ' ';
  if ($summary_only) {
    $extra = " Summary ";
  }
  $out .= "<div class='triage-report-wrapper'><h2>Triage" . $extra . "Report - " . $vocname . "</h2>";
  $count = 0;
  $no_count = 0;
  $oos_count = 0;
  $intake_count = 0;
  if ($summary_only) {
    $query = $con->select('triage_log', 'l');
    $query->fields('l', [
      'id',
      'completed',
      'time',
      'zipcode',
      'county',
      'state',
      'pov',
      'status',
      'steps',
      'history',
      'last_stop',
      'intake_elig',
      'other_cat',
      'tid',
      'factors',
      'help',
    ]);
    $query->condition('l.time', [$dmystart, $dmyend], 'BETWEEN');
    if ($use_ip) {
      $query->condition('l.ip', $noips, 'NOT IN');
    }
    $query->condition('l.vid', $thisvid);
    $show_non = $configvals['triage_nonservice'];
    if ($show_non) {
      $no_query = clone $query;
      $no_query->isNull('l.non_service_area');
      $no_count = $no_query->countQuery()->execute()->fetchField();
      $oos_query = clone $query;
      $oos_query->condition('l.non_service_area', 1);
      $oos_count = $oos_query->countQuery()->execute()->fetchField();
    }
    $cnt_query = clone $query;
    $total_count = $cnt_query->countQuery()->execute()->fetchField();
    $out .= "Total Triage Runs: " . ($total_count) . "<br /><hr />";
    $email_query = clone $query;
    $email_query->condition('l.email', 1);
    $email_count = $email_query->countQuery()->execute()->fetchField();
    if ($show_non) {
      $query->condition('l.non_service_area', 0);
    }
    $cnt_query = clone $query;
    $cnt_query->condition('l.completed', 1);
    $complete_count = $cnt_query->countQuery()->execute()->fetchField();
    $intake_query = clone $query;
    $intake_query->condition('l.intake_elig', 1);
    $intake_count = $intake_query->countQuery()->execute()->fetchField();

    if ($show_non) {
      $out .= "Left without answering 1st question: " . $no_count . "<br />";
      if ($configvals['triage_nonservice']) {
        $out .= "Out of Service Area: " . $oos_count . "<br />";
      }
    }
    $out .= "Completed Runs: " . $complete_count . "<br />";
    $out .= "Given Online Intake Link: " . $intake_count . "<br />";
    $out .= "Sent Results via Email: " . $email_count . "<br />";
    $cnt_query2 = clone $query;
    $or = $cnt_query2->orConditionGroup()
      ->condition('l.completed', 0)
      ->condition('l.completed', NULL);
    $cnt_query2->condition($or);
    $inc_count = $cnt_query2->countQuery()->execute()->fetchField();
    $out .= "Incomplete Runs: " . ($inc_count) . "<br />";

    //By County
    $show_county = $configvals['triage_use_county'];
    if ($show_county) {
      $rows = [];
      $headers = [
        ['data' => t('County&nbsp;&nbsp;'), 'field' => 'county'],
        [
          'data' => t('Number&nbsp;of&nbsp;Triage&nbsp;Runs'),
          'field' => 'count',
        ],
        ['data' => t('Complete&nbsp;Runs')],
        ['data' => t('Incomplete&nbsp;Runs')],
      ];
      $cnt_query = clone $query;
      $cnt_query->addExpression('COUNT(l.county)', 'count');
      $cnt_query->groupBy('l.county');
      $cnt_query->extend('Drupal\Core\Database\Query\TableSortExtender')->orderByHeader($headers);;
      $results = $cnt_query->execute();
      foreach ($results as $result) {
        $result->county = trim($result->county);
        if ($result->count == 0) {
          continue;
        }
        $complete_query = clone $query;
        $complete_query->condition('l.completed', 1);
        $complete_query->condition('l.county', trim($result->county));
        $complete = $complete_query->countQuery()->execute()->fetchField();
        $incomplete_query = clone $query;
        $or1 = $incomplete_query->orConditionGroup()
          ->condition('l.completed', 0)
          ->condition('l.completed', NULL);
        $incomplete_query->condition($or1);
        $incomplete_query->condition('l.county', trim($result->county));
        $incomplete = $incomplete_query->countQuery()
          ->execute()
          ->fetchField();
        $rows[] = [
          trim($result->county),
          $spacer . $result->count,
          $spacer . $complete,
          $spacer . $incomplete,
        ];
      }
      $table1 = [
        '#theme' => 'table',
        '#header' => $headers,
        '#rows' => $rows,
      ];
      $out .= "<fieldset class='collapsible collapsed'>";
      $out .= "<legend>By County</legend>";
      $out .= \Drupal::service('renderer')->render($table1);
      $out .= "</fieldset>";
    }

    // By State
    $show_state = $configvals['triage_use_state'];
    if ($show_state) {
      $rows = [];
      $headers = [
        ['data' => t('State'), 'field' => 'state'],
        ['data' => t('Number of Triage Runs'), 'field' => 'count'],
        ['data' => t('Complete Runs')],
        ['data' => t('Incomplete Runs')],
      ];
      $cnt_query = clone $query;
      $cnt_query->addExpression('COUNT(l.state)', 'count');
      $cnt_query->groupBy('l.state');
      $cnt_query->extend('Drupal\Core\Database\Query\TableSortExtender')->orderByHeader($headers);
      $results = $cnt_query->execute();
      foreach ($results as $result) {
        $result->state = trim($result->state);
        if ($result->count == 0) {
          continue;
        }
        $complete_query = clone $query;
        $complete_query->condition('l.completed', 1);
        $complete_query->condition('l.state', trim($result->state));
        $complete = $complete_query->countQuery()->execute()->fetchField();
        $incomplete_query = clone $query;
        $or1 = $incomplete_query->orConditionGroup()
          ->condition('l.completed', 0)
          ->condition('l.completed', NULL);
        $incomplete_query->condition($or1);
        $incomplete_query->condition('l.state', trim($result->state));
        $incomplete = $incomplete_query->countQuery()->execute()->fetchField();
        $rows[] = [
          trim($result->state),
          $spacer . $result->count,
          $spacer . $complete,
          $spacer . $incomplete,
        ];
      }
      $table1 = [
        '#theme' => 'table',
        '#header' => $headers,
        '#rows' => $rows,
      ];
      $out .= "<fieldset class='collapsible collapsed'>";
      $out .= "<legend>By State</legend>";
      $out .= \Drupal::service('renderer')->render($table1);
      $out .= "</fieldset>";
    }

    // By Kind of Help Sought
    // Get kind of help taxonomy
    $help_vid = ta_variable_get('triage_kind_of_help', '', $nid);
    if ($help_vid) {
      $rows = [];
      $headers = [
        ['data' => t('Help Sought&nbsp;&nbsp;'), 'field' => 'status'],
        ['data' => t('Number&nbsp;of&nbsp;Triage&nbsp;Runs')],
        ['data' => t('% of Runs')],
      ];
      $help_query = clone $query;
      $help_query->condition('l.help', '', '>');
      $help_num = $help_query->countQuery()->execute()->fetchField();
      $helpvals = $con->query('select name from taxonomy_term_field_data where vid = :vid', [':vid' => $help_vid])->fetchCol();
      foreach ($helpvals as $help) {
        $factray = explode(',', $help);
        $temp_query = clone $help_query;
        $temp_query->condition('l.help', '%' . $help . '%', 'LIKE');
        $num = $temp_query->countQuery()->execute()->fetchField();
        $rows[] = [
          $help,
          $num,
          round($num * 100 / $help_num, 2) . '%',
        ];
      }
      $table = [
        '#theme' => 'table',
        '#header' => $headers,
        '#rows' => $rows,
        '#attributes' => ['class' => ['triage_table']],
      ];
      $out .= \Drupal::service('renderer')->render($table);
    }

    // By Status
    $show_status = $configvals['triage_use_status'];
    if ($show_status) {
      $rows = [];
      $headers = [
        ['data' => t('Status&nbsp;&nbsp;'), 'field' => 'status'],
        ['data' => t('Number&nbsp;of&nbsp;Triage&nbsp;Runs')],
        ['data' => t('% of Runs')],
      ];
      $status_query = clone $query;
      $status_query->condition('l.status', '', '>');
      $stat_num = $status_query->countQuery()->execute()->fetchField();
      // Get status taxonomy for this show/hide display
      $vid = ta_variable_get('triage_status_voc', '', $nid);
      $statvals = $con->query('select name from taxonomy_term_field_data where vid = :vid', [':vid' => $vid])->fetchCol();
      foreach ($statvals as $stat) {
        $statray = explode(',', $stat);
        $temp_query = clone $status_query;
        $temp_query->condition('l.status', '%' . $stat . '%', 'LIKE');
        $num = $temp_query->countQuery()->execute()->fetchField();
        $rows[] = [
          $stat,
          $num,
          round($num * 100 / $stat_num, 2) . '%',
        ];
      }
      $table = [
        '#theme' => 'table',
        '#header' => $headers,
        '#rows' => $rows,
        '#attributes' => ['class' => ['triage_table']],
      ];
      $out .= "<fieldset class='collapsible collapsed'>";
      $out .= "<legend>By Status</legend>";
      $out .= \Drupal::service('renderer')->render($table);
      $out .= "</fieldset>";
    }

    // By Other Factors
    $fact_vid = ta_variable_get('triage_factors', '', $nid);
    if ($fact_vid) {
      $rows = [];
      $headers = [
        ['data' => t('Factor&nbsp;&nbsp;'), 'field' => 'status'],
        ['data' => t('Number&nbsp;of&nbsp;Triage&nbsp;Runs')],
        ['data' => t('% of Runs')],
      ];
      $factor_query = clone $query;
      $factor_query->condition('l.factors', '', '>');
      $fact_num = $factor_query->countQuery()->execute()->fetchField();
      $factvals = $con->query('select name from taxonomy_term_field_data where vid = :vid', [':vid' => $fact_vid])->fetchCol();
      foreach ($factvals as $fact) {
        $factray = explode(',', $fact);
        $temp_query = clone $factor_query;
        $temp_query->condition('l.factors', '%' . $fact . '%', 'LIKE');
        $num = $temp_query->countQuery()->execute()->fetchField();
        $rows[] = [
          $fact,
          $num,
          round($num * 100 / $fact_num, 2) . '%',
        ];
      }

      $table = [
        '#theme' => 'table',
        '#header' => $headers,
        '#rows' => $rows,
        '#attributes' => ['class' => ['triage_table']],
      ];
      $out .= "<fieldset class='collapsible collapsed'>";
      $out .= "<legend>By Other Factors</legend>";
      $out .= \Drupal::service('renderer')->render($table);
      $out .= "</fieldset>";
    }

    // Referred to Intake, by Problem
    $show_intake = $configvals['triage_use_intake'];
    if ($show_intake) {
      $legal_rows = [];
      $legal_headers = [
        [
          'data' => t('Number&nbsp;of&nbsp;Intake&nbsp;Referrals'),
          'field' => 'count',
          'sort' => 'desc',
        ],
        [
          'data' => t('Legal&nbsp;Problem&nbsp;&nbsp;&nbsp;'),
          'field' => 'last_stop',
        ],
        ['data' => t('Case Code'), 'field' => 'other_cat'],
      ];
      $legal_query = clone $query;
      $legal_query->addExpression('COUNT(l.last_stop)', 'count');
      $legal_query->condition('l.intake_elig', 1);
      $legal_query->groupBy('l.last_stop');
      $legal_query->extend('Drupal\Core\Database\Query\TableSortExtender')->orderByHeader($legal_headers);
      $legal_query->extend('Drupal\Core\Database\Query\TableSortExtender');
      $legal_results = $legal_query->execute();
      foreach ($legal_results as $lresult) {
        $legal_rows[] = [
          $lresult->count,
          $lresult->last_stop,
          $lresult->other_cat,
        ];
      }

      $table1 = [
        '#theme' => 'table',
        '#header' => $legal_headers,
        '#rows' => $legal_rows,
      ];
      $out .= "<fieldset class='collapsible collapsed'>";
      $out .= "<legend>Referred to Intake, by Problem</legend>";
      $out .= \Drupal::service('renderer')->render($table1);
      $out .= "</fieldset>";
    }

    // By Legal Problem
    $show_probs = $configvals['triage_use_problems'];
    if ($show_probs) {
      $legal_rows = [];
      $legal_headers = [
        [
          'data' => t('Number&nbsp;of&nbsp;Triage&nbsp;Runs'),
          'field' => 'count',
          'sort' => 'desc',
        ],
        [
          'data' => t('Legal&nbsp;Category&nbsp;&nbsp;&nbsp;'),
          'field' => 'category',
        ],
        [
          'data' => t('Legal&nbsp;Problem&nbsp;&nbsp;&nbsp;'),
          'field' => 'last_stop',
        ],
      ];
      $legal_query = clone $query;
      $legal_query->addExpression('COUNT(l.last_stop)', 'count');
      $legal_query->condition('l.completed', 1);
      $lquery = clone $legal_query;
      $legal_query->groupBy('l.last_stop');
      $legal_results = $legal_query->execute();
      $con->query("drop temporary table if exists bdscats");
      $con->query("create temporary table if not exists bdscats select id, tid, 0 as count, last_stop,  SPACE(100) as category from triage_log where 0");
      foreach ($legal_results as $lresult) {
        $tid = $lresult->tid;
        $parents = \Drupal::service('entity_type.manager')->getStorage('taxonomy_term')->loadAllParents($tid);
        $parents = array_reverse($parents);
        if (!isset($parents[0])) {
          $cat = "";
        }
        else{
          $cat = $parents[0]->getName();
        }
        $con->insert('bdscats')
          ->fields(array(
            'category' => $cat,
            'id'=> $lresult->id,
            'tid'=> $lresult->tid,
            'last_stop'=>$lresult->last_stop,
            'count'=>$lresult->count,
          ))
          ->execute();

        $legal_rows[] = [
          $lresult->count,
          $cat,
          $lresult->last_stop,
        ];
      }
      $thisquery = $con->select('bdscats', 'b');
      $thisquery->fields('b',array(
        'count',
        'category',
        'last_stop',
      ));
      $thisquery->extend('Drupal\Core\Database\Query\TableSortExtender')->orderByHeader($legal_headers);
      $legal_results = $thisquery->execute();
      $cat_rows = [];
      foreach ($legal_results as $lresult) {
        $cat_rows[] = array(
          $lresult->count,
          $spacer.$lresult->category,
          $spacer.$lresult->last_stop,
        );
      }
      $table1 = [
        '#theme' => 'table',
        '#header' => $legal_headers,
        '#rows' => $cat_rows,
      ];
      $out .= "<fieldset class='collapsible collapsed'>";
      $out .= "<legend>By Legal Category</legend>";
      $out .= \Drupal::service('renderer')->render($table1);
      $out .= "</fieldset>";
    }

    //By Legal Category
    $show_cats = $configvals['triage_use_categories'];
    if ($show_cats) {
      $cat_headers = [
        [
          'data' => t('Legal&nbsp;Category&nbsp;&nbsp;&nbsp;'),
          'field' => 'name',
        ],
        [
          'data' => t('Number&nbsp;of&nbsp;Triage&nbsp;Runs'),
          'field' => 'count',
        ],
      ];
      $vid = $thisvid;
      $cquery = $con->select('taxonomy_term_field_data', 't');
      $cquery->addField('t', 'tid');
      $cquery->addField('t', 'name');
      $cquery->innerJoin('taxonomy_term__parent', 'h', 'h.entity_id=t.tid');
      $cquery->condition('h.parent_target_id', 0)
        ->condition('h.bundle', $vid)
        ->orderBy('t.name');
      $results = $cquery->execute();
      $cat_rows = [];

      foreach ($results as $result) {
        $parent = $result->tid;
        $name = $result->name;
        $kids = triage_get_children_all($parent);
        $srch = [];
        foreach ($kids as $kid) {
          $srch[] = $kid->id();
        }
        $cat_query = clone $query;
        $cat_query->addExpression('COUNT(*)', 'count');
        $cat_query->condition('l.completed', 1);
        if (count($srch) > 0) {
          $cat_query->condition('l.tid', $srch, 'IN');
        }
        $cat_results = $cat_query->execute()->fetchAssoc();
        $cat_rows[] = [
          $name,
          $cat_results['count'],
        ];
      }

      $table3 = [
        '#type' => 'table',
        '#header' => $cat_headers,
        '#rows' => $cat_rows,
        '#attributes' => ['class' => ['triage_table']],
      ];
      $out .= "<fieldset class='collapsible collapsed'>";
      $out .= "<legend>By Parent Category</legend>";
      $out .= \Drupal::service('renderer')->render($table3);
      $out .= "</fieldset>";
    }

    $show_income = $configvals['triage_use_income'];
    // Income Breakdown
    if ($show_income) {
      $atriagedata = [];
      $atotal = 0;
      $inc_headers = [
        ['data' => t('Percent of Poverty')],
        ['data' => t('Incomplete&nbsp;&nbsp;&nbsp;')],
        ['data' => t('Complete&nbsp;&nbsp;&nbsp;')],
      ];
      $inc_rows = [];
      $cnt_query = clone $query;
      $or = $cnt_query->orConditionGroup()
        ->condition('l.completed', 0)
        ->condition('l.completed', NULL);
      $cnt_query->condition('l.pov', 0, '<');
      $count1 = $cnt_query->countQuery()->execute()->fetchField();
      $cnt_query = clone $query;
      $cnt_query->condition('l.completed', 1);
      $cnt_query->condition('l.pov', 0, '<');
      $count2 = $cnt_query->countQuery()->execute()->fetchField();
      $inc_rows[] = [
        'No Income Recorded',
        $spacer . $count1,
        $spacer . $count2,
      ];
      $cnt_query = clone $query;
      $or = $cnt_query->orConditionGroup()
        ->condition('l.completed', 0)
        ->condition('l.completed', NULL);
      $cnt_query->condition($or);
      $cnt_query->condition('l.pov', [0, 100], 'BETWEEN');
      $count1 = $cnt_query->countQuery()->execute()->fetchField();
      $cnt_query = clone $query;
      $cnt_query->condition('l.completed', 1);
      $cnt_query->condition('l.pov', [0, 100], 'BETWEEN');
      $count2 = $cnt_query->countQuery()->execute()->fetchField();
      $inc_rows[] = [
        'Under 100%',
        $spacer . $count1,
        $spacer . $count2,
      ];
      $atotal = $atotal + $count2;
      $atriagedata[] = $count2;
      $cnt_query = clone $query;
      $or1 = $cnt_query->orConditionGroup()
        ->condition('l.completed', 0)
        ->condition('l.completed', NULL);
      $cnt_query->condition($or1);
      $cnt_query->condition('l.pov', [100, 200], 'BETWEEN');
      $count1 = $cnt_query->countQuery()->execute()->fetchField();
      $cnt_query = clone $query;
      $cnt_query->condition('l.completed', 1);
      $cnt_query->condition('l.pov', [100, 200], 'BETWEEN');
      $count2 = $cnt_query->countQuery()->execute()->fetchField();
      $inc_rows[] = [
        '100% to 200%',
        $spacer . $count1,
        $spacer . $count2,
      ];
      $atotal = $atotal + $count2;
      $atriagedata[] = $count2;
      $cnt_query = clone $query;
      $or1 = $cnt_query->orConditionGroup()
        ->condition('l.completed', 0)
        ->condition('l.completed', NULL);
      $cnt_query->condition($or1);
      $cnt_query->condition('l.pov', [200, 300], 'BETWEEN');
      $count1 = $cnt_query->countQuery()->execute()->fetchField();
      $cnt_query = clone $query;
      $cnt_query->condition('l.completed', 1);
      $cnt_query->condition('l.pov', [200, 300], 'BETWEEN');
      $count2 = $cnt_query->countQuery()->execute()->fetchField();
      $inc_rows[] = [
        '200% to 300%',
        $spacer . $count1,
        $spacer . $count2,
      ];
      $atotal = $atotal + $count2;
      $atriagedata[] = $count2;
      $cnt_query = clone $query;
      $or = $cnt_query->orConditionGroup()
        ->condition('l.completed', 0)
        ->condition('l.completed', NULL);
      $cnt_query->condition($or);
      $cnt_query->condition('l.pov', 300, '>');
      $count1 = $cnt_query->countQuery()->execute()->fetchField();
      $cnt_query = clone $query;
      $cnt_query->condition('l.completed', 1);
      $cnt_query->condition('l.pov', 300, '>');
      $count2 = $cnt_query->countQuery()->execute()->fetchField();
      $inc_rows[] = [
        'Over 300%',
        $spacer . $count1,
        $spacer . $count2,
      ];
      $atotal = $atotal + $count2;
      $atriagedata[] = $count2;
      $table1 = [
        '#theme' => 'table',
        '#header' => $headers,
        '#rows' => $inc_rows,
      ];
      $out .= "<fieldset class='collapsible collapsed'>";
      $out .= "<legend>By Income</legend>";
      $out .= \Drupal::service('renderer')->render($table1);
      $out .= "</fieldset>";
      $atriagedata[0] = round(($atriagedata[0] / $atotal) * 100, 1);
      $atriagedata[1] = round(($atriagedata[1] / $atotal) * 100, 1);
      $atriagedata[2] = round(($atriagedata[2] / $atotal) * 100, 1);
      $atriagedata[3] = round(($atriagedata[3] / $atotal) * 100, 1);
      $out .= "</div>";
      $chart = [
        '#type' => 'chart',
        '#title' => t('Proportion of completed triage searches by income level'),
        '#chart_type' => 'pie',
        '#chart_library' => 'highcharts',
        // Allowed values: 'google' or 'highcharts'
        '#title_font_size' => 25,
        '#legend_position' => 'bottom',
        '#data_labels' => TRUE,
        '#tooltips' => TRUE,
        '#font_size' => 20,
      ];
      $chart['pie_data'] = [
        '#type' => 'chart_data',
        '#title' => t('Income Levels'),
        '#labels' => [
          'less than 100% POV',
          '100% - 200% POV',
          '200% - 300% POV',
          'more than 300% POV',
        ],
        '#data' => $atriagedata,
      ];

      $example['chart'] = $chart;

      $out .= "<div>" . \Drupal::service('renderer')->render($example) . "</div>";
    }
  }
  // Simple Listing ---------------------------------------------------------------
  else {
    $headers = [
      ['data' => t('Completed'), 'field' => 'completed'],
      ['data' => t('Date/Time'), 'field' => 'time'],
      ['data' => t('Zip Code'), 'field' => 'zipcode'],
      ['data' => t('County&nbsp;&nbsp;'), 'field' => 'county'],
      ['data' => t('%&nbsp;Poverty&nbsp;&nbsp;'), 'field' => 'pov'],
      ['data' => t('Steps&nbsp;&nbsp;'), 'field' => 'steps'],
      ['data' => t('Status&nbsp;&nbsp;'), 'field' => 'status'],
      ['data' => t('History&nbsp;&nbsp;'), 'field' => 'history'],
      ['data' => t('IP&nbsp;&nbsp;'), 'field' => 'ip'],
    ];
    $query = $con->select('triage_log', 'l');
    $query->fields('l', [
      'completed',
      'time',
      'zipcode',
      'county',
      'pov',
      'status',
      'steps',
      'history',
      'ip',
    ]);
    $query->condition('l.time', [$dmystart, $dmyend], 'BETWEEN');
    $query->condition('l.vid', $thisvid);
    $cnt_query = clone $query;
    $table_sort = $query->extend('Drupal\Core\Database\Query\TableSortExtender')->orderByHeader($headers);
    $pager = $table_sort->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(25);
    $results = $query->execute();
    $count = $cnt_query->countQuery()->execute()->fetchField();
    $out .= "Records in set: " . $count . "<br /><hr />";
    $rows = [];
    foreach ($results as $result) {
      $spacer = '';
      $rows[] = [
        $result->completed,
        date("Y-m-d H:i:s", $result->time),
        $result->zipcode,
        $result->county,
        $result->pov,
        $result->steps,
        $result->status,
        $result->history,
        $result->ip,
      ];
    }
    $build = [
      'table'           => [
        '#theme'         => 'table',
        '#attributes'    => [
          'data-striping' => 0
        ],
        '#header' => $headers,
        '#rows'   => $rows,
      ],
    ];
    //for pagination
    $build['pager'] = array(
      '#type' => 'pager'
    );
    $out .= "<fieldset>";
    $out .= "<legend>Details</legend>";
    $out .= \Drupal::service('renderer')->render($build);
    $out .= "</fieldset>";
  }
  return $out;
}

function triage_summary_excel() {
  $database = \Drupal::database();
  $dmystart = strtotime($_SESSION['triage']['triage_summary_report'][0]);
  $dmyend = strtotime($_SESSION['triage']['triage_summary_report'][1]);
  $filename = 'triage_data';
  $filename .= "_" . date("m-d-Y", time()) . ".csv";
  $sql = "select * from triage_log 
            where time between :start AND :end";
  $results = $database->query($sql, [":start" => $dmystart, ":end" => $dmyend]);
  $getkeys = $database->query('select * from triage_log')->fetchAssoc();
  $keys = array_keys($getkeys);
  $out .= "Records in set: " . $count . "<br /><hr />";
  $out .= "Data exported to Excel File";
  $GLOBALS['devel_shutdown'] = TRUE;

  // Set the headers to indicate this is a CSV file.
  header('Content-type: text/csv; charset=UTF-8');
  header('Content-Disposition: attachment; filename=' . $filename);
  header('Pragma: no-cache');
  header('Expires: 0');

  // Create a file.
  $output = fopen('php://output', 'w');

  // Column names.
  fputcsv($output, $keys);

  // Loop through the rows.
  foreach ($results as $row) {
    fputcsv($output, (array) $row);
  }

  fclose($output);
}

function triage_suggestions() {
  global $base_url;
  $database = \Drupal::database();
  $spacer = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
  $form = drupal_get_form('triage_suggestionfilter_form');
  $out = \Drupal::service('renderer')->render($form);
  $out .= "<h4>Additional User Problems</h4>";
  $out .= "<div class='suggestions-report'>";
  $dmystart = strtotime($_SESSION['triage']['triage_suggestions_report'][0]);
  $dmyend = strtotime($_SESSION['triage']['triage_suggestions_report'][1]) + 86399;
  $query = $database->select('triage_suggestions', 'l');
  $query->fields('l', ['created', 'text', 'tid']);
  $query->condition('l.created', [$dmystart, $dmyend], 'BETWEEN');
  $query->condition('l.text', '', ">");
  $query->orderBy('l.created', 'DESC');
  $results = $query->execute();
  $rows = [];
  $headers = [
    ['data' => t('Date&nbsp;&nbsp;')],
    ['data' => t('Endpoint&nbsp;&nbsp;')],
    ['data' => t('Problem or Suggestion')],
  ];
  foreach ($results as $result) {
    $term = \Drupal::service('entity_type.manager')->getStorage('taxonomy_term')->load($result->tid);
    $rows[] = [
      date("F j, Y", $result->created),
      $spacer . $term->name . " (" . $term->tid . ")",
      $spacer . "<a href='" . $base_url . "/search_all/" . $result->text . "'>" . $result->text . "</a>",
    ];
  }
  $table = theme('table', [
    'header' => $headers,
    'rows' => $rows,
    'attributes' => ['class' => ['triage_table']],
  ]);
  $out .= theme('ctools_collapsible', [
      'handle' => '<h3>Additional Problems or Suggestions</h3>',
      'content' => $table,
    ]
  );
  $out .= "</div>";
  return $out;
}