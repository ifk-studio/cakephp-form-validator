<?php
App::uses('Form', 'Forms.Lib/Form');

class ExampleForm extends Form {
	public $model = 'Example';

	public $fields = array(
		'first_field' => array(
			'type' => 'text',
		),
		'second_field' => array(
			'type' => 'checkbox',
		)
	);

	public $validate = array(
		'second_field' => array(
			'isActive' => array(
				'rule' => 'isActive',
				'message' => "Is fields not active!"
			),
		),
		'first_field' => array(
			'aboveZero' => array(
				'rule' => 'aboveZero',
				'message' => "Number must be greater than 0",
			),
		),
	);

	/**
	 * @return bool
	 */
	public function isActive(){
		if(isset($this->options['user_id'])) {
			return false;
		}
		return (bool)$this->data[$this->model]['second_field'];
	}

	/**
	 * @return bool
	 */
	public function aboveZero() {
		return (is_numeric($this->data[$this->model]['first_field']) && ($this->data[$this->model]['first_field'] > 0));
	}
}