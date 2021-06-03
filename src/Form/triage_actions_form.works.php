<?php

namespace Drupal\triage\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Core\Database\Database;



class triage_actions_form extends FormBase
{
  /**
   * {@inheritdoc}
   */
  public function getFormId()
  {
    return 'triage_actions_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $term = null)
  {
    require_once DRUPAL_ROOT . '/' . drupal_get_path('module', 'triage') . '/includes/triage_actions.inc';
    require_once DRUPAL_ROOT . '/' . drupal_get_path('module', 'triage') . '/includes/triage.orgsearch.inc';
    require_once DRUPAL_ROOT . '/modules/contrib/htmlawed/htmLawed/htmLawed.php';
    $tempstore = \Drupal::service('tempstore.private')->get('triage');
    global $base_url;
    $config =  \Drupal::config('triage.admin_voc');
    $vid = "casework";
    if($config) {
      $vid = $config->get('admin_voc');
    }
    $node = triage_get_page($vid);
    $nid = $node->id();

//    drupal_add_js($base_url . "/misc/tabledrag.js", "file");
    $bundle = $tempstore->get('my_bundle');
    $default_region = 'ta-main-panel';
    $actionTypes = array(
      'text' => 'Custom Text',
      'reuse' => 'Reusable Text',
      'node' => 'Node Content',
      'div' => 'Display Wrapper',
    );
    $region_opts = array(
      'none' => 'None',
      'ta-message-panel' => 'Top Message Panel',
      'ta-main-panel' => 'Main Panel',
      'ta-help-panel' => 'Help Panel',
      'ta-bottom-panel' => 'Bottom Panel',
    );
    switch ($bundle) {
      case 'taxonomy':
        $default_region = 'ta-main-panel';
        break;
      case 'help':
        $default_region = 'ta-help-panel';
        $actionTypes['srch'] = 'Triage Search Results';
        $actionTypes['orgsrch'] = 'Triage Organizational Search Results';
        $actionTypes['form'] = 'User Info Form';
        $actionTypes['func'] = 'Miscellaneous Functions';
        break;
      case 'node':
        $default_region = 'ta-main-panel';
        $actionTypes['form'] = 'User Info Form';
        $actionTypes['group'] = 'Navigation Wrapper';
        break;
      case 'quests':
        unset($region_opts['ta-main-panel']);
        $actionTypes['func'] = 'Miscellaneous Functions';
        $actionTypes['form'] = 'User Info Form';
        break;
    }
    $this_tid = $term;
    //$this_tid = 917;
    $items = array();
    $tems = array();
    $form = array();
    $form['#tree'] = true;
    $form['#attached']['library'][] = array('system', 'tabledrag');
    $realitems = triage_actions_parent_get_data($bundle, $this_tid);
    foreach ($realitems as $ri) {
      $depth = 0;
      if ($ri->pid > 0) {
        $depth = 1;
      }
      $tems[] = array(
        'id' => $ri->id,
        'pid' => $ri->pid,
        'title' => $ri->title,
        'depth' => $ri->depth,
        'weight' => $ri->weight,
        'text' => $ri->action_text,
        'language' => $ri->language,
        'region' => $ri->region,
        'type' => $ri->type,
        'enabled' => $ri->enabled,
      );
    }
    $tems[] = array(
      'id' => 0,
      'pid' => 0,
      'title' => '',
      'weight' => 0,
      'depth' => 0,
      'text' => '',
      'language' => 'und',
      'region' => $default_region,
      'type' => 0,
      'enabled' => true,
    );
    $items = json_decode(json_encode($tems), FALSE);
    //dsm($items);
    $form['#tree'] = TRUE;
    $form['act_types'] = array(
      '#type' => 'value',
      '#value' => $actionTypes
    );
    $form['region_types'] = array(
      '#type' => 'value',
      '#value' => $region_opts
    );


    foreach ($items as $item) {
      $attr = array();
      $disable = false;
      if ($item->id > 0) {
        $attr = array(
          'readonly' => 'readonly',
          'class' => array('readonly-input')
        );
        $disable = true;
        $btntext = "Edit";
      } else {
        $btntext = "Add";
        $item->type = 'text';
      }
      // Textfield to hold content id.
      $form['items'][$item->id]['id'] = array(
        '#type' => 'hidden',
        '#size' => 3,
        '#default_value' => $item->id,
        '#disabled' => TRUE,
        '#attributes' => array('class' => 'draggable'),
      );
      $form['items'][$item->id]['type'] = array(
        '#type' => 'hidden',
        '#default_value' => $item->type,
      );
      //    $form['items'][$item->id]['region'] = array(
      //      '#type' => 'hidden',
      //      '#default_value' => $item->region,
      //    );
      $form['items'][$item->id]['bundle'] = array(
        '#type' => 'hidden',
        '#default_value' => $bundle,
      );
      $form['items'][$item->id]['title'] = array(
        '#type' => 'textfield',
        '#size' => 40,
        '#attributes' => $attr,

        '#default_value' => $item->title,
        '#group' => 'items',
      );
      $form['items'][$item->id]['region'] = array(
        '#type' => 'select',
        '#options' => $form['region_types']['#value'],
        '#attributes' => $attr,
        '#disabled' => $disable,
        '#default_value' => $item->region,
      );
      // Caption for the itemshow.
      $form['items'][$item->id]['type'] = array(
        '#type' => 'select',
        '#options' => $form['act_types']['#value'],
        '#attributes' => $attr,
        '#disabled' => $disable,
        '#default_value' => $item->type,
      );
      // This field is invisible, but contains sort info (weights).
      $form['items'][$item->id]['weight'] = array(
        '#type' => 'weight',
        '#title' => t('Weight'),
        '#title_display' => 'invisible',
        '#default_value' => $item->weight,
        '#attributes' => array('class' => array('mytable-order-weight')),
      );
      $form['items'][$item->id]['pid'] = array(
        '#type' => 'textfield',
        '#default_value' => $item->pid,
        '#size' => 3,
      );
      $form['items'][$item->id]['depth'] = array(
        '#type' => 'hidden',
        '#value' => $item->depth,
      );
      // Operation links
      $form['items'][$item->id]['translate'] = array(
        '#id' => 'trans' . $item->id,
        '#name' => 'trans' . $item->id,
        '#type' => 'submit',
        '#value' => t('Translate'),
        '#attributes' => array(
          'class' => array('trans-row trans-row-' . $item->id)),
        '#submit' => array('triage_actions_translate_submit')
      );
      $form['items'][$item->id]['enabled'] = array(
        '#type' => 'checkbox',
        '#default_value' => $item->enabled,
      );
      $form['items'][$item->id]['edit'] = array(
        '#id' => 'btn' . $item->id,
        '#name' => 'btn' . $item->id,
        '#type' => 'submit',
        '#value' => t($btntext),
        '#attributes' => array(
          'class' => array('edit-row edit-row-' . $item->id)),
        '#submit' => array('triage_actions_edit_submit')
      );
      // Operation links (to remove rows).
      $form['items'][$item->id]['remove'] = array(
        '#id' => 'del' . $item->id,
        '#name' => 'del' . $item->id,
        '#type' => 'submit',
        '#value' => t('Delete'),
        '#attributes' => array(
          'class' => array('del-row del-row-' . $item->id)),
        '#submit' => array('triage_actions_delete_submit')
      );
    }
    $form['submit'] = array('#type' => 'submit', '#value' => t('Save'));
    $form['preview'] = array(
      '#type' => 'submit',
      '#value' => t('Preview'),
    );
    return $form;
  }
  public function triage_actions_edit_submit($form, &$form_state) {
    $tempstore = \Drupal::service('tempstore.private')->get('triage');
    $con = Database::getConnection();
    global $language;
    global $base_url;
    $bundle = $tempstore->get('my_bundle');
    $tid = $tempstore->get('edit_tid');
    $id = $form_state['triggering_element']['#id'];
    $id = str_replace("btn", '', $id);
    $ray = $form_state['values']['items'][$id];
    if ($id == 0) {
      $id = $con->insert('triage_actions')
        ->fields(array(
          'title' => $ray['title'],
          'type' => $ray['type'],
          'weight' => $ray['weight'],
          'language' => $language->language,
          'entity_id' => $tid,
          'bundle' => $bundle,
          'region' => $ray['region'],
        ))
        ->execute();
    }
    $url = 'taxonomy/actions/' . $id . '/' . $ray['type'] . '/edit';
    unset($form_state['storage']);
    $form_state['redirect'] = $url;
  }

  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $bds = "";
    $values = $form_state->getValues();
    $admin_voc = $values['triage_actions_admin_voc'];
    $config = \Drupal::service('config.factory')->getEditable('triage.admin_voc');
    $tempstore = \Drupal::service('tempstore.private')->get('triage');
    $tempstore->set('triage_actions_admin_voc', $admin_voc);
    $config
      ->set('triage', 'triage')
      ->set('admin_voc', $admin_voc)
      ->save();
    $form_state->setRedirect('triage.triage_actions_admin');
  }
}
