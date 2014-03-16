<?php

class FlexiCache_Config_Form {

	private static function _renderSelectStart ($sId)
	{
		return sprintf('<select id="%s" name="%1$s">', $sId);
	}

	public static function renderArraySelect ($sId, $sDefault, $aOption)
	{

		$sHtml = self::_renderSelectStart($sId);

		foreach ($aOption as $sKey=>$sValue) {

			$sHtml .= sprintf('<option value="%s"%s>%s</option>',
				$sKey,
				($sKey==$sDefault)?' selected="selected"':'',
				$sValue
			);

		}

		$sHtml .= "</select>";

		return $sHtml;

	}

	public static function renderCheckbox ($sId, $bDefault, $bDisabled=false)
	{

		return sprintf('<input type="checkbox" id="%s" name="%1$s"%s%s />',
			$sId,
			(true == (bool)$bDefault)?' checked="checked"':'',
			(true == (bool)$bDisabled)?' disabled="disabled"':''
		);

	}

	public static function renderBooleanSelect ($sId, $sDefault)
	{
		return self::renderArraySelect($sId, $sDefault, array(0=>'No',1=>'Yes'));
	}

	public static function renderCompressionLevelSelect ($sId, $sDefault)
	{

		$aOption = array();

		for ($i=1;$i<=9;$i++) {
			$aOption[$i] = $i;
		}

		return self::renderArraySelect($sId, $sDefault, $aOption);

	}

	public static function renderStorageEngineSelect ($sId, $sDefault)
	{

		$aOption = array();

		foreach (FlexiCache_Store::getEngines() as $sStoreId=>$sStoreName) {

			$oStore = FlexiCache_Store::factory($sStoreId);
			$oStore->check();

			if (true == $oStore->getIsEnabled()) {
				$aOption[$sStoreId] = $sStoreName;
			}

		}

		return self::renderArraySelect($sId, $sDefault, $aOption);

	}

	public static function renderInputText ($sId, $sDefault, $iSize=5, $bDisabled=false)
	{

		return sprintf('<input size="%d" type="text" id="%s" name="%2$s" value="%s"%s />',
			$iSize,
			$sId,
			htmlspecialchars($sDefault),
			(true == (bool)$bDisabled)?' disabled="disabled"':''
		);

	}

	public static function getFieldId ($sPrefix, $sId)
	{
		return join('_', array($sPrefix,$sId));
	}

	private static function _getConditionFieldId ($sPrefix, $oCondition)
	{
		return self::getFieldId($sPrefix,$oCondition->getId());
	}

	/**
	* Render a config condition as a table row
	*/
	public static function renderCondition ($oCondition)
	{

		$aInput = array ();

		array_push($aInput, self::renderArraySelect(self::_getConditionFieldId('Source',$oCondition),$oCondition->getSource(),FlexiCache_Config_Condition::getAvailableSourceOptions()));
		array_push($aInput, self::renderInputText(self::_getConditionFieldId('Key',$oCondition),$oCondition->getKey(),20));
		array_push($aInput, self::renderArraySelect(self::_getConditionFieldId('MatchType',$oCondition),$oCondition->getMatchType(),FlexiCache_Config_Condition::getAvailableMatchTypeOptions()));
		array_push($aInput, self::renderInputText(self::_getConditionFieldId('Value',$oCondition),$oCondition->getValue(),20));

		/**
		* If it's an expiry condition, add the expiry time
		*/
		if (true == ($oCondition instanceof FlexiCache_Config_Condition_Expire)) {
			array_push($aInput, self::renderInputText(self::_getConditionFieldId('ExpiresSeconds',$oCondition),$oCondition->getExpiresSeconds(),9));
		}

		array_push($aInput, self::renderInputText(self::_getConditionFieldId('Description',$oCondition),$oCondition->getDescription(),40));
		array_push($aInput, self::renderCheckbox(self::_getConditionFieldId('IsEnabled',$oCondition),$oCondition->getIsEnabled()));

		if (null != $oCondition->getId()) {

			/**
			* If it's an existing condition, add a delete option
			*/
			array_push($aInput, self::renderCheckbox(self::_getConditionFieldId('Delete',$oCondition),null));

		} else {

			/**
			* If it's a new condition, add a disabled delete condition (so the forms line up)
			*/
			array_push($aInput, self::renderCheckbox(self::_getConditionFieldId('Delete',$oCondition),null,true));

		}

		$sHtml = '<tr>';

		foreach ($aInput as $sInput) {
			$sHtml .= sprintf("<td>%s</td>", $sInput);
		}

		$sHtml .= '</tr>';

		echo $sHtml;

	}

	public static function renderConditions ($aoCondition)
	{

		foreach ($aoCondition as $oCondition) {
			self::renderCondition($oCondition);
		}

	}

}
