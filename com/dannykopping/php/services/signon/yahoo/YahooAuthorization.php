<?php
	require_once("../BaseAuthorization.php");
	require_once("../../MatchAndRankService.php");
	require_once("../lightopenid/openid.php");

	class YahooAuthorization extends BaseAuthorization
	{
		public function checkRegistration($information, $email=null, $registrationType=null, $login=false)
		{
			if($login)
				return $this->checkLogin("yahoo", $information["id"]);
			else
				return $this->determineRegistrationStatus("yahoo", $information, $email, $registrationType);
		}

		protected function importInformation($type, $information, $email, $registrationType)
		{
			$information = (object) $information;

			$names = explode(" ", $information->name);
			$lastName = array_pop($names);
			$firstName = implode(" ", array_slice($names, 0, count($names)));

			$user = new User();
			$user->firstName = $firstName;
			$user->lastName = $lastName;
			$user->email = $email;
			$user->type = $registrationType;
			$user->active = true;
			$user->verified = true;

			$externalAccount = new ExternalAccount();
			$externalAccount->type = $type;
			$externalAccount->accessId = $information->id;
			$externalAccount->User = $user;

            return $this->registerAccount($externalAccount);
		}
	}
?>