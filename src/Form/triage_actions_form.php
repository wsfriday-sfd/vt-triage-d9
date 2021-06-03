<?php

namespace Drupal\triage\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;


class triage_actions_form extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'triage_actions_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $term = NULL) {
    require_once DRUPAL_ROOT . '/' . drupal_get_path('module', 'triage') . '/includes/triage_actions.inc';
    require_once DRUPAL_ROOT . '/' . drupal_get_path('module', 'triage') . '/includes/triage.orgsearch.inc';
    require_once DRUPAL_ROOT . '/modules/contrib/htmlawed/htmLawed/htmLawed.php';
    $tempstore = \Drupal::service('tempstore.private')->get('triage');
    global $base_url;
    $config = \Drupal::config('triage.admin_voc');
    $vid = "vt_triage";
    if ($config) {
      $vid = $config->get('admin_voc');
    }
    $node = triage_get_page($vid);
    //    dpm($node);
    $nid = $node->id();

    //    drupal_add_js($base_url . "/misc/tabledrag.js", "file");
    $bundle = $tempstore->get('my_bundle');
    if (is_null($bundle)) {
      $bundle = "taxonomy";
    }
    $default_region = 'ta-main-panel';
    $actionTypes = [
      'text' => 'Custom Text',
      'reuse' => 'Reusable Text',
      'node' => 'Node Content',
      'div' => 'Display Wrapper',
    ];
    $region_opts = [
      'none' => 'None',
      'ta-message-panel' => 'Top Message Panel',
      'ta-main-panel' => 'Main Panel',
      'ta-help-panel' => 'Help Panel',
      'ta-bottom-panel' => 'Bottom Panel',
    ];
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
    $items = [];
    $tems = [];
    $form = [];
    $form['#tree'] = TRUE;
    $form['bundle'] = [
      '#type' => 'hidden',
      '#default_value' => $bundle,
    ];
    $form['tid'] = [
      '#type' => 'hidden',
      '#default_value' => $term,
    ];
    $form['triage_actions_admin_voc'] = [
      '#type' => 'hidden',
      '#default_value' => $vid,
    ];
    $form['actions'] = [
      '#type' => 'table',
      '#caption' => $this->t('Actions'),
      '#header' => [
        'Title',
        'Region',
        'Type',
        'Enabled',
        'Weight',
        ['data' => 'Operations', 'colspan' => 8],
      ],
      '#empty' => $this->t('No items.'),
      '#tree' => TRUE,
      '#tableselect' => FALSE,
      '#tabledrag' => [
        [
          'action' => 'match',
          'relationship' => 'parent',
          'group' => 'triage-order-pid',
          'hidden' => TRUE,
          'source' => 'actid',
        ],
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'triage-order-weight',
        ],
      ],
    ];
    $form['actions']['act_types'] = [
      '#type' => 'value',
      '#value' => $actionTypes,
    ];
    $form['actions']['region_types'] = [
      '#type' => 'value',
      '#value' => $region_opts,
    ];

    $realitems = triage_actions_parent_get_data($bundle, $this_tid);
    foreach ($realitems as $ri) {
      $depth = 0;
      if ($ri->pid > 0) {
        $depth = 1;
      }
      $tems[] = [
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
      ];
    }
    $tems[] = [
      'id' => 0,
      'pid' => 0,
      'title' => '',
      'weight' => 0,
      'depth' => 0,
      'text' => '',
      'language' => 'und',
      'region' => $default_region,
      'type' => 0,
      'enabled' => TRUE,
    ];
    $items = json_decode(json_encode($tems), FALSE);
    //dsm($items);


    foreach ($items as $item) {
      $attr = [];
      $disable = FALSE;
      if ($item->id > 0) {
        $attr = [
          'readonly' => 'readonly',
          'class' => ['readonly-input'],
        ];
        $disable = TRUE;
        $btntext = "Edit";
      }
      else {
        $btntext = "Add";
        $item->type = 'text';
      }
      if (isset($item->depth) && $item->depth > 0) {
        $indentation = [
          '#theme' => 'indentation',
          '#size' => $item->depth,
        ];
      }
      $form['actions'][$item->id]['#attributes']['class'][] = 'draggable';
      $form['actions'][$item->id]['#attributes']['class'][] = 'actid';
      $form['actions'][$item->id]['#weight'] = $item->weight;
      //       Textfield to hold content id.
      $form['actions'][$item->id]['showtitle'] = [
        '#markup' => $item->title,
        // '#prefix' => !empty($indentation) ? drupal_render($indentation) : '',
        '#prefix' => !empty($indentation) ? \Drupal::service('renderer')->render($indentation) : '',
      ];
      $form['actions'][$item->id]['region'] = [
        '#type' => 'select',
        '#options' => $form['actions']['region_types']['#value'],
        '#attributes' => $attr,
        '#disabled' => $disable,
        '#default_value' => $item->region,
      ];
      // Caption for the itemshow.
      $form['actions'][$item->id]['wrapper'] = [
        '#type' => 'select',
        '#options' => $form['actions']['act_types']['#value'],
        '#attributes' => $attr,
        '#disabled' => $disable,
        '#default_value' => $item->type,
      ];
      $form['actions'][$item->id]['enabled'] = [
        '#type' => 'checkbox',
        '#default_value' => $item->enabled,
      ];
      // This field is invisible, but contains sort info (weights).
      $form['actions'][$item->id]['weight'] = [
        '#type' => 'weight',
        '#title' => t('Weight'),
        '#title_display' => 'invisible',
        '#default_value' => $item->weight,
        '#attributes' => ['class' => ['triage-order-weight']],
      ];
      // Operation links
      $form['actions'][$item->id]['translate'] = [
        '#id' => 'trans' . $item->id,
        '#name' => 'trans' . $item->id,
        '#type' => 'submit',
        '#value' => t('Translate'),
        '#attributes' => [
          'class' => ['trans-row trans-row-' . $item->id],
        ],
        '#submit' => ['triage_actions_translate_submit'],
      ];
      $form['actions'][$item->id]['edit'] = [
        '#id' => 'btn' . $item->id,
        '#name' => 'btn' . $item->id,
        '#type' => 'submit',
        '#value' => t($btntext),
        '#attributes' => [
          'class' => ['edit-row edit-row-' . $item->id],
        ],
        '#submit' => ['::triage_actions_edit_submit'],
      ];
      // Operation links (to remove rows).
      $form['actions'][$item->id]['remove'] = [
        '#id' => 'del' . $item->id,
        '#name' => 'del' . $item->id,
        '#type' => 'button',
        '#value' => t('Delete'),
        '#attributes' => [
          'class' => ['del-row del-row-' . $item->id],
        ],
      ];
      $form['actions'][$item->id]['id'] = [
        '#type' => 'hidden',
        '#size' => 3,
        '#default_value' => $item->id,
        '#disabled' => TRUE,
      ];
      $form['actions'][$item->id]['title'] = [
        '#type' => 'hidden',
        '#default_value' => $item->title,
      ];
      $form['actions'][$item->id]['type'] = [
        '#type' => 'hidden',
        '#default_value' => $item->type,
      ];
      $form['actions'][$item->id]['bundle'] = [
        '#type' => 'hidden',
        '#default_value' => $bundle,
      ];
      $form['actions'][$item->id]['pid'] = [
        '#type' => 'textfield',
        '#default_value' => $item->pid,
        '#size' => 3,
        '#attributes' => ['class' => ['triage-order-pid']],
      ];
      $form['actions'][$item->id]['depth'] = [
        '#type' => 'hidden',
        '#size' => 3,
        '#value' => $item->depth,
      ];
    }
    $form['submit'] = ['#type' => 'submit', '#value' => t('Save')];
    $form['preview'] = [
      '#type' => 'submit',
      '#value' => t('Preview'),
      '#submit' => ['::triage_actions_preview_submit'],
    ];
    return $form;
  }

  public function triage_actions_edit_submit($form, &$form_state) {
    $tempstore = \Drupal::service('tempstore.private')->get('triage');
    $con = Database::getConnection();
    $language = \Drupal::languageManager()->getCurrentLanguage();
    $lang = $language->getId();
    $vals = $form_state->getValues();
    $bundle = $vals['bundle'];
    $tid = $vals['tid'];
    $tempstore->set('edit_tid', $tid);
    $id = $form_state->getTriggeringElement()['#id'];
    $id = str_replace("btn", '', $id);
    $ray = $form_state->getValues()['actions'][$id];
    $format = "";
    if ($ray['wrapper'] == "text"){
      $format = "full_html";
    }
    if ($id == 0) {
      $id = $con->insert('triage_actions')
        ->fields([
          'title' => $ray['title'],
          'type' => $ray['wrapper'],
          'weight' => $ray['weight'],
          'action_text_format' => "2",
          'language' => $lang,
          'entity_id' => $tid,
          'bundle' => $bundle,
          'region' => $ray['region'],
        ])
        ->execute();
    }
    //    $newpath = '/taxonomy/actions/' . $id . '/' . $ray['type'] . '/edit';
    //    $routename = Url::fromUserInput($newpath)->getRouteName();
    $form_state->setRedirect('triage.actions.edit', [
      'id' => $id,
      'type' => $ray['wrapper'],
    ]);
  }
  public function triage_actions_preview_submit($form, &$form_state) {
    $tempstore = \Drupal::service('tempstore.private')->get('triage');
    $vals = $form_state->getValues();
    $tid = $vals['tid'];
    $form_state->setRedirect('triage.triage_actions_process', [
      'tid' => $tid,
      'preview' => 1,
    ]);
  }
  public function triage_actions_translate_submit($form, &$form_state) {
    $tempstore = \Drupal::service('tempstore.private')->get('triage');
    $language = \Drupal::languageManager()->getCurrentLanguage();
    $lang = $language->getId();
    $vals = $form_state->getValues();
    $bundle = $vals['bundle'];
    $tid = $vals['tid'];
    $tempstore->set('edit_tid', $tid);
    $tempstore->set('my_bundle', $bundle);
    $id = $form_state->getTriggeringElement()['#id'];
    $id = str_replace("trans", '', $id);
    $url = 'triage_actions_translate/' . $id;
    $ray = $form_state->getValues()['actions'][$id];
    $format = "";
    if ($ray['wrapper'] == "text"){
      $format = "full_html";
    }

    //    unset($form_state['storage']);
//    $form_state['redirect'] = $url;
    $form_state->setRedirect('triage.actions.translate', [
      'id' => $id,
      'type' => $ray['wrapper'],
    ]);

  }
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $con = Database::getConnection();
    $bds = "";
    $values = $form_state->getValues();
    foreach ($values['actions'] as $key => $value) {
      $enabled = 1;
      $weight = 0;
      $pid = 0;
      if(isset($value['pid'])){$pid = $value['pid'];}
      if(isset($value['weight'])){$weight = $value['weight'];}
      if(isset($value['enabled'])){$enabled = $value['enabled'];}
      $con->update('triage_actions')
        ->fields([
          'pid' => $pid,
          'weight' => $weight,
          'enabled' => $enabled,
        ])
        ->condition('id', $key)
        ->execute();
    }
    $admin_voc = $values['triage_actions_admin_voc'];
    $config = \Drupal::service('config.factory')
      ->getEditable('triage.admin_voc');
    $tempstore = \Drupal::service('tempstore.private')->get('triage');
    $tempstore->set('triage_actions_admin_voc', $admin_voc);
    $config
      ->set('triage', 'triage')
      ->set('admin_voc', $admin_voc)
      ->save();
    $form_state->setRedirect('triage.triage_actions_admin');
  }

  public function triage_actions_delete_confirm($form, &$form_state) {
    // Always provide entity id in the same form key as in the entity edit form.
    // $delete_group = 1 if we're coming from the reusable text editor, otherwise 0
    $tempstore = \Drupal::service('tempstore.private')->get('triage');
    $con = Database::getConnection();
    $language = \Drupal::languageManager()->getCurrentLanguage();
    $lang = $language->getId();
    $id = $form_state->getTriggeringElement()['#id'];
    $id = str_replace("del", '', $id);
    $ray = $form_state->getValues()['actions'][$id];
    $num = 0;
    $drop_entity_only = FALSE;
    $reusable = $con->query('select id from triage_actions where reusable_text = 1', [":id" => $id])->fetchColumn();
    if ($reusable) {
      $query = $con->select('triage_actions', 'ta');
      $query->fields('ta', ['id']);
      $query->condition('node_ref_nid', $id)
        ->condition('type', 'reuse');
      $result = $query->execute();
      $result->allowRowCount = TRUE;
      $num = $result->rowCount();
      if ($num > 0) {
        $drop_entity_only = TRUE;
      }
    }
    $title = $con->query('select title from triage_actions where id = :id', [":id" => $id])->fetchColumn();
    $msg = t('Are you sure you want to delete %title?', ['%title' => $title]);
    if ($drop_entity_only) {
      $msg = "This text is marked as re-usable and is in use by other entities<br />";
      $msg .= "Deleting it will remove its association with this item only<br />";
      $msg .= "It will continue to be available to other entities as re-usable text<br /><br />";
      $msg .= t('Do you want to delete this instance of %title?', ['%title' => $title]);
//      if ($delete_group) {
//        $msg = "This reusable text is being referenced by " . $num . " other actions<br />";
//        $msg .= "All references will also be deleted<br /><br />";
//        $msg .= t('Do you want to delete %title?', ['%title' => $title]);
//      }
    }
    //$url = 'triage_actions_reuse_editor';
    $form_state->setRedirect('triage_action.delete', ['id' => $id, 'msg'=>$msg]);
    //return confirm_form($form, $msg, $url, t('This action cannot be undone.'), t('Delete'), t('Cancel'));
  }

  public function triage_actions_delete_confirm_submit($form, &$form_state) {
    $database = \Drupal::database();
    $delete_group = $_SESSION['triage']['delete_group'];
    //dsm($delete_group);
    $_SESSION['triage']['delete_group'] = 0;
    $bundle = $_SESSION['triage']['my_bundle'];
    $id = $form_state['values']['id'];
    // $title = db_query('select title from triage_actions where id = :id', [":id" => $id])->fetchColumn();
    $title = $database->query('select title from triage_actions where id = :id', [":id" => $id])->fetchColumn();
    $drop_entity_only = FALSE;
    // $reusable = db_query('select id from triage_actions where reusable_text = 1 and id = :id', [":id" => $id])->fetchColumn();
    $reusable = $database->query('select id from triage_actions where reusable_text = 1 and id = :id', [":id" => $id])->fetchColumn();
    if ($reusable) {
      if ($delete_group) {
      }
      else {
        // $query = db_select('triage_actions', 'ta');
        $query = $database->select('triage_actions', 'ta');
        $query->fields('ta', ['id']);
        $query->condition('node_ref_nid', $id)
          ->condition('type', 'reuse');
        $result = $query->execute();
        $num = $result->rowCount();
        if ($num > 0) {
          $drop_entity_only = TRUE;
        }
      }
    }
    if ($form_state['values']['confirm']) {
      if ($drop_entity_only && !$delete_group) {
        // db_query('update triage_actions set entity_id = 0 where id = :id', [':id' => $id]);
        $database->query('update triage_actions set entity_id = 0 where id = :id', [':id' => $id]);
        MessengerInterface::addMessage('Triage Action %title has been removed from this entity but is still available as reusable text.', ['%title' => $title]);
      }
      else {
        // db_query('delete from triage_actions where id = :id', [':id' => $id]);
        $database->query('delete from triage_actions where id = :id', [':id' => $id]);
        if ($delete_group) {
          // db_query("delete from triage_actions where type = 'reuse' and node_ref_nid = :id", [':id' => $id]);
          $database->query("delete from triage_actions where type = 'reuse' and node_ref_nid = :id", [':id' => $id]);
          MessengerInterface::addMessage('Triage Action %title and its references have been deleted.', ['%title' => $title]);
        }
        else {
          MessengerInterface::addMessage('Triage Action %title has been deleted.', ['%title' => $title]);
        }
      }
    }
    if ($delete_group) {
      $url = 'triage_actions_reuse_editor';
    }
    else {
      if ($bundle == 'taxonomy') {
        $url = '/taxonomy/term/' . $_SESSION['triage']['edit_tid'] . '/actions';
      }
      else {
        $url = '/node/' . $_SESSION['triage']['edit_tid'] . '/actions';
      }
    }
    $form_state['redirect'] = $url;
  }
}