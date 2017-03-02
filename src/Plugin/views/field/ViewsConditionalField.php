<?php

namespace Drupal\views_conditional\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Views field handler for conditional field.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("views_conditional")
 */
class ViewsConditionalField extends FieldPluginBase {

  /**
   * @{inheritdoc}
   */
  public function query() {
    // Leave empty to avoid a query on this field.
  }

  /**
   * Define the available options
   * @return array
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['if'] = array('default' => '');
    $options['condition'] = array('default' => '');
    $options['equalto'] = array('default' => '');
    $options['then'] = array('default' => '');
    $options['or'] = array('default' => '');
    $options['strip_tags'] = array('default' => FALSE);

    return $options;
  }

  /**
   * Provide the options form.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['relationship']['#access'] = FALSE;

    // Display all labels weighted less than the current label.
    $fields = ['- ' . $this->t('no field selected') . ' -'];
    $previous = $this->getPreviousFieldLabels();
    foreach ($previous as $id => $label) {
      $options[$id] = $label;
    }
    $fields += $options;







    $form['if'] = array(
      '#type' => 'select',
      '#title' => $this->t('If this field...'),
      '#options' => $fields,
      '#default_value' => $this->options['if'],
    );

    $form['condition'] = array(
      '#type' => 'select',
      '#title' => $this->t('Is...'),
      '#options' => $this->getConditions(),
      '#default_value' => $this->options['condition'],
    );

    $form['equalto'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('This value'),
      '#description' => $this->t('Input a value to compare the field against.  Replacement variables may be used'),
      '#default_value' => $this->options['equalto'],
    );

    $form['then'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Then output this...'),
      '#description' => $this->t('Input what should be output.  Replacement variables may be used.'),
      '#default_value' => $this->options['then'],
    );

    $form['or'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Otherwise, output this...'),
      '#description' => $this->t('Input what should be output if the above conditions do NOT evaluate to true.'),
      '#default_value' => $this->options['or'],
    );

    $previous = $this->getPreviousFieldLabels();
    $optgroup_arguments = (string) t('Arguments');
    $optgroup_fields = (string) t('Fields');
    foreach ($previous as $id => $label) {
      $options[$optgroup_fields]["{{ $id }}"] = substr(strrchr($label, ":"), 2 );
    }
    // Add the field to the list of options.
    $options[$optgroup_fields]["{{ {$this->options['id']} }}"] = substr(strrchr($this->adminLabel(), ":"), 2 );

    foreach ($this->view->display_handler->getHandlers('argument') as $arg => $handler) {
      $options[$optgroup_arguments]["{{ arguments.$arg }}"] = $this->t('@argument title', array('@argument' => $handler->adminLabel()));
      $options[$optgroup_arguments]["{{ raw_arguments.$arg }}"] = $this->t('@argument input', array('@argument' => $handler->adminLabel()));
    }

    $this->documentSelfTokens($options[$optgroup_fields]);

    // Default text.

    $output = [];
    $output[] = [
      '#markup' => '<p>' . $this->t('You must add some additional fields to this display before using this field. These fields may be marked as <em>Exclude from display</em> if you prefer. Note that due to rendering order, you cannot use fields that come after this field; if you need a field not listed here, rearrange your fields.') . '</p>',
    ];
    // We have some options, so make a list.
    if (!empty($options)) {
      $output[] = [
        '#markup' => '<p>' . $this->t("The following replacement tokens are available for this field. Note that due to rendering order, you cannot use fields that come after this field; if you need a field not listed here, rearrange your fields.") . '</p>',
      ];
      foreach (array_keys($options) as $type) {
        if (!empty($options[$type])) {
          $items = array();
          foreach ($options[$type] as $key => $value) {
            $items[] = $key . ' == ' . $value;
          }
          $item_list = array(
            '#theme' => 'item_list',
            '#items' => $items,
          );
          $output[] = $item_list;
        }
      }
    }
    // This construct uses 'hidden' and not markup because process doesn't
    // run. It also has an extra div because the dependency wants to hide
    // the parent in situations like this, so we need a second div to
    // make this work.
    $form['tokens'] = array(
      '#type' => 'details',
      '#title' => $this->t('Replacement patterns'),
      '#value' => $output,
    );


    $form['strip_tags'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Strip html tags from the output'),
      '#default_value' => $this->options['strip_tags'],
    );

    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * @{inheritdoc}
   */
  public function render(ResultRow $values) {


    $if = $this->options['if'];
    $condition = $this->options['condition'];
    $equalto = $this->options['equalto'];
    $then = $this->options['then'];
    $or = ($this->options['or'] ? $this->options['or'] : '');

    // Gather field information.
    $fields = $this->view->display_handler->getHandlers('field');
    $r = isset($fields["$if"]->last_render) ? trim(strip_tags($fields["$if"]->last_render, '<img>')) : NULL;

    $tokens = $this->getRenderTokens([]);

    switch ($condition) {
      // Equal to.
      case 1:
        if ($r == $equalto) {
          return $this->viewsTokenReplace($then, $tokens);
        }
        else {
          return $this->viewsTokenReplace($or, $tokens);
        }
        break;
    }
  }

  /**
   * Returns the available conditions.
   *
   * @return array
   */
  public function getConditions() {
    return [
      1 => 'Equal to',
      2 => 'NOT equal to',
      3 => 'Greater than',
      4 => 'Less than',
      5 => 'Empty',
      6 => 'NOT empty',
      7 => 'Contains',
      8 => 'Does NOT contain',
      9 => 'Length Equal to',
      10 => 'Length NOT equal to',
      11 => 'Length Greater than',
      12 => 'Length Less than',
    ];
  }
}
