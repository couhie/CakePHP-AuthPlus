<?php
/**
 * PrefixAuthenticate.php
 * @author kohei hieda
 *
 */
App::uses('BaseAuthenticate', 'Controller/Component/Auth');

class PrefixAuthenticate extends BaseAuthenticate {

	var $prefix = '';
	var $original = true;

	public function __construct(ComponentCollection $collection, $settings) {
		parent::__construct($collection, $settings);
		$this->_Collection = $collection;
		$controller = $collection->getController();
		$this->controller($controller);
		$this->settings = Set::merge($this->settings, $settings);

		$this->defineViewPath();
		$this->setDefaultPrefix();
		$this->setDefaultSessionKey();
		$this->propertyOverride();
	}

	public function controller($controller = null) {
		if ($controller) {
			if (!$controller instanceof Controller) {
				throw new CakeException(__d('cake_dev', '$controller needs to be an instance of Controller'));
			}
			$this->_Controller = $controller;
			return true;
		}
		return $this->_Controller;
	}

	private function defineViewPath($prefix = 'Default') {
		$viewPaths = App::path('View');
		if (!empty($viewPaths[0])) {
			$viewPathArray = explode(DS, $viewPaths[0]);
			while (count($viewPathArray) > 0) {
				$viewDir = array_pop($viewPathArray);
				if (!empty($viewDir)) {
					break;
				}
			}
			if (!empty($viewDir)) {
				if ($viewDir != 'Default') {
					$viewPathArray[] = $viewDir;
				}
				$viewPathArray[] = $prefix;
				$viewPathArray[] = '';
				$viewPath = implode(DS, $viewPathArray);
				App::build(array('View'=>array($viewPath)));
			}
		}
	}

	private function setDefaultPrefix() {
		if (!empty($this->_Controller->request->params['prefix'])) {
			$this->prefix = $this->_Controller->request->params['prefix'];
			return;
		}
		foreach (array_keys($this->_Controller->WrapAuth->authenticate['AuthPlus.Prefix']) as $prefix) {
			if (!empty($this->settings[$prefix]['default'])) {
				$this->prefix = $prefix;
				$this->original = false;
				break;
			}
		}
	}

	private function setDefaultSessionKey() {
		foreach (array_keys($this->_Controller->WrapAuth->authenticate['AuthPlus.Prefix']) as $prefix) {
			if (empty($this->settings[$prefix]['sessionKey'])) {
				$this->settings[$prefix]['sessionKey'] = 'Auth.'.Inflector::camelize($prefix).'.'.$this->settings[$prefix]['userModel'];
				$this->_Controller->WrapAuth->authenticate['AuthPlus.Prefix'][$prefix]['sessionKey'] = $this->settings[$prefix]['sessionKey'];
			}
		}
	}

	private function propertyOverride() {
		if (empty($this->prefix) ||
			empty($this->settings[$this->prefix])) {
			return;
		}

		$this->defineViewPath(Inflector::camelize($this->prefix));

		foreach ($this->settings[$this->prefix] as $key=>$value) {
			$this->_Controller->WrapAuth->{$key} = $value;
		}

		$this->_Controller->WrapAuth->setSessionKey($this->settings[$this->prefix]['sessionKey']);

		if (empty($this->settings[$this->prefix]['authError'])) {
			$this->_Controller->WrapAuth->authError = __('You are not authorized to access that location.');
		}

		if (empty($this->settings[$this->prefix]['loginError'])) {
			$this->_Controller->WrapAuth->loginError = __('Login failed.');
		}

		if ($this->original) {
			App::uses('AuthPlusAppView', 'AuthPlus.View');
			$View = new AuthPlusAppView($this->_Controller);

			if (empty($this->settings[$this->prefix]['defaultLayout'])) {
				if ($View->isExistsLayout($this->prefix.'_default')) {
					$this->_Controller->WrapAuth->defaultLayout = $this->prefix.'_default';
				} else {
					$this->_Controller->WrapAuth->defaultLayout = 'default';
				}
			}

			if (empty($this->settings[$this->prefix]['loginLayout'])) {
				if ($View->isExistsLayout($this->prefix.'_login')) {
					$this->_Controller->WrapAuth->loginLayout = $this->prefix.'_login';
				} else {
					$this->_Controller->WrapAuth->loginLayout = 'login';
				}
			}

			if (!method_exists($this->_Controller, $this->_Controller->request['action'])) {
				$this->_Controller->request['action'] = str_replace($this->prefix.'_', '', $this->_Controller->request['action']);
			}

			if (!$View->isExistsView()) {
				$this->_Controller->view = str_replace($this->prefix.'_', '', $this->_Controller->view);
			}
		}
	}

	public function authenticate(CakeRequest $request, CakeResponse $response) {
		$userModel = $this->settings[$this->prefix]['userModel'];
		list($plugin, $model) = pluginSplit($userModel);

		$fields = $this->settings[$this->prefix]['fields'];
		if (empty($request->data[$model])) {
			return false;
		}
		$conditions = array();
		foreach ($fields as $field) {
			if (empty($request->data[$model][$field])) {
				$this->_Controller->WrapAuth->flash($this->_Controller->WrapAuth->loginError);
				return false;
			}
			$conditions[$model.'.'.$field] = $request->data[$model][$field];
		}

		if (!empty($this->settings[$this->prefix]['conditions'])) {
			$conditions = array_merge($conditions, $this->settings[$this->prefix]['conditions']);
		}
		$result = ClassRegistry::init($userModel)->find('first', array(
			'conditions' => $conditions,
			'recursive' => -1
		));
		if (empty($result) || empty($result[$model])) {
			$this->_Controller->WrapAuth->flash($this->_Controller->WrapAuth->loginError);
			return false;
		}
		return $result[$model];
	}

	public function shiftAuthentication() {
		$loginData = array();
		foreach ($this->_Controller->WrapAuth->authenticate['AuthPlus.Prefix'] as $prefix=>$data) {
			$loginData[$prefix] = $this->_Controller->WrapAuth->getAuthData($data['sessionKey']);
		}
		$this->_Controller->WrapAuth->setLoginData($loginData);
	}

}