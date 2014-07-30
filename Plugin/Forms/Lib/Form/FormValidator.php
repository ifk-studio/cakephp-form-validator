<?php

App::uses('CakeValidationSet', 'Model/Validator');
App::uses('Hash', 'Utility');
App::uses('Form', 'Forms.Lib');

class FormValidator extends Object implements ArrayAccess, IteratorAggregate, Countable {

	/**
	 * The validators $validate property, used for checking whether validation
	 * rules definition changed in the model and should be refreshed in this class
	 *
	 * @var array
	 */
	protected $_validate = array();

	/**
	 * Holds the CakeValidationSet objects array
	 *
	 * @var array
	 */
	protected $_fields = array();

	/**
	 * Holds the reference to the model this Validator is attached to
	 *
	 * @var Form
	 */
	protected $_form = array();

	/**
	 * Holds the available custom callback methods from the form
	 *
	 * @var array
	 */
	protected $_formMethods = array();

	/**
	 * Holds the available custom callback methods, usually taken from model methods
	 * and behavior methods
	 *
	 * @var array
	 */
	protected $_methods = array();

	/**
	 * Constructor
	 *
	 * @param Form $Form A reference to the Form the Validator is attached to
	 */
	public function __construct(Form $Form) {
		$this->_form = $Form;
	}


	/**
	 * Returns true if all fields pass validation.
	 *
	 * @param array $options An optional array of custom options to be made available in the beforeValidate callback
	 *
	 * @return boolean True if there are no errors
	 */
	public function validates($options = array()) {
		$errors = $this->errors($options);
		if (is_array($errors)) {
			return count($errors) === 0;
		}
		return $errors;
	}


	/**
	 * Returns an array of fields that have failed validation. This method will
	 * actually run validation rules over data, not just return the messages.
	 *
	 * @param string $options An optional array of custom options to be made available in the beforeValidate callback
	 *
	 * @return array Array of invalid fields
	 * @see FormValidator::validates()
	 */
	public function errors($options = array()) {
		if (!$this->_triggerBeforeValidate($options)) {
			return false;
		}
		$form = $this->getForm();

		if (!$this->_parseRules()) {
			return $form->validationErrors;
		}

		$exists = $form->exists();
		$methods = $this->getMethods();
		$fields = $this->_fields;

		foreach ($fields as $name => $field) {
			$field->setMethods($methods);
			$errors = $field->validate($form->getData($name), $exists);
			foreach ($errors as $error) {
				$this->invalidate($field->field, $error);
			}
		}

		$form->getEventManager()->dispatch(new CakeEvent('Form.afterValidate', $form));
		return $form->validationErrors;
	}

	/**
	 * Marks a field as invalid, optionally setting a message explaining
	 * why the rule failed
	 *
	 * @param string $field   The name of the field to invalidate
	 * @param string $message Validation message explaining why the rule failed, defaults to true.
	 *
	 * @return void
	 */
	public function invalidate($field, $message = true) {
		$this->getForm()->validationErrors[$field][] = $message;
	}

	/**
	 * Gets all possible custom methods from the Form
	 * to be used as validators
	 *
	 * @return array List of callables to be used as validation methods
	 */
	public function getMethods() {
		if (!empty($this->_methods)) {
			return $this->_methods;
		}

		if (empty($this->_formMethods)) {
			foreach (get_class_methods($this->_form) as $method) {
				$this->_formMethods[strtolower($method)] = array($this->_form, $method);
			}
		}

		$methods = $this->_formMethods;

		return $this->_methods = $methods;
	}

	/**
	 * Returns a CakeValidationSet object containing all validation rules for a field, if no
	 * params are passed then it returns an array with all CakeValidationSet objects for each field
	 *
	 * @param string $name [optional] The fieldname to fetch. Defaults to null.
	 *
	 * @return CakeValidationSet|array
	 */
	public function getField($name = null) {
		$this->_parseRules();
		if ($name !== null) {
			if (!empty($this->_fields[$name])) {
				return $this->_fields[$name];
			}
			return null;
		}
		return $this->_fields;
	}

	/**
	 * Sets the CakeValidationSet objects from the `Form::$validate` property
	 * If `Form::$validate` is not set or empty, this method returns false. True otherwise.
	 *
	 * @return boolean true if `Form::$validate` was processed, false otherwise
	 */
	protected function _parseRules() {
		if ($this->_validate === $this->_form->validate) {
			return true;
		}

		if (empty($this->_form->validate)) {
			$this->_validate = array();
			$this->_fields = array();
			return false;
		}

		$this->_validate = $this->_form->validate;
		$this->_fields = array();
		$methods = $this->getMethods();
		foreach ($this->_validate as $fieldName => $ruleSet) {
			$this->_fields[$fieldName] = new CakeValidationSet($fieldName, $ruleSet);
			$this->_fields[$fieldName]->setMethods($methods);
		}
		return true;
	}

	/**
	 * Gets the model related to this validator
	 *
	 * @return Form
	 */
	public function getForm() {
		return $this->_form;
	}

	/**
	 * Processes the Form's whitelist or passed fieldList and returns the list of fields
	 * to be validated
	 *
	 * @param array $fieldList list of fields to be used for validation
	 *
	 * @return array List of validation rules to be applied
	 */
	protected function _validationList($fieldList = array()) {
		$form = $this->getForm();
		$whitelist = $form->whitelist;

		if (!empty($fieldList)) {
			if (!empty($fieldList[$form->alias]) && is_array($fieldList[$form->alias])) {
				$whitelist = $fieldList[$form->alias];
			} else {
				$whitelist = $fieldList;
			}
		}
		unset($fieldList);

		if (empty($whitelist) || Hash::dimensions($whitelist) > 1) {
			return $this->_fields;
		}

		$validateList = array();
		$form->validationErrors = array();
		foreach ((array)$whitelist as $f) {
			if (!empty($this->_fields[$f])) {
				$validateList[$f] = $this->_fields[$f];
			}
		}

		return $validateList;
	}

	/**
	 * Propagates beforeValidate event
	 *
	 * @param array $options
	 *
	 * @return boolean
	 */
	protected function _triggerBeforeValidate($options = array()) {
		$form = $this->getForm();
		$event = new CakeEvent('Form.beforeValidate', $form, array($options));
		list($event->break, $event->breakOn) = array(true, false);
		$form->getEventManager()->dispatch($event);
		if ($event->isStopped()) {
			return false;
		}
		return true;
	}

	/**
	 * Returns whether a rule set is defined for a field or not
	 *
	 * @param string $field name of the field to check
	 *
	 * @return boolean
	 */
	public function offsetExists($field) {
		$this->_parseRules();
		return isset($this->_fields[$field]);
	}

	/**
	 * Returns the rule set for a field
	 *
	 * @param string $field name of the field to check
	 *
	 * @return CakeValidationSet
	 */
	public function offsetGet($field) {
		$this->_parseRules();
		return $this->_fields[$field];
	}

	/**
	 * Sets the rule set for a field
	 *
	 * @param string                  $field name of the field to set
	 * @param array|CakeValidationSet $rules set of rules to apply to field
	 *
	 * @return void
	 */
	public function offsetSet($field, $rules) {
		$this->_parseRules();
		if (!$rules instanceof CakeValidationSet) {
			$rules = new CakeValidationSet($field, $rules);
			$methods = $this->getMethods();
			$rules->setMethods($methods);
		}
		$this->_fields[$field] = $rules;
	}

	/**
	 * Unsets the rule set for a field
	 *
	 * @param string $field name of the field to unset
	 *
	 * @return void
	 */
	public function offsetUnset($field) {
		$this->_parseRules();
		unset($this->_fields[$field]);
	}

	/**
	 * Returns an iterator for each of the fields to be validated
	 *
	 * @return ArrayIterator
	 */
	public function getIterator() {
		$this->_parseRules();
		return new ArrayIterator($this->_fields);
	}

	/**
	 * Returns the number of fields having validation rules
	 *
	 * @return int
	 */
	public function count() {
		$this->_parseRules();
		return count($this->_fields);
	}

	/**
	 * Adds a new rule to a field's rule set. If second argument is an array or instance of
	 * CakeValidationSet then rules list for the field will be replaced with second argument and
	 * third argument will be ignored.
	 *
	 * ## Example:
	 *
	 * {{{
	 *        $validator
	 *            ->add('title', 'required', array('rule' => 'notEmpty', 'required' => true))
	 *            ->add('user_id', 'valid', array('rule' => 'numeric', 'message' => 'Invalid User'))
	 *
	 *        $validator->add('password', array(
	 *            'size' => array('rule' => array('between', 8, 20)),
	 *            'hasSpecialCharacter' => array('rule' => 'validateSpecialchar', 'message' => 'not valid')
	 *        ));
	 * }}}
	 *
	 * @param string                         $field The name of the field from which the rule will be removed
	 * @param string|array|CakeValidationSet $name  name of the rule to be added or list of rules for the field
	 * @param array|CakeValidationRule       $rule  or list of rules to be added to the field's rule set
	 *
	 * @return FormValidator this instance
	 */
	public function add($field, $name, $rule = null) {
		$this->_parseRules();
		if ($name instanceof CakeValidationSet) {
			$this->_fields[$field] = $name;
			return $this;
		}

		if (!isset($this->_fields[$field])) {
			$rule = (is_string($name)) ? array($name => $rule) : $name;
			$this->_fields[$field] = new CakeValidationSet($field, $rule);
		} else {
			if (is_string($name)) {
				$this->_fields[$field]->setRule($name, $rule);
			} else {
				$this->_fields[$field]->setRules($name);
			}
		}

		$methods = $this->getMethods();
		$this->_fields[$field]->setMethods($methods);

		return $this;
	}

	/**
	 * Removes a rule from the set by its name
	 *
	 * ## Example:
	 *
	 * {{{
	 *        $validator
	 *            ->remove('title', 'required')
	 *            ->remove('user_id')
	 * }}}
	 *
	 * @param string $field The name of the field from which the rule will be removed
	 * @param string $rule  the name of the rule to be removed
	 *
	 * @return FormValidator this instance
	 */
	public function remove($field, $rule = null) {
		$this->_parseRules();
		if ($rule === null) {
			unset($this->_fields[$field]);
		} else {
			$this->_fields[$field]->removeRule($rule);
		}
		return $this;
	}
}