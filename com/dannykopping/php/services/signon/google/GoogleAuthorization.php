<?php
	require_once("../BaseAuthorization.php");
	require_once("../../MatchAndRankService.php");
	require_once("../lightopenid/openid.php");

	class GoogleAuthorization extends BaseAuthorization
	{
		public function checkRegistration($information, $email=null, $registrationType=null, $login=false)
		{
			if($login)
				return $this->checkLogin("google", $information["id"]);
			else
				return $this->determineRegistrationStatus("google", $information, $email, $registrationType);
		}

		protected function importInformation($type, $information, $email, $registrationType)
		{
			$information = (object) $information;

			$user = new User();
			$user->firstName = $information->firstName;
			$user->lastName = $information->lastName;
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