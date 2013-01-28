<?php
/**
 * PrefixAuthHelper.php
 * @author kohei hieda
 *
 */
class PrefixAuthHelper extends AppHelper {

	public function __construct(View $View, $settings = array()) {
		parent::__construct($View, $settings);
		$this->_View = $View;
	}

	function isAuthorized($prefix) {
		return !empty($this->_View->viewVars['loginData'][$prefix]);
	}

	function url($prefix, $url = '/') {
		if ($this->isAuthorized($prefix)) {
			return parent::url("/{$prefix}{$url}");
		} else {
			return parent::url($url);
		}
	}

}