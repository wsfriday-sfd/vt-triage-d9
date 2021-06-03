<?php

namespace Drupal\triage\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Component\Utility\Tags;
use Drupal\Component\Utility\Unicode;

/**
 * Class triageController.
 */
class triageController extends ControllerBase {

  /**
   * Triage_actions_process.
   *
   * @return array
   *   Return Hello string.
   */

  public function triage_post() {
    triage_post();
    return [
      '#type' => 'markup',
      '#markup' => "",
    ];
  }

  public function triage_nothanks() {
    triage_nothanks();
    return [
      '#type' => 'markup',
      '#markup' => "",
    ];
  }

  public function triage_write_hist() {
    triage_write_history();
    return [
      '#type' => 'markup',
      '#markup' => "",
    ];
  }

  public function getTriageTitle($voc_name = 'vt_triage') {
    $title = "Help Navigator";
    $node = triage_get_page($voc_name);
    if ($node){
      $title = $node->getTitle();
    }
    return $title ;
  }

  public function triage_actions_process($tid = NULL, $preview = 0) {
    $term = \Drupal\taxonomy\Entity\Term::load($tid);
    $mytitle = 'Help for Your Legal Problem';
    $mysubtitle = "";
    if (!is_null($term->field_results_title)) {
      $myhelp = $term->field_results_title->getString();
    }
    if ($myhelp) {
      $mysubtitle .= '<h3 class="triage-endpoint-subtitle">' . $myhelp . '</h3>';
    }

    $output[] = [
      '#type' => 'inline_template',
      '#template' => '{{ yourvar }} {{ yourhtml | raw }}',
      '#context' => [
        'yourhtml' => $mysubtitle . triage_output($tid, $preview),
      ],
    ];
    return $output;
  }

  public function triage($voc_name = 'vt_triage', $thistid = NULL, $return_js = 0, $groupid = 0) {
    if(is_null($voc_name)){$voc_name = 'vt_triage';}
    $tempstore = \Drupal::service('tempstore.private')->get('triage');
    $con = Database::getConnection();
    $qry = $con->select('ta_variable', 'ta');
    $qry->addField('ta', 'value');
    $qry->addField('ta', 'nid');
    $qry->condition('ta.name', 'triage_vocabulary');
    $vals = $qry->execute()->fetchAll();
    $pagenid = 0;
    foreach ($vals as $val) {
      $vl = unserialize($val->value);
      if ($vl == $voc_name) {
        $pagenid = $val->nid;
      }
    }
    $tempstore->set('triage_page_nid', $pagenid);
    if ($return_js) {
      $output = triage_build($voc_name, $thistid, $return_js, $groupid);
      return new JsonResponse($output);
    }
    $output[] = [
      '#type' => 'inline_template',
      '#template' => '{{ yourvar }} {{ yourhtml | raw }}',
      '#context' => [
        'yourhtml' => triage_build($voc_name, $thistid, $return_js, $groupid),
      ],
    ];
    return $output;

  }

  public function triage_actions_admin() {
    $tempstore = \Drupal::service('tempstore.private')->get('triage');
    $tempstore->set('my_bundle','taxonomy');
    $ret = triage_main_admin();
    $output[] = [
      '#type' => 'inline_template',
      '#template' => '{{ yourvar }} {{ yourhtml | raw }}',
      '#context' => [
        'yourhtml' => $ret,
      ],
    ];
    return $output;
  }

  public function triage_actions_editor($id = 0, $type = 'text', $reuseit = 0, $lang = 'en') {
    $ret = "";
    $form = \Drupal::formBuilder()
      ->getForm("Drupal\\triage\Form\\triage_act_edit_form", $id, $type, $reuseit, $lang);
    $ret .= \Drupal::service('renderer')->render($form);
    $output[] = [
      '#type' => 'inline_template',
      '#template' => '{{ yourvar }} {{ yourhtml | raw }}',
      '#context' => [
        'yourhtml' => $ret,
      ],
    ];
    return $output;
  }

  public function triage_actions_translate($id = 0) {
   $ret = triage_actions_translist($id);
    $output[] = [
      '#type' => 'inline_template',
      '#template' => '{{ yourvar }} {{ yourhtml | raw }}',
      '#context' => [
        'yourhtml' => $ret,
      ],
    ];
    return $output;
  }

  public function triage_summary_report() {
    $ret = triage_summary();
    $output[] = [
      '#type' => 'inline_template',
      '#template' => '{{ yourvar }} {{ yourhtml | raw }}',
      '#context' => [
        'yourhtml' => $ret,
      ],
    ];
    return $output;
  }

  public function triage_node_actions($node, $type){
    $ret = "";
    $vid = ta_variable_get('triage_vocabulary', '', $node);
    $tempstore = \Drupal::service('tempstore.private')->get('triage');
    $tempstore->set('my_bundle',$type);
    $tempstore->set('triage_page_nid', $node);
    $tempstore->set('triage_actions_admin_voc', $vid);
    $form = \Drupal::formBuilder()
      ->getForm("\Drupal\\triage\Form\\triage_actions_form", $node);
    $ret .= \Drupal::service('renderer')->render($form);
    $output[] = [
      '#type' => 'inline_template',
      '#template' => '{{ yourvar }} {{ yourhtml | raw }}',
      '#context' => [
        'yourhtml' => $ret,
      ],
    ];
    return $output;

  }

  public function triage_autocomplete(Request $request, $field_name = null) {
    $con = Database::getConnection();
    $results = [];
    if ($input = $request->query->get('q')) {
          $items = array();
          $results = array();
          $query = $con->select('node_field_data', 'n');
          // Select rows that match the string
          $return = $query
            ->fields('n', array('title', 'nid'))
            ->condition('n.title', '%' . $query->escapeLike($input) . '%', 'LIKE')
            ->orderBy('title')
            ->range(0, 20)
            ->execute();
          foreach ($return as $obj) {
            $items[$obj->nid] = $obj->title;
            $results[] = [
              'value' => $obj->nid,
              'label' => $obj->title,
            ];
          }
    }
    return new JsonResponse($results);
  }

  public function triage_admin() {
    $tempstore = \Drupal::service('tempstore.private')->get('triage');
    $ret = triage_admin();
    $output[] = [
      '#type' => 'inline_template',
      '#template' => '{{ yourvar }} {{ yourhtml | raw }}',
      '#context' => [
        'yourhtml' => $ret,
      ],
    ];
    return $output;
  }

  public function triage_del() {
    $ret = triage_del();
    return new JsonResponse($ret);
  }

  public function triage_delete() {
    $data = $_POST;
    $id = key($data);
    triage_del_actions($id);
    return [
      '#type' => 'markup',
      '#markup' => "",
    ];
  }

  public function triage_reuse() {
    $ret = triage_actions_reusable_admin();
    $output[] = [
      '#type' => 'inline_template',
      '#template' => '{{ yourvar }} {{ yourhtml | raw }}',
      '#context' => [
        'yourhtml' => $ret,
      ],
    ];
    return $output;
  }
}
