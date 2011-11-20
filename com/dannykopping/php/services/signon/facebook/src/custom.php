<?php

	require_once("facebook.php");

	class CustomFacebook extends Facebook
	{
		public function clearSession()
		{
			$this->clearAllPersistentData();
		}

		public function getAccessTokenFromCode($code)
		{
			return parent::getAccessTokenFromCode($code);
		}
	}
?>