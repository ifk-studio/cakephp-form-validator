<?php

/**
 * Class ExchangesController
 *
 */
class ExamplesController extends AppController {
	public $name = 'Examples';
	public $uses = array(
		'Example',
	);

	public $components = array(
		'Forms.FormValidator'
	);

	public $forms = array(
		'ExampleForm',
	);

	/**
	 * Example validation html-form data in $this->request->data
	 */
	public function example() {
		if ($this->ExampleForm->validates($this->request->data)) {
			echo 'true validations';
		} else {
			echo 'false validation';
		}
	}

	/**
	 * Example validation array data
	 */
	public function example2() {
		$data = array(
			'Example' => array(
				'first_field' => 0,
				'second_field' => true
			)
		);
		if ($this->ExampleForm->validates($data)) {
			echo 'true validations';
		} else {
			echo 'false validation';
		}
	}

	/**
	 * Example validation array data width additional options
	 */
	public function example3() {
		$data = array(
			'Example' => array(
				'first_field' => 0,
				'second_field' => true
			)
		);
		$this->ExampleForm->addOptions(array('user_id' => $this->Auth->user('id')));
		if ($this->ExampleForm->validates($data)) {
			echo 'true validations';
		} else {
			echo 'false validation';
		}
	}
}

