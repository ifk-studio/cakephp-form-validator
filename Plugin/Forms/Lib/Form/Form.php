<?php
App::uses('ClassRegistry', 'Utility');
App::uses('Validation', 'Utility');
App::uses('FormValidator', 'Forms.Lib/Form');
App::uses('CakeEvent', 'Event');
App::uses('CakeEventListener', 'Event');
App::uses('CakeEventManager', 'Event');

class Form extends Object implements CakeEventListener {

	/**
	 * Instance of the CakeEventManager this model is using
	 * to dispatch inner events.
	 *
	 * @var CakeEventManager
	 */
	protected $_eventManager = null;

	/**
	 * Instance of the ModelValidator
	 *
	 * @var ModelValidator
	 */
	protected $_validator = null;

	/**
	 * List of validation rules. It must be an array with the field name as key and using
	 * as value one of the following possibilities
	 */
	public $validate = array();

	/**
	 * List of validation errors.
	 *
	 * @var array
	 */
	public $validationErrors = array();

	/**
	 * Whitelist of fields allowed .
	 *
	 * @var array
	 */
	public $whitelist = array();

	/**
	 * if not empty use it for  render this form
	 *
	 * @var string
	 */
	public $template = '';

	/**
	 * @var array
	 */
	public $data = array();

	/**
	 * List of forms fields with model and type
	 *
	 * @var array
	 */
	public $fields = array();

	/**
	 * Default model for forms fields
	 *
	 * @var string
	 */
	public $model = '';


	public $alias;

	/**
	 * Additional data for audits, such as userId
	 * Had to be introduced because otherwise does not pass inspection
	 *
	 * @var array
	 */
	public $options = array();

	public function __construct(){
		parent::__construct();
		$this->alias = __CLASS__;
	}

	/**
	 * Returns true if all fields pass validation. Will validate hasAndBelongsToMany associations
	 * that use the 'with' key as well. Since _saveMulti is incapable of exiting a save operation.
	 *
	 * @param array $data    Data from form, $controller->request->data
	 * @param array $options An optional array of custom options to be made available in the beforeValidate callback
	 *
	 * @return boolean True if there are no errors
	 */
	public function validates($data, $options = array()) {
		$this->data = $data;
		return $this->validator()->validates($options);
	}

	/**
	 * Returns an array of fields that have failed validation. On the current model.
	 *
	 * @param string $options An optional array of custom options to be made available in the beforeValidate callback
	 *
	 * @return array Array of invalid fields
	 * @see Model::validates()
	 */
	public function invalidFields($options = array()) {
		return $this->validator()->errors($options);
	}

	/**
	 * Marks a field as invalid, optionally setting the name of validation
	 * rule (in case of multiple validation for field) that was broken.
	 *
	 * @param string $field The name of the field to invalidate
	 * @param mixed  $value Name of validation rule that was not failed, or validation message to
	 *                      be returned. If no validation key is provided, defaults to true.
	 *
	 * @return void
	 */
	public function invalidate($field, $value = true) {
		$this->validator()->invalidate($field, $value);
	}

	/**
	 * Returns a list of all events that will fire in the model during it's lifecycle.
	 * You can override this function to add you own listener callbacks
	 *
	 * @return array
	 */
	public function implementedEvents() {
		return array(
			'Form.beforeValidate' => array('callable' => 'beforeValidate', 'passParams' => true),
			'Form.afterValidate' => array('callable' => 'afterValidate'),
		);
	}

	/**
	 * Returns the CakeEventManager manager instance that is handling any callbacks.
	 * You can use this instance to register any new listeners or callbacks to the
	 * model events, or create your own events and trigger them at will.
	 *
	 * @return CakeEventManager
	 */
	public function getEventManager() {
		if (empty($this->_eventManager)) {
			$this->_eventManager = new CakeEventManager();
			$this->_eventManager->attach($this);
		}
		return $this->_eventManager;
	}


	/**
	 * Returns an instance of a model validator for this class
	 *
	 * @param FormValidator Form validator instance.
	 *                      If null a new FormValidator instance will be made using current model object
	 *
	 * @return FormValidator
	 */
	public function validator(FormValidator $instance = null) {
		if ($instance) {
			$this->_validator = $instance;
		} elseif (!$this->_validator) {
			$this->_validator = new FormValidator($this);
		}
		return $this->_validator;
	}

	/**
	 * Returns true if a record with particular field exists.
	 *
	 *
	 * @param string $field ID of field to check for existence
	 *
	 * @return boolean True if such a record exists
	 */
	public function exists($field = null) {
		if ($field === null) {
			return false;
		}
		return in_array($field, array_keys($this->fields));
	}

	/**
	 * Return these form data, depending on the model of the field
	 *
	 * @param string $fieldName
	 *
	 * @return array
	 */
	public function getData($fieldName) {
		if (!empty($this->validate[$fieldName]['model'])) {
			$data = isset($this->data[$this->fields[$fieldName]['model']]) ? $this->data[$this->fields[$fieldName]['model']] : array();
		} else {
			$data = isset($this->data[$this->model]) ? $this->data[$this->model] : array();
		}
		return $data;
	}

	/**
	 * before validate callback
	 *
	 * @param array $options
	 *
	 * @return bool
	 */
	public function beforeValidate($options = array()) {
		return true;
	}

	/**
	 * after validate callback
	 *
	 * @return bool
	 */
	public function afterValidate() {
		return true;
	}

	/**
	 * @param array $options
	 *
	 * @return array
	 */
	public function addOptions($options) {
		if(is_array($options)) {
			$this->options = array_merge($this->options, $options);
		}
		return $this->options;
	}
}
