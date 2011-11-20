<?php
	require_once("../BaseAuthorization.php");
	require_once("../../MatchAndRankService.php");

	class TwitterAuthorization extends BaseAuthorization
	{
		public function checkRegistration($token, $secret, $email=null, $registrationType=null, $login=false)
		{
			try
			{
				$consumer_key = "dkZPDf2egte9li23BO2Pfw";
				$consumer_secret = "SwdjM0eLFaUKLcIbCgMW2Tm9C1A7dUM7h46V2PvDTG8";

				require_once 'twitter-async/EpiCurl.php';
				require_once 'twitter-async/EpiOAuth.php';
				require_once 'twitter-async/EpiTwitter.php';

				$twitterObj = new EpiTwitter($consumer_key, $consumer_secret);
				$twitterObj->setToken($token, $secret);

				$twitterInfo = $twitterObj->get_accountVerify_credentials();

				$twitterInfo->response;

				$info = new stdClass();
				$info->id = $twitterInfo->id_str;
				$info->name = $twitterInfo->name;

				$names = explode(" ", $info->name);
				$lastName = array_pop($names);
				$firstName = implode(" ", array_slice($names, 0, count($names)));

				$info->firstName = $firstName;
				$info->lastName = $lastName;
			}
			catch(Exception $e)
			{
				throw new Exception("Not logged in");
			}

			if($login)
				return $this->checkLogin("twitter", $info->id);
			else
				return $this->determineRegistrationStatus("twitter", $info, $email, $registrationType);
		}

		protected function importInformation($type, $information, $email, $registrationType)
		{
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