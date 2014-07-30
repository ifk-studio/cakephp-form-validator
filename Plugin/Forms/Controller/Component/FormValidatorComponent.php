<?php

class FormValidatorComponent extends Component {

    /**
     * Configuration options
     * @public $config array
     */
    public $config = array(
        'formDir' => 'Form',
    );

    /**
     * Initialization method. You may override configuration options from a controller
     *
     * @param $controller object
     */
    public function initialize(Controller $controller) {
        $this->controller = $controller;
        $this->config = array_merge(
            $this->config, /* default general configuration */
            $this->settings /* overriden configurations */
        );
		$forms = $controller->forms;
		if(!empty($forms)) {
			foreach ($forms as $form) {
				App::uses($form, $this->config['formDir']);
				$this->controller->{$form} = new $form;
			}
		}
    }
}