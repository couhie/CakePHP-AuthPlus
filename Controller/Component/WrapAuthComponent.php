<?php
/**
 * WrapAuthComponent.php
 * @author kohei hieda
 *
 */
App::uses('AuthComponent', 'Controller/Component');

class WrapAuthComponent extends AuthComponent {

	public function initialize($controller) {
		parent::initialize($controller);
		$this->_controller = $controller;
		$this->constructAuthenticate();
		$this->shiftAuthentication();
	}

	public function startup($controller) {
		if (empty($controller->request->params['prefix'])) {
			$isAuthorized = true;
		} else {
			$isAuthorized = parent::startup($controller);
		}

		$action = $controller->request->params['action'];

		$request = $controller->request;

		$url = '';

		if (isset($request->url)) {
			$url = $request->url;
		}
		$url = Router::normalize($url);
		$loginAction = Router::normalize($this->loginAction);

		$allowedActions = $this->allowedActions;
		$isAllowed = (
			$this->allowedActions == array('*') ||
			in_array($action, $allowedActions)
		);

		if (!empty($this->defaultLayout) && (!$isAuthorized || $isAllowed || $loginAction == $url)) {
			$controller->layout = $this->defaultLayout;
		} else if (!empty($this->loginLayout) && $isAuthorized && !$isAllowed) {
			$controller->layout = $this->loginLayout;
		}

		$this->shiftAuthentication();

		return $isAuthorized;
	}

	public function login($user = null, $redirect = true) {
		if (parent::login($user)) {
			$this->shiftAuthentication();
			if ($redirect) {
				$this->_controller->redirect($this->redirect());
			}
		}
	}

	public function logout() {
		$this->_controller->redirect(parent::logout());
		$this->shiftAuthentication();
	}

	public function setSessionKey($sessionKey) {
		self::$sessionKey = $sessionKey;
	}

	public function getAuthData($sessionKey = null) {
		if (empty($sessionKey)) {
			$sessionKey = self::$sessionKey;
		}
		return CakeSession::read($sessionKey);
	}

	public function shiftAuthentication() {
		foreach ($this->_authenticateObjects as $auth) {
			if (method_exists($auth, __FUNCTION__)) {
				$auth->{__FUNCTION__}();
			}
		}
	}

	public function setLoginData($loginData) {
		$this->_controller->loginData = $loginData;
		$this->_controller->set('loginData', $loginData);
	}

}