<?php
/**
 * AuthPlusAppView.php
 * @author kohei hieda
 *
 */
class AuthPlusAppView extends View {

	/**
	 * isExistsView
	 * @param $name
	 * @return boolean
	 */
	function isExistsView($name = null) {
		try {
			$this->_getViewFileName($name);
		} catch (MissingViewException $e) {
			return false;
		}
		return true;
	}

	/**
	 * isExistsLayout
	 * @param $name
	 * @return boolean
	 */
	function isExistsLayout($name = null) {
		try {
			$this->_getLayoutFileName($name);
		} catch (MissingLayoutException $e) {
			return false;
		}
		return true;
	}

}