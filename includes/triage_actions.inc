<?php

use Drupal\Core\Database\Database;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\Core\Language;
use Drupal\Component\Render\FormattableMarkup;


function triage_actions_get_by_parent($tid, $pid = 0, $bundle = 'taxonomy', $ta_region = 'ta-main-panel') {
  $tempstore = \Drupal::service('tempstore.private')->get('triage');
  $language = \Drupal::languageManager()->getCurrentLanguage();
  $lang = $language->getId();
  if ($bundle == 'help' || $bundle == 'quest') {
    //$tempstore->set('triage_page_nid', 2055);
    $tid = $tempstore->get('triage_page_nid');
  }
  $con = Database::getConnection();
  $xquery = $con->select('triage_actions', 'ta');
  $xquery->fields('ta', ['id',]);
  $xquery->condition('ta.entity_id', $tid);
  $xquery->condition('ta.pid', $pid);
  $xquery->condition('ta.language', $lang);
  $xquery->condition('ta.bundle', $bundle);
  $xquery->condition('ta.region', $ta_region);
  $xquery->condition('ta.enabled', 1);
  $xitems = $xquery->execute()->fetchCol();

  $query = $con->select('triage_actions', 'ta');
  $query->fields('ta');
  $query->condition('ta.entity_id', $tid);
  $query->condition('ta.pid', $pid);
  $query->condition('ta.language', $lang);
  $query->condition('ta.bundle', $bundle);
  $query->condition('ta.region', $ta_region);
  $query->condition('ta.enabled', 1);

  $subquery = $con->select('triage_actions', 'sub');
  $subquery->fields('sub');
  $subquery->condition('sub.entity_id', $tid);
  $subquery->condition('sub.pid', $pid);
  $subquery->condition('sub.language', $lang, '!=');
  $subquery->condition('sub.bundle', $bundle);
  $subquery->condition('sub.region', $ta_region);
  $subquery->condition('sub.enabled', 1);
  if (count($xitems) > 0) {
    $subquery->condition('sub.id', $xitems, "NOT IN");
  }
  $subquery->orderBy('weight');

  $query->union($subquery, 'UNION ALL');
  $items = $query->execute()->fetchAll();
  return $items;
}

function triage_actions_get_action($items, &$output, $tid, $bundle = 'taxonomy', $ta_region = 'ta-main-panel', &$display_ray = NULL) {
  $language = \Drupal::languageManager()->getCurrentLanguage();
  $lang = $language->getId();
  //Get the underlying Triage Page nid so we can get the right variables
  $tempstore = \Drupal::service('tempstore.private')->get('triage');
  $con = Database::getConnection();
  $nid = $tempstore->get('triage_page_nid');
  $taxparents = [];
  $mytid = NULL;
  $mytid = trim($tempstore->get('my_tid'));
  if($mytid){
    $taxparents[] = trim($mytid);
  }
  $tps = NULL;
  if ($bundle == 'taxonomy' || $bundle == 'quests') {
    try {
      $tps = \Drupal::service('entity_type.manager')
        ->getStorage('taxonomy_term')
        ->loadAllParents($mytid);
    } catch (\Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException $e) {}
    foreach ($tps as $tp) {
      $taxparents[] = $tp->id();
    }
  }
  $use_div_order = ta_variable_get('triage_order_action_divs', FALSE, $nid);
  $triage_nav_type = ta_variable_get('triage_nav_type', 'bottom', $nid);
  $backtext = t(ta_variable_get('triage_reset_text', 'Back', $nid))->render();
  $nexttext = t(ta_variable_get('triage_next_text', 'Next', $nid))->render();
  $preview = $tempstore->get('triage_preview');
  $group_status = [];
  if ($tempstore->get('triage_status')) {
    $group_status = array_keys($tempstore->get('triage_status'));
  }
  $county_info = [trim($tempstore->get('my_county'))];
  $income_status = $tempstore->get('triage_eligibility');
  $ben_status = [];
  if ($tempstore->get('my_benefits')) {
    $ben_status = array_keys($tempstore->get('my_benefits'));
  }
  $groupid = $tempstore->get('triage_group_id');
  $grp_array = $con->query("select id from triage_actions 
                        where bundle=:bundle
                        and region=:region
                        and type = 'group'
                        and entity_id = :entity_id
                        order by weight", [
    ':bundle' => $bundle,
    ':region' => $ta_region,
    ':entity_id' => $tid,
  ])->fetchCol();
  $group_num = count($grp_array);
  $prev = 0;
  $next = 0;
  $itemnum = 0;
  $titlestarttag = "<h2>";
  $titleendtag = "</h2>";
  if ($ta_region == "ta-help-panel") {
    $titlestarttag = "<h3>";
    $titleendtag = "</h3>";
  }
  foreach ($items as $item) {
    $nvo = 'teaser';
    $len = 350;
    if ($item->node_view_opt > '') {
      $nvo = $item->node_view_opt;
    }
    if ($item->id == 646) {
      $bds = "";
    }
    // Visibility Logic
    if (TRUE) {
      $php_show = $item->php_show;
      if(! empty($php_show)){
        $keep_going = eval($php_show);
        if(!$keep_going){continue;}
      }
      // Show or hide items by income
      $show_inc = x_plode(",", $item->show_income);
      if ($item->trim_length) {
        $len = $item->trim_length;
      }
      if ($show_inc[0] > 0 && count(array_intersect($income_status, $show_inc)) == 0) {
        continue;
      }
      $hide_inc = x_plode(",", $item->hide_income);
      if ($hide_inc[0] > 0 && count(array_intersect($income_status, $hide_inc)) !== 0) {
        continue;
      }
      // Show or hide items by status
      $show_stat = x_plode(",", $item->show_status);
      if ($show_stat[0] > 0 && count(array_intersect($group_status, $show_stat)) == 0) {
        continue;
      }
      $hide_stat = x_plode(",", $item->hide_status);
      if ($hide_stat[0] > 0 && count(array_intersect($group_status, $hide_stat)) !== 0) {
        continue;
      }
      // Show or hide items by status
      $show_ben = x_plode(",", $item->show_benefits);
      if ($show_ben[0] > 0 && count(array_intersect($ben_status, $show_ben)) == 0) {
        continue;
      }
      $hide_ben = x_plode(",", $item->hide_benefits);
      if ($hide_ben[0] > 0 && count(array_intersect($ben_status, $hide_ben)) !== 0) {
        continue;
      }
      // Show or hide items by County
      // Carry default county info on reusables
      $show_count = x_plode(",", $item->show_county);
      if ($item->type == 'reuse') {
        if (!$show_count[0] > '') {
          $sql = 'select show_county, hide_county 
              from triage_actions 
              where id = :id
              and language = :lang';
          $act = $con->query($sql, [
            ':id' => $item->node_ref_nid,
            ':lang' => $lang,
          ])->fetch();
          if ($act) {
            $show_count = x_plode(",", $act->show_county);
          }
        }
      }
      if ($show_count[0] > '' && count(array_intersect($county_info, $show_count)) == 0) {
        continue;
      }
      // Carry default county info on reusables
      $hide_count = x_plode(",", $item->hide_county);
      if ($item->type == 'reuse') {
        if (!$hide_count[0] > '') {
          $sql = 'select show_county, hide_county 
              from triage_actions 
              where id = :id
              and language = :lang';
          $act = $con->query($sql, [
            ':id' => $item->node_ref_nid,
            ':lang' => $lang,
          ])->fetch();
          if ($act) {
            $hide_count = x_plode(",", $act->hide_county);
          }
        }
      }
      if ($hide_count[0] > '' && count(array_intersect($county_info, $hide_count)) !== 0) {
        continue;
      }
      // Show or hide items by taxonomy
      $show_tax = x_plode(",", $item->show_tax);
      if ($show_tax[0] > ''){
        $showcnt = count(array_intersect($taxparents, $show_tax));
        if($showcnt  == 0) {
          continue;
        }
      }
      $hide_tax = x_plode(",", $item->hide_tax);
      if ($hide_tax[0] > '' && count(array_intersect($taxparents, $hide_tax)) !== 0) {
        continue;
      }
    }
    // Tell session if we're offering intake, for triage log
    if ($item->intake_elig) {
      $tempstore->set('triage_intake_elig', 1);
    }
    // Get title - use display header if there is one
    $titl = t(trim($item->title))->render();
    if (trim($item->display_header) != '') {
      $titl = t(trim($item->display_header))->render();
    }
    if ($item->display_header == '<none>') {
      $titl = "";
    }
    // Process according to item type
    switch (strtolower($item->type)) {
      case 'text':
        //add or remove according to print only status stored in extra field
        if ($preview == 3 || $preview == 4) {
          if ($item->extra == 'non_print') {
            break;
          }
        }
        else {
          if ($item->extra == 'print_only') {
            break;
          }
        }
        $class = $item->classes;
        if ($class == "none") {
          $output .= "<div class='triage-action-item'>";
        }
        else {
          $output .= "<div class='triage-action-item " . $class . "'>";
        }
        if (trim($titl) > '') {
          $output .= $titlestarttag . $titl . $titleendtag;
        }
        if($item->action_text_format == "php"){
          $output .= eval($item->action_text);
        }
        else {
        $output .= $item->action_text;
        }
        $output .= "</div>";
        break;
      case 'form':
        if ($preview == 3) {
        }
        else {
          if ($item->action_text == 'triage_in_service_area_form') {
            $tempstore->set('triage_sa_extra', $item->extra);
          }
          if ($item->action_text == 'triage_suggestion_form') {
            $max_chars = $item->trim_length;
          }
          $form = \Drupal::formBuilder()->getForm("Drupal\\triage\Form\\" . $item->action_text);
          $class = $item->classes;
          $class = str_replace("none", "", $class);
          if ($item->action_text == "triage_print_form") {
            $class .= ' noprint ';
          }
          if ($item->reusable_text) {
            $class .= " mandatory ";
          }
          $output .= "<div class='triage-action-form " . $class . "'>";
          if (trim($titl) > '') {
            $output .= $titlestarttag . t($titl)->render() . $titleendtag;
          }
          $output .= \Drupal::service('renderer')->render($form);
          // TODO: SMS
          // if ($item->action_text == 'triage_print_form') {
          //     if (module_exists('twilio') && variable_get('triage_use_sms', FALSE)) {
          //         $block = module_invoke('webform', 'block_view', variable_get('triage_sms_block', 'block-webform-client-block-2252'));
          //         $output .= render($block['content']);
          //     }
          // }
          $output .= "</div>";
        }
        break;
      case 'func':
        if ($preview == 3 || $preview == 4) {
          if ($item->node_view_opt == 'non_print') {
            break;
          }
        }
        else {
          if ($item->node_view_opt == 'print_only') {
            break;
          }
        }
        // if ($item->action_text == 'triagequests') {}
        $class = $item->classes;
        if ($class == "none") {
          $classout = "<div class='triage-action-item'>";
        }
        else {
          $classout = "<div class='triage-action-item " . $class . "'>";
        }
        if (trim($titl) > '') {
          $titleout = $titlestarttag . t($titl)->render() . $titleendtag;
        }
        else {
          $titleout = '';
        }
        switch ($item->action_text) {
          case 'triagepath':
            $output .= $classout . t($titleout);
            $output .= triage_showpath($tid);
            $output .= "</div>";
            $intro = t(trim($item->extra))->render();
            $tempstore->set('triage_intro_text', t($intro)->render());
            break;
          case 'triagequests':
            if (\Drupal::currentUser()->hasPermission('view user input')) {
              $output .= $classout . $titleout;
              $output .= "<div class='triage-answers'>";
              $output .= triage_answers($tid);
              $output .= "</div>";
            }
            break;
          case 'triagedesc':
            $bds = "";
            if ($mytid) {
              $descterm = \Drupal\taxonomy\Entity\Term::load(intval($mytid));
              if ($descterm && $descterm->parent->target_id != "0") {
                $text = "";
                if (isset($descterm->get('description')->getValue()[0])) {
                  $text = $descterm->get('description')->getValue()[0]['value'];
                  if(trim($text)>"") {
                    $output .= $classout . t($titleout);
                    $output .= "<div class='triage-tax_desc'>";
                    $output .= $text;
                    $output .= "</div>";
                  }
                }
              }
            }
            break;

          case 'triage_restart':
            $output .= $classout . t($titleout);
            $output .= triage_restart();
            break;
          case 'triage_language':
            break;
        }
        break;
      case 'reuse':
        $sql = 'select title,display_header,action_text, classes, intake_elig, php_show
                    from triage_actions 
                    where id = :id
                    and language = :lang';
        $act = $con->query($sql, [
          ':id' => $item->node_ref_nid,
          ':lang' => $lang,
        ])->fetchAssoc();
        if (!$act['action_text']) {
          $sql = 'select title,display_header,action_text, classes, intake_elig, php_show  
                      from triage_actions 
                      where id = :id';
          $act = $con->query($sql, [':id' => $item->node_ref_nid])
            ->fetchAssoc();
        }
        $php_show = $act['php_show'];
        if(! empty($php_show)){
          $keep_going = eval($php_show);
          if(!$keep_going){continue;}
        }

        if ($act['intake_elig']) {
          $tempstore->set('triage_intake_elig', 1);
        }
        $titl = $act['title'];
        if ($act['display_header'] == '<none>') {
          $titl = "";
        }
        if ($item->title > '') {
          $titl = $item->title;
        }
        if ($item->display_header > '') {
          $titl = $item->display_header;
          if(trim($titl) == '<none>'){
            $titl = "";
          }
        }
        if ($act['display_header'] > '' && $lang != 'en') {
          $titl = $act['display_header'];
          if(trim($titl) == '<none>'){
            $titl = "";
          }
        }
        $class = $item->classes;
        if ($class == 'inherit' || $class == '' || is_null($class)) {
          $class = $act['classes'];
        }
        if ($class == "none") {
          $output .= "<div class='triage-action-item'>";
        }
        else {
          $output .= "<div class='triage-action-item " . $class . "'>";
        }
        if (trim($titl) > '') {
          $output .= $titlestarttag . $titl . $titleendtag;
        }
        $output .= "<p>" . $act['action_text'] . '</p>';
        $output .= "</div>";
        break;
      case 'node':
        $nodeview = $nvo;
        $nodeview = str_replace("node.", "", $nodeview);
        if (is_null($nvo) || is_int($nvo)) {
          break;
        }
        if (!$item->node_ref_nid) {
          break;
        }
        if ($tempstore->get('end_nodes')) {
          if (in_array($item->node_ref_nid, $tempstore->get('end_nodes'))) {
            break;
          }
          else {
            $endnodes = $tempstore->get('end_nodes');
            $endnodes[] = $item->node_ref_nid;
            $tempstore->set('end_nodes', $endnodes);
          }
        }
        else {
          $endnodes = $tempstore->get('end_nodes');
          $endnodes[] = $item->node_ref_nid;
          $tempstore->set('end_nodes', $endnodes);
        }
        if ($nvo == 'trim') {
          $nodeview = 'full';
        }
        $node1 = Node::load($item->node_ref_nid);
        $tnid = NULL;
        if (isset($translations[$lang])) {
          $tnid = $translations[$lang]->nid;
        }
        if ($tnid) {
          $node = Node::load($tnid);
        }
        else {
          $node = \Drupal\node\Entity\Node::load($item->node_ref_nid);
        }
        if ($node && $node->isPublished()) {
          $build = \Drupal::entityTypeManager()->getViewBuilder('node')->view($node, $nodeview, $lang);
          if ($nvo == 'trim') {
            $config = [
              'safe' => 1,
              'elements' => '-img',
            ];
            $txt = htmLawed($build['body'][0]["#markup"], $config);
            $txt1 = mb_strimwidth($txt, 0, $len, "...");
            $build['body'][0]["#markup"] = $txt1;

          }
          $titl = $node->getTitle();
          if ($item->display_header > '' && $item->display_header != '<none>') {
            $titl = $item->display_header;
          }
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
          $bds = \Drupal::service('renderer')->render($build);
          $output .= $bds;
          if ($nvo == 'trim') {
            $url = "<a href='/node/" . $item->node_ref_nid . "'>Read more...</a>";
            $output .= "<ul class='links inline'><li class='node-readmore'>" . $url . "</li><ul>";
          }
          $output .= "</div>";
        }
        break;
      case 'srch':
        $class = $item->classes;
        if ($class == "none") {
          $output .= "<div>";
        }
        else {
          $output .= "<div class='" . $class . "'>";
        }
        $output .= triage_search($tid, $item, $titl);
        $output .= "</div>";
        break;
      case 'orgsrch':
        $class = $item->classes;
        if ($item->action_text == "court") {
          $key = 'court';
        }
        else {
          $key = 'other';
          $og = x_plode(",", $item->extra);
          if (in_array(31, $og)) {
            $key = 'lawyer';
          }
        }
        $div_out = '';
        if ($class == "none") {
          if ($use_div_order) {
            if ($key > '') {
              $class = " display-wrapper type-" . $key . " ";
              $div_out .= "<div class='" . $class . "'>";
            }
            else {
              $div_out .= "<div>";
            }
          }
          else {
            $div_out .= "<div>";
          }
        }
        else {
          $div_out .= "<div class='" . $class . "'>";
        }
        $func = 'triage_orgsearch_sl';
        if (is_null($func)) {
          $func = "triage_orgsearch";
        }
        $div_out .= $func($tid, $item, $titl, $og);
        $div_out .= "</div>";
        if ($use_div_order) {
          $div_out .= "</div>";
          if ($key) {
            $display_ray[] = [
              'type' => $key,
              'text' => $div_out,
            ];
          }
          else {
            $display_ray[] = [
              'type' => 'none',
              'text' => $div_out,
            ];
          }
        }
        else {
          $output .= $div_out;
        }
        break;
      case 'div':
        $div_out = '';
        $key = NULL;
        $class = $item->classes;
        $typetid = $item->action_text;
        if ($typetid > "") {
          $thistype = $con->query('select description from taxonomy_term_data where tid=:tid', [':tid' => $typetid])
            ->fetchColumn();
          $thistype = strtolower(str_replace(" ", "-", $thistype));
          if ($thistype) {
            if ($class == "none") {
              $class = "  display-wrapper  type-" . $thistype . " ";
            }
            else {
              $class .= "  display-wrapper type-" . $thistype . " ";
            }
          }
        }

        if ($class == "none") {
          $div_out .= "<div class=' display-wrapper '>";
        }
        else {
          $div_out .= "<div class='" . $class . "'>";
        }
        if ($typetid) {
          $key = \Drupal\taxonomy\Entity\Term::load($typetid)->description->getString();
        }
        if (trim($titl) > '') {
          $div_out .= $titlestarttag . $titl . $titleendtag;
        }
        $ditems = triage_actions_get_by_parent($tid, $item->id, $bundle, $ta_region);
        triage_actions_get_action($ditems, $div_out, $tid, $bundle, $ta_region);
        $div_out .= "</div>";
        if ($use_div_order) {
          if ($key) {
            $display_ray[] = [
              'type' => $key,
              'text' => $div_out,
            ];
          }
          else {
            $display_ray[] = [
              'type' => 'none',
              'text' => $div_out,
            ];
          }
        }
        else {
          $output .= $div_out;
        }
        break;
      case 'group':
        $next = 'next-0 ';
        $prev = 'prev-0 ';
        $grp = 'grp-' . $item->id;
        if ($itemnum > 0) {
          $prev = 'prev-' . $grp_array[($itemnum - 1)] . " ";
        }
        if ($itemnum < $group_num - 1) {
          $next = 'next-' . $grp_array[($itemnum + 1)] . " ";
        }
        $class = $item->classes;
        if ($class == "none") {
          if ($itemnum == 0) {
            $output .= "<div class='triage-group " . $grp . " '>";
          }
          else {
            $output .= "<div class='triage-group hidden " . $grp . " '>";
          }
        }
        else {
          if ($itemnum == 0) {
            $output .= "<div class='triage-group " . $class . " " . $grp . " '>";
            if (trim($titl) > '') {
              $output .= $titlestarttag . $titl . $titleendtag;
            }
          }
          else {
            $output .= "<div class='triage-group hidden " . $class . " " . $grp . " '>";
            if (trim($titl) > '') {
              $output .= $titlestarttag . $titl . $titleendtag;
            }
          }
        }
        $itemnum++;
        $regions = [
          'ta-message-panel',
          'ta-main-panel',
          'ta-help-panel',
          'ta-bottom-panel',
        ];
        foreach ($regions as $region) {
          if ($region == "ta-main-panel") {
            $output .= "<div class='triage-main-info'>";
          }
          $output .= "<div class='" . $region . "'>";
          $pitems = triage_actions_get_by_parent($tid, $item->id, 'node', $region);
          triage_actions_get_action($pitems, $output, $tid, "node");
          $output .= "</div>";
          if ($region == "ta-help-panel") {
            $output .= "</div>";
          }
        }
        $output .= "<div class='ta-nav-bar'>";
        $submit_class = "triage-group-submit " . $next;
        if ($prev != 'prev-0 ') {
          $lefticon = '<img class="svg triage-nav triage-left" src="/sites/default/files/inline-images/leftnav.svg"></img>';
          $bktxt = "<div class='nav-text'>" . t($backtext)->render() . "</div>";
          $output .= "<button class='triage-group-reset " . $prev . "'>" . $lefticon . $bktxt . "</button>";
        }
        $righticon = '<img class="svg triage-nav triage-right" src="/sites/default/files/inline-images/rightnav.svg"></img>';
        $nxtxt = "<div class='nav-text'>" . $nexttext . "</div>";
        $output .= "<button tabindex='0' class='" . $submit_class . "'>" . $nxtxt . ' ' . $righticon . "</button>";
        $output .= "</div>";
        $output .= "</div>";
        break;
    }
  }
}

function x_plode($delim, $str) {
  if (strpos($str, $delim) == FALSE && trim($str) > "") {
    return [$str];
  }
  return explode($delim, $str);
}

function in_array_r($needle, $haystack) {
  $found = FALSE;
  foreach ($haystack as $item) {
    if ($item === $needle) {
      $found = TRUE;
      break;
    }
    elseif (is_array($item)) {
      $found = in_array_r($needle, $item);
      if ($found) {
        break;
      }
    }
  }
  return $found;
}

function triage_showpath($tid) {
  $language = \Drupal::languageManager()->getCurrentLanguage();
  $lang = $language->getId();
  $tempstore = \Drupal::service('tempstore.private')->get('triage');
  $out = "<div class='triage-path'>";
  $out .= "<div class='triage-path-content clearfix'>";
  $breadcrumb = triage_crumb($tid, FALSE, TRUE);
  try {
    $parents = \Drupal::service('entity_type.manager')
      ->getStorage('taxonomy_term')
      ->loadAllParents($tid);
  } catch (\Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException $e) {
  }
  $parents = array_reverse($parents);
  if (!isset($parents[0])) {
    return '';
  }
  $thistid = $parents[0]->id();
  $journey = $tempstore->get('triage_journey');
  $journey = NULL; //history variable needs some work;for now reconstruct
  if ($journey) {
    $thistid = 0;
    foreach ($journey as $key => $value) {
      if ($thistid == 0) {
        $thistid = $key;
      }
    }
  }
  $bds = "";
  if (!$thistid) {
    return "";
  }
  $term = \Drupal\taxonomy\Entity\Term::load($thistid);
  $term = \Drupal::service('entity.repository')
    ->getTranslationFromContext($term, $lang); // The important part!
  $image = $term->get('field_icon')->getValue();
  $out .= "<div class='ta-answers-left'>";
  $out .= "<div class='triage-category'>" . t('Legal Category') . "</div>";
  $img_link = "";
  $ext = "";
  $imgclass = "";
  if ($image) {
    $fid = $image[0]['target_id'];
    $img = \Drupal\file\Entity\File::load($fid);
    if ($img) {
      $path = $img->getFileUri();
      $path_parts = pathinfo($path);
      $ext = $path_parts['extension'];
      if ($ext == "svg") {
        $imgclass = "svg";
        $url = file_create_url($path);
      }
      else {
        $url = \Drupal\image\Entity\ImageStyle::load('thumbnail')
          ->buildUrl($path);
      }
      if ($url) {
        $alt = " alt='icon for " . t($term->name->getString()) . "' ";
        $img_link = "<img class='" . $imgclass . "' " . $alt . " src='" . $url . "' />";
      }
      $out .= "<div class='triage-image'>" . $img_link . "</div>";
    }
  }
  $out .= "<div class='triage-title'>" . t($breadcrumb[0]) . "</div></div>";
  unset($breadcrumb[0]);
  $out .= "<div class='ta-answers-right'>";
  $intro = $tempstore->get('triage_intro_text');
  if (!empty($intro)) {
    $out .= t("You said") . ":";
  }
  $journey = $tempstore->get('triage_journey');
  $journey = NULL; //history variable needs some work;for now reconstruct
  if ($journey) {
    $breadcrumb = $journey;
  }
  $out .= "<ul>";
  foreach ($breadcrumb as $crumb) {
    $out .= "<li>" . $crumb . "</li>";
  }
  $out .= "</ul>";
  $out .= "</div></div></div>";
  return $out;
}

function triage_actions_mail($from = 'default_from', $to, $subject, $msg) {
  $my_module = 'triage_actions';
  $my_mail_token = microtime();
  if ($from == 'default_from') {
    // Change this to your own default 'from' email address.
    $from = \Drupal::config('system.site')->get('mail');;
  }
  $send_mail = new \Drupal\Core\Mail\Plugin\Mail\PhpMail(); // this is used to send HTML emails
  $message['headers'] = [
    'content-type' => 'text/html; charset=UTF-8"',
    'MIME-Version' => '1.0',
    'reply-to' => $from,
    'from' => $from,
  ];
  $msg = "<head><style>.svg{display: none;}.triage-path-content{display: flex;}</style></head><body>" . $msg . "</body>";
  $message['to'] = $to;
  $message['subject'] = $subject;
  $message['body'] = $msg;
  if ($send_mail->mail($message)) {
    return TRUE;
  }
  else {
    return FALSE;
  }
}

function triage_main_admin() {
  //  return "HI";
  $con = Database::getConnection();
  $tempstore = \Drupal::service('tempstore.private')->get('triage');
  $curr_path = \Drupal::service('path.current')->getPath();
  $tempstore->set('triage_return_to', $curr_path);
  $vid = $tempstore->get('triage_actions_admin_voc');
  $parent = $tempstore->get('triage_actions_admin_parent');
  if (!$parent) {
    $parent = 0;
  }
  $output = '<h1>Triage Action Administration</h1>';
  $form = \Drupal::formBuilder()
    ->getForm("Drupal\\triage\Form\\triage_admin_actions_form");
  $output .= \Drupal::service('renderer')->render($form);
  $voc = \Drupal\taxonomy\Entity\Vocabulary::load($vid);
  $tree = triage_get_nested_tree($vid, NULL, $parent);
  $rtree = triage_nested_tree_render($tree);
  $output .= \Drupal::service('renderer')->render($rtree);
  return $output;
}

function triage_get_nested_tree($vid, $max_depth = NULL, $parent = 0, $parents_index = [], $depth = 0) {
  $terms = \Drupal::entityTypeManager()
    ->getStorage('taxonomy_term')
    ->loadTree($vid);
  foreach ($terms as $term) {
    foreach ($term->parents as $term_parent) {
      if ($term_parent == $parent) {
        $return[$term->tid] = $term;
      }
      else {
        $parents_index[$term_parent][$term->tid] = $term;
      }
    }
    $thisterm = \Drupal\taxonomy\Entity\Term::load($term->tid);
    if ($thisterm) {
      $vid = $thisterm->bundle();
      $fld = 'field_triage_ref_' . $vid;
      if($vid == "aba_questions"){
        $fld = 'field_vttriage_ref';
      }

    }
  }
  foreach ($return as $term) {
    $bds = "";
    $vid = $term->vid;
    if (isset($parents_index[$term->tid]) && (is_null($max_depth) || $depth < $max_depth)) {
      $term->children = triage_get_nested_tree($vid, $max_depth, $term->tid, $parents_index, $depth + 1);
    }
  }
  return $return;
}

function triage_nested_tree_render($tree, $recurring = FALSE) {
  global $base_url;
  $items = [];
  if (count($tree)) {
    foreach ($tree as $term) {
      $addtid = '';
      $addterm = NULL;
      $addname = '';
      $thisterm = \Drupal\taxonomy\Entity\Term::load($term->tid);
      $thisparent = $term->parents[0];
      if ($thisterm) {
        $vid = $thisterm->bundle();
        if(strlen(trim($vid)) > 20){
          $voc = \Drupal\taxonomy\Entity\Vocabulary::load($vid);
          if(isset($voc->short_name)) {
            $fld = 'field_triage_ref_' . $voc->short_name;
          }
          else{
            $fld = 'field_triage_ref_' . substr($vid,0,20);
            $fld = 'field_triage_ref_vet_risk';
          }
        }
        else {
        $fld = 'field_triage_ref_' . $vid;
        }
        if($vid == "aba_questions"){
          $fld = 'field_vttriage_ref';
        }
        $addtid = $thisterm->get($fld)->getValue();
        if ($addtid) {
          if (is_array($addtid)) {
            if (isset($addtid[0]['target_id'])) {
              $addtid = $addtid[0]['target_id'];
            }
            else {
              $addtid = NULL;
            }
          }
          else {
            $addtid = NULL;
          }
        }
        if ($addtid) {
          $addterm = \Drupal\taxonomy\Entity\Term::load($addtid);
          if ($addterm) {
            $addname = $addterm->getName();
          }
        }
      }
      $pbutton = '';
      $ebutton = '';
      $tx = \Drupal::service('entity_type.manager')
        ->getStorage('taxonomy_term')
        ->loadAllParents($term->tid);
      $has_children = count(\Drupal::entityTypeManager()
        ->getStorage('taxonomy_term')
        ->loadTree($term->vid, $term->tid));
      $class = '';
      $class2 = "class='transbutton' title='Translations incomplete - click to add translations'";
      if (triage_actions_transcomplete($term->tid)) {
        $class2 = "class='transbutton transcomplete' title='Click to edit existing translations'";
      }
      $class3 = "class='preview-button' title='click for Preview'";
      $class4 = "class='edit-button' title='Edit Taxonomy Term'";
      $text = 'T';
      $path = $base_url . '/taxonomy/term/' . $term->tid . '/actions';
      $tpath = $base_url . '/taxonomy/term/' . $term->tid . '/translations';
      $ppath = $base_url . '/triage_actions_process/' . $term->tid . '/2';
      $title = '';
      if (triage_actions_has_actions($term->tid) || $addname > '') {
        if ($addname > '') {
          $class .= " has-reference ";
          $title = $addterm->id() . " - " . $addname;
        }
        else {
          $class .= " has-actions ";
        }
        $pbutton = '<a ' . $class3 . ' href="' . $ppath . '">&#xf06e;</a>';
      }
      if ($has_children) {
        $path = $base_url . '/taxonomy/term/' . $term->tid . '/edit';
        $epath = $path;
        $etext = "E";
        $ebutton = '<a ' . $class4 . ' href="' . $epath . '">' . $etext . '</a>';

      }
      if ($tx == NULL) {
        if ($thisparent == "0") {
          $item = [
            '#markup' => '<a class="accordion ' . $class . '" href ="' . $path . '">' . $term->name . '</a>',
            '#wrapper_attributes' => [
              'class' => ['triage-admin-top', 'collapsed'],
            ],
          ];
        }
        else {
          $item = [
            '#markup' => '<a class="' . $class . '" href ="' . $path . '">' . $term->name . '</a>',
          ];
        }

      }
      else {
        $tbutton = '<a ' . $class2 . ' href="' . $tpath . '">' . $text . '</a>';
        if ($thisparent == "0") {
          $item = [
            '#markup' => $pbutton . $ebutton . $tbutton . '<a class="accordion ' . $class . '" title="' . $title . '" href ="' . $path . '">' . $term->name . '</a>',
            '#wrapper_attributes' => [
              'class' => ['triage-admin-top', 'collapsed'],
            ],
          ];
        }
        else {
          $item = [
            '#markup' => $pbutton . $ebutton . $tbutton . '<a class="' . $class . '" title="' . $title . '" href ="' . $path . '">' . $term->name . '</a>',
          ];
        }
      }
      if (isset($term->children)) {
        $item["children"] = triage_nested_tree_render($term->children, TRUE);
      }
      $items[] = $item;
    }
  }
  $bds = '';
  if ($recurring) {
    return $items;
  }
  return [
    '#theme' => 'item_list',
    '#items' => $items,
    '#attributes' => ['class' => 'taxonomy-tree'],
  ];
}

function triage_actions_has_actions($tid) {
  $con = Database::getConnection();
  $ret = FALSE;
  //$items = db_query('select id from triage_actions where entity_id = :entity_id', array(':entity_id' => $tid))->fetchCol();
  $qry = $con->select('triage_actions', 't');
  $qry->addField('t', 'id');
  $qry->condition('t.entity_id', $tid);
  $items = $qry->execute()->fetchCol();
  if (count($items) > 0) {
    $ret = TRUE;
  }
  return $ret;
}

function triage_actions_endpoints() {
  $output = "";
  $endpoints = [];
  $con = Database::getConnection();
  $tempstore = \Drupal::service('tempstore.private')->get('triage');
  $curr_path = \Drupal::service('path.current')->getPath();
  $tempstore->set('triage_return_to', $curr_path);
  $output = '<h1>Triage Endpoint Check</h1>';
  $qry = $con->select("taxonomy_term_field_data", "t");
  $qry->fields("t");
  $terms = $qry->condition("t.vid", "casework")->execute();
  foreach ($terms as $term) {
    $tid = $term->tid;
    $kids = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadChildren($tid);
    if (!$kids) {
      $thisterm = \Drupal\taxonomy\Entity\Term::load($tid);
      $endpoints[$tid] = $thisterm->getName();
    }
  }
  return $endpoints;

}

function triage_actions_transcomplete($tid = 0) {
  $con = Database::getConnection();
  $langs = \Drupal::languageManager()->getLanguages();
  $default_lang = \Drupal::LanguageManager()->getDefaultLanguage();
  foreach ($langs as $lang) {
    $opt = $lang->getId();
    $find = NULL;
    $qry = $con->select("taxonomy_term_field_data", "t");
    $qry->addField("t", "tid");
    $qry->condition("t.tid", $tid)
      ->condition("t.langcode", $opt);
    $find = $qry->execute()->fetchColumn();
    if (!$find) {
      return FALSE;
    }
  }
  return TRUE;
}

function triage_actions_trans2complete($id) {
  $con = Database::getConnection();
  $langs = \Drupal::languageManager()->getLanguages();
  $default_lang = \Drupal::LanguageManager()->getDefaultLanguage();
  foreach ($langs as $lang) {
    $opt = $lang->getId();
    $find = NULL;
    $qry = $con->select("triage_actions", "t");
    $qry->addField("t", "id");
    $qry->condition("t.id", $id)
      ->condition("t.language", $opt);
    $find = $qry->execute()->fetchColumn();
    if (!$find) {
      return FALSE;
    }
  }
  return TRUE;
}

function triage_actions_translist($id = NULL, $type = 'text') {
  $output = "";
  if (is_null($id)) {
    return "No action was selected";
  }
  $rows = [];
  $con = Database::getConnection();
  $langs = \Drupal::languageManager()->getLanguages();
  $default_lang = \Drupal::LanguageManager()->getDefaultLanguage();
  foreach ($langs as $lang) {
    $opt = $lang->getId();
    $find = NULL;
    $qry = $con->select("triage_actions", "t");
    $qry->addField("t", "title");
    $qry->condition("t.id", $id)->condition("t.language", $opt);
    $find = $qry->execute()->fetchColumn();
    $key = $opt;
    $lang = $langs['#options'][$opt];
    // see if action exists for this language
    $title = $con->query('select title from triage_actions 
              where id = :id', array(':id' => $id))->fetchColumn();
    $status = "No translation yet";
    $path = "/taxonomy/actions/" . $id . "/" . $type . "/edit/0/" . $key;
    $op = ['#markup' => "<a href='" . $path . "'>Add</a>"];
    if ($find) {
      $status = 'Translated';
      $path = "/taxonomy/actions/" . $id . "/" . $type . "/edit/0/" . $opt;
      $op = ['#markup' => "<a href='" . $path . "'>Edit</a>"];
      //$op = l('Edit', $path, $options);
    }
    $rows[] = [
      $opt,
      $find,
      $status,
      render($op),
    ];
  }
  //dsm($rows);
  $header = [t('Language'), t('Title'), t('Status'), t('Op')];
  $output .= "<div id='triage-actions-trans'>";
  $table1 = [
    '#theme' => 'table',
    '#header' => $header,
    '#rows' => $rows,
  ];
  $output .= \Drupal::service('renderer')->render($table1);
  $output .= "</div>";
  return $output;

}

function triage_answers($tid, $nopov = FALSE) {
  $con = Database::getConnection();
  $tempstore = \Drupal::service('tempstore.private')->get('triage');
  $form_opts = [
    'none' => 'None',
    'triage_zip_form' => 'Zip Code or Town',
    'triage_senior_form' => 'Older than 60',
    'triage_pov_form' => 'Income Info',
    'triage_bank_form' => 'Bank Account',
    'triage_status_form' => 'Status or Group',
  ];
  $out = '';
  $pov = triage_percent_pov();
  if (triage_is_form_used('triage_zip_form')) {
    $out .= "Zipcode: " . $tempstore->get('my_zip') . "<br />";
    $out .= "County: " . $tempstore->get('my_county') . "<br />";
    $out .= "Town: " . $tempstore->get('my_town') . "<br />";
    $out .= "State: " . $tempstore->get('my_state') . "<br />";
    //    $out .= "Lat: " . $_SESSION['triage']['my_lat'] . "<br />";
    //    $out .= "Long: " . $_SESSION['triage']['my_long'] . "<br />";
  }
  if (triage_is_form_used('triage_location_form')) {
    $out .= "County: " . $tempstore->get('my_county') . "<br />";
    $kind = $tempstore->get('triage_kind_of_help');
    $help = $con->query('select name from taxonomy_term_data where tid = :tid', [':tid' => $kind])
      ->fetchColumn();
    if ($help) {
      $out .= "I am looking for : " . $help . "<br />";
    }
  }
  if (triage_is_form_used('triage_pov_form') || triage_is_form_used('triage_income_form')) {
    $txt = $tempstore->get('triage_income_period');
    $out .= "Household Size: " . $tempstore->get('my_house_size') . "<br />";
    $out .= $txt . " Income: " . $tempstore->get('my_house_income') . "<br />";
    if (\Drupal::currentUser()->isAuthenticated() && !$nopov) {
      $out .= "Percent of Poverty: " . $pov . "%<br />";
    }
  }
  if (triage_is_form_used('triage_bank_form')) {
    $out .= "Bank Account Total: " . $tempstore->get('triage_bank_amt') . "<br />";
  }
  if (triage_is_form_used('triage_status_form')) {
    $vals = $tempstore->get('triage_status');
    if (count($vals) > 0) {
      $sql = 'select name from taxonomy_term_field_data where tid in (' . implode(",", $vals) . ')';
      $vTypes = $con->query($sql)->fetchCol();
      if (count($vTypes) > 0) {
        $out .= "Status Checks:<br />";
      }
      foreach ($vTypes as $stat) {
        $out .= '&nbsp&nbsp&nbsp' . $stat . "<br />";
      }
    }
    if (triage_is_form_used('triage_income_form')) {
      $vals = $tempstore->get('my_benefits');
      if (count($vals) > 0) {
        $sql = 'select name from taxonomy_term_field_data where tid in (' . implode(",", $vals) . ')';
        $vTypes = $con->query($sql)->fetchCol();
        if (count($vTypes) > 0) {
          $out .= "Public Benefits:<br />";
        }
        foreach ($vTypes as $stat) {
          $out .= '&nbsp&nbsp&nbsp' . $stat . "<br />";
        }
        //        if (user_is_logged_in() && !$nopov) {
        //          $score = triage_get_aggregate();
        //          $out .= "Aggregate Value: " . $score . "<br />";
        //        }
      }
    }
  }
  return $out;
}
