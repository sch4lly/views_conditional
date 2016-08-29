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

    switch ($condition) {
      // Equal to.
      case 1:
        if ($r == $equalto) {
          return $then;
        }
        else {
          return $or;
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
