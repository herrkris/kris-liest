<?php

require_once 'Condition.php';

class FlexiCache_Config_ConditionSet {

	/**
	* Array of conditions in the set
	*/
	private $_aCondition = array ();

	/**
	* Return the id for a new condition to be added to the set
	* by adding 1 to the current maximum id
	*/
	private function _getNextConditionId ()
	{

		$iNextId = 1;

		foreach ($this->getConditions() as $oCondition) {

			if ($oCondition->getId() >= $iNextId) {
				$iNextId = 1 + $oCondition->getId();
			}

		}

		return $iNextId;

	}

	/**
	* Add a new condition to the set
	*/
	public function addCondition (FlexiCache_Config_Condition $oCondition)
	{
		$iId = $this->_getNextConditionId();
		$oCondition->setId($iId);
		$this->_aCondition[$iId] = $oCondition;
	}

	/**
	* Return an individual condition referenced by $iId
	*/
	public function getCondition ($iId)
	{

		if (false == isset($this->_aCondition[$iId])) {
			return null;
		}

		return $this->_aCondition[$iId];

	}

	/**
	* Return boolean to indicate whether there are conditions in the
	* condition set
	*/
	public function hasConditions ()
	{
		return (true == empty($this->_aCondition))?false:true;
	}

	/**
	* Return the array of condition objects in the set
	*/
	public function getConditions ()
	{
		return $this->_aCondition;
	}

	/**
	* Delete the condition identified by $iId from the condition set
	*/
	public function deleteCondition ($iId)
	{

		if (null != $this->getCondition($iId)) {
			unset($this->_aCondition[$iId]);
		}

	}

	/**
	* If any of the conditions in the set validates, return
	* the id of the condition, otherwise false
	*/
	public function validateAny ()
	{

		foreach ($this->_aCondition as $oCondition) {

			if (true == $oCondition->validate()) {
				return $oCondition->getId();
			}

		}

		return false;

	}

	/**
	* If any Expires condition in the set validates, return the expiry time
	* contained within the condition, otherwise false if no conditions
	* validate
	*/
	public function validateAnyExpires ()
	{

		foreach ($this->_aCondition as $oCondition) {

			if (true == $oCondition->validate()) {
				return $oCondition->getExpiresSeconds();
			}

		}

		return false;

	}

	/**
	* Update a condition set from $aInput
	*/
	public static function update ($aInput)
	{

		/**
		* If the condition set isn't recognized, stop here
		*/

		$oConditionSet = FlexiCache_Config::get('Main', $aInput['_section']);

		if (null == $oConditionSet) {
			FlexiCache_Wp_Admin::addUserMessage('Couldn\'t update ConditionSet: ' . $aInput['_section']);
			return false;
		}

		/**
		* Group input vars into arrays indexed by condition id
		*/

		$aaCondition = array ();

		foreach ($aInput as $sKey=>$sVal) {

			if (preg_match('#^([^_]+)_(\d+)$#', $sKey, $aCapture)) {

				$iCondition = (int) $aCapture[2];
				$sConditionKey = $aCapture[1];

				if (false == isset($aaCondition[$iCondition])) {
					$aaCondition[$iCondition] = array();
				}

				$aaCondition[$iCondition][$sConditionKey] = $sVal;

			}

		}

		/**
		* For each array of input vars indexed by condition id, fetch that
		* condition and update it
		*/

		foreach ($aaCondition as $iCondition=>$aCondition) {

			if ($iCondition > 0) {

				/**
				* Existing condition
				*/

				if (true == isset($aCondition['Delete'])) {

					/**
					* Delete
					*/
					$oConditionSet->deleteCondition($iCondition);

				} else {

					/**
					* Update
					*/
					$oCondition = $oConditionSet->getCondition($iCondition);
					$oCondition->update($aCondition);

				}

			} else {

				/**
				* Add new condition if key is non-empty
				*/
				if (false == empty($aCondition['Key'])) {

					if (true == isset($aCondition['ExpiresSeconds'])) {

						/**
						* Create an expiry condition
						*/
						$oNewCondition = new FlexiCache_Config_Condition_Expire($aCondition['Source'],$aCondition['Key'],$aCondition['Value'],$aCondition['MatchType'],$aCondition['IsEnabled'],$aCondition['ExpiresSeconds'],$aCondition['Description']);

					} else {

						/**
						* Create an standard condition
						*/
						$oNewCondition = new FlexiCache_Config_Condition($aCondition['Source'],$aCondition['Key'],$aCondition['Value'],$aCondition['MatchType'],$aCondition['IsEnabled'],$aCondition['Description']);

					}

					$oConditionSet->addCondition($oNewCondition);

				}

			}

		}

		return true;

	}

}
