<?php

use Drupal\Core\Database\Database;

function triage_orgfind($tid = 0, $item, $titl = '', $orgtype = NULL) {
  $output = '';
  $nvo = 'teaser';
  $len = 350;
  $types = explode(",", $item->action_text);
  if ($item->node_view_opt > '') {
    $nvo = $item->node_view_opt;
  }
  $status = array();
  if ($_SESSION['triage']['triage_status']) {
    $status = array_values($_SESSION['triage']['triage_status']);
  }

}

function triage_orgsearch_sl($tid = 0, $item, $titl = '') {
  $tempstore = \Drupal::service('tempstore.private')->get('triage');
  $con = Database::getConnection();
  $database = \Drupal::database();
  $endterm =  \Drupal\taxonomy\Entity\Term::load($tid);
  $triage_voc = $endterm->bundle();
  $output = '';
  $nvo = 'teaser';
  $len = 350;
  $types = explode(",", $item->action_text);
  if ($item->node_view_opt > '') {
    $nvo = $item->node_view_opt;
  }
  $srchtbl = "field_legal_services_provided";
  $cnty = "field_counties_served";
  $inc = null;
  $srchfld = $srchtbl . "_target_id";
  $cntyfld = $cnty . "_value";
  $inctype = 'taxonomy_term_reference';
  if ($inc) {
    $inctype = $database->query('select type from field_config where field_name = :fn', [':fn' => $inc])->fetchcolumn();
  }
  if ($inctype == 'taxonomy_term_reference') {
    $incfld = $inc . "_tid";
  }
  else {
    $incfld = $inc . "_value";
  }

  if ($srchtbl == '') {
    return '';
  }
  $srchtbl = 'taxonomy_term__' . $srchtbl;
  $cntytbl = 'node__' . $cnty;
  $inctbl = 'node__' . $inc;
  $valquery = $con->select("taxonomy_term__field_legal_services_provided", 'x');
  $valquery->addField("x", "field_legal_services_provided_target_id", 'tid');
  $valquery->condition('x.entity_id', $tid);
  $valquery->condition('x.bundle', $triage_voc);
  $valset = $valquery->execute()->fetchAll();
  $query = $con->select('node', 'n');
  $query->addField('n', 'nid');
  $query->innerJoin('node__field_active', 'a', 'a.entity_id = n.nid');
  $query->innerJoin('node__field_legal_services_provided', 's', 's.entity_id = n.nid');
  $query->condition('s.field_legal_services_provided_target_id', $valquery, "IN");
  $query->condition('s.bundle', 'organization');
  $query->condition('a.field_active_value', 1);
  if (count($types) > 0 && $types[0] > "") {
    $query->condition('n.type', ($types), "IN");
  }
  if ($cnty) {
    $mycnty = $tempstore->get('my_county');
    $my_state = $tempstore->get('my_state');
    if ($mycnty && $my_state) {
      $cntqry = $con->select('bdscounty_fips', 'c');
      $cntqry->addField('c','id');
      $cntqry->condition('c.state_name', $my_state)
             ->condition('c.county_name', $mycnty);
      $cntynid = $cntqry->execute()->fetchColumn();
      if ($cntynid) {
        $query->leftJoin($cntytbl, 'c', 'c.entity_id=n.nid');
        $query->leftJoin("node__field_states_served", 'ss', 'ss.entity_id=n.nid');
        $db_or = $query->orConditionGroup()
          ->condition('c.' . $cntyfld, $cntynid)
          ->condition('ss.field_states_served_target_id', 4198);
        $query->condition($db_or);
      }
      else{
        $query->leftJoin("node__field_states_served", 'ss', 'ss.entity_id=n.nid');
        $query->condition('ss.field_states_served_target_id', 4198);
      }
    }
    else{
      $query->leftJoin("node__field_states_served", 'ss', 'ss.entity_id=n.nid');
      $query->condition('ss.field_states_served_target_id', 4198);
    }
  }
  else{
    $query->leftJoin("node__field_states_served", 'ss', 'ss.entity_id=n.nid');
    $query->condition('ss.field_states_served_target_id', 4198);
  }
  if($inctype !== 'taxonomy_term_reference') {
    if ($inc) {
      $pov = $_SESSION['triage']['triage_percent_pov'];
      if ($pov > 0) {
        $query->leftJoin($inctbl, 'i', 'i.entity_id = n.nid');
        $db_or = $query->orConditionGroup()
          ->condition('i.' . $incfld, $pov, '>=')
          ->condition('i.' . $incfld, 0)
          ->condition('i.' . $incfld, null)
          ->condition('i.' . $incfld, "");
        $query->condition($db_or);
      }
    }
  }

  $query->groupBy('n.nid');
  $results = $query->execute()->fetchCol();
  $nodeview = $nvo;
  if (is_null($nvo) || is_int($nvo)) {
    $nodeview = 'teaser';
  }
  if ($nvo == 'trim') {
    $nodeview = 'full';
  }
  $master_count = 0;
  foreach ($results as $nid) {
    if ($inctype == 'taxonomy_term_reference') {
      if ($inc) {
        $query = $con->select($inctbl, "i");
        $query->condition('i.entity_id', $nid);
        $query->innerJoin('taxonomy_term_data', 't', 't.tid = i.' . $incfld);
        $query->addField('t', 'tid');
        $inc_elig_tid = $query->execute()->fetchColumn();
        if ($inc_elig_tid) {
          if (!in_array($inc_elig_tid, $_SESSION['triage']['triage_eligibility'])) {
            continue;
          }
        }
      }
    }
    $master_count++;
    $node = \Drupal\node\Entity\Node::load($nid);
    $view_builder = \Drupal::entityTypeManager()->getViewBuilder('node');
    $build = $view_builder->view($node, $nodeview);
    if ($nvo == 'trim') {
      $config = array(
        'safe' => 1,
        'elements' => '-img',
      );
      $txt = htmLawed($build['body'][0]["#markup"], $config);
      $txt1 = mb_strimwidth($txt, 0, $len, "...");
      $build['body'][0]["#markup"] = $txt1;

    }
    unset($build['links']['blog']);
    unset($build['links']['comment']);
    $build['body']['#object']->title = $node->title;
    unset($build['print_links']);
    $class = $item->classes;
    if ($nvo == 'triage_view_mode') {
      $class .= ' triage-remove-a ';
    }
    if ($class == "none") {
      $output .= "<div class='triage-action-item'>";
    }
    else {
      $output .= "<div class='triage-action-item " . $class . "'>";
    }
    $output .= render($build);
    if ($nvo == 'trim') {
      $output .= "<ul class='links inline'><li class='node-readmore'>" . l('Read More', 'node/' . $nid) . "</li></ul>";
    }
    $output .= "</div>";
  }
  if ($results) {
    if ($titl > '') {
      $output = "<h4>" . $titl . '</h4>' . $output;
    }
  }
  if ($master_count == 0) {
    $output = '';
  }
  return $output;
}
