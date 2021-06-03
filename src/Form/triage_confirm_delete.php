<?php

namespace Drupal\triage\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
* Defines a confirmation form for deleting mymodule data.
*/
class triage_confirm_delete extends ConfirmFormBase {

/**
* The ID of the item to delete.
*
* @var string
*/
protected $id;

/**
* {@inheritdoc}
*/
public function getFormId() {
return 'triage_confirm_delete';
}

/**
* {@inheritdoc}
*/
public function getQuestion() {
return t('Do you want to delete %id?', array('%id' => $this->id));
}

/**
* {@inheritdoc}
*/
public function getCancelUrl() {
return new Url('triage.triage_actions_admin');
}

/**
* {@inheritdoc}
*/
public function getDescription() {
return t('Only do this if you are sure!');
}

/**
* {@inheritdoc}
*/
public function getConfirmText() {
return t('Delete it!');
}

/**
* {@inheritdoc}
*/
public function getCancelText() {
return t('Nevermind');
}

/**
* {@inheritdoc}
*
* @param int $id
*   (optional) The ID of the item to be deleted.
*/
public function buildForm(array $form, FormStateInterface $form_state, $id = NULL, $msg = NULL) {
$this->id = $id;
return parent::buildForm($form, $form_state);
}

/**
* {@inheritdoc}
*/
public function submitForm(array &$form, FormStateInterface $form_state) {
triage_delete($this->id);
}

}