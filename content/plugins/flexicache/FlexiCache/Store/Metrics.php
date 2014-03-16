<?php

class FlexiCache_Store_Metrics {

	private $_iNumResponses = 0;
	private $_iNumExpiredResponses;

	private $_iSize = 0;
	private $_sSource = 'Unknown';

	public function setNumResponses ($iNumResponses)
	{
		$this->_iNumResponses = (float) $iNumResponses;
		return $this;
	}

	public function getNumResponses ()
	{
		return $this->_iNumResponses;
	}

	public function setNumExpiredResponses ($iNumExpiredResponses)
	{
		$this->_iNumExpiredResponses = (float) $iNumExpiredResponses;
		return $this;
	}

	public function getNumExpiredResponses ()
	{
		return $this->_iNumExpiredResponses;
	}

	public function setSize ($iSize)
	{
		$this->_iSize = (float) $iSize;
		return $this;
	}

	public function getSize ()
	{
		return $this->_iSize;
	}

	public function setSource ($sSource)
	{
		$this->_sSource = trim($sSource);
		return $this;
	}

	public function getSource ()
	{
		return $this->_sSource;
	}

	public function getActivityArray ()
	{

		$aActivity = array ();

		$aActivity['Total cached responses'] = number_format($this->getNumResponses());

		$aActivity['Total cache size'] = self::getSizeString($this->getSize());

		if ((0 != $this->getSize()) && (0 != $this->getNumResponses())) {
			$aActivity['Average cached response size'] = self::getSizeString(($this->getSize()/$this->getNumResponses()));
		}

		if ((null !== $this->getNumExpiredResponses()) && (0 != $this->getNumResponses())) {
			$aActivity['Expired responses'] = number_format(100*$this->getNumExpiredResponses()/$this->getNumResponses(),1) . '%';
		}

		$aActivity['Stats source'] = $this->getSource();

		return $aActivity;

	}

	/**
	* Return a string describing a size
	*/
	public static function getSizeString ($iStoreSize)
	{

		if ($iStoreSize >= pow(1024,3)) {

			return sprintf("%0.1f GB", round($iStoreSize/pow(1024,3),1));

		} else if ($iStoreSize >= pow(1024,2)) {

			return sprintf("%0.1f MB", round($iStoreSize/pow(1024,2),1));

		} else if ($iStoreSize >= 1024) {

			return sprintf("%0.1f KB", round($iStoreSize/1024,1));

		} else {

			return sprintf("%d bytes", $iStoreSize);

		}

	}

}
