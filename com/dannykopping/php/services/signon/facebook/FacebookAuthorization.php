<?php
	require_once('src/custom.php');
	require_once("../BaseAuthorization.php");
	require_once("../../MatchAndRankService.php");

	class FacebookAuthorization extends BaseAuthorization
	{
		public function checkRegistration($accessToken, $email=null, $registrationType=null, $login=false)
		{
			$facebook = new CustomFacebook(array(
												'appId' => '235394626481909',
												'secret' => '028169a90b435c8d975a3e61824f9f8f'
										   ));

			$facebook->setAccessToken($accessToken);
			$user = $facebook->getUser();
			if (!$user)
			{
				$facebook->clearSession();
				throw new Exception("Not logged in");
			}

			$user_profile = new ArrayObject($facebook->api('/me?fields=name,first_name,last_name,gender,languages,birthday,education,work'));

			$information = new stdClass();
			foreach($user_profile as $key => $value)
			{
				$information->$key = $value;
			}

			if($login)
				return $this->checkLogin("facebook", $information->id);
			else
				return $this->determineRegistrationStatus("facebook", $information, $email, $registrationType);
		}

		protected function importInformation($type, $information, $email, $registrationType)
		{
			try
			{
				$id = $information->id;

				$name = json_encode($information->name);
				$gender = json_encode(ucwords($information->gender));

				$birthday = explode("/", $information->birthday);
				$month = date("F", mktime(0, 0, 0, $birthday[0], 1, 2011));

				$age = array("year" => $birthday[2], "month" => $month, "day" => $birthday[1]);
				$age = json_encode($age);

				if($information->languages && count($information->languages) > 0)
				{
					$languages = array();
					foreach($information->languages as $language)
					{
						$languages[] = $language["name"];
					}

					$languages = json_encode($languages);
				}

				if($information->education && count($information->education) > 0)
				{
					$educations = array();
					foreach($information->education as $education)
					{
						$item = array();
						$item["institution"] = $education["school"]["name"];
						$item["completionDate"] = array("year" => $education["year"]["name"]);
						$item["qualification"] = $education["concentration"][0]["name"];

						$educations[] = $item;
					}

					$educations = json_encode($educations);
				}

				if($information->work && count($information->work) > 0)
				{
					$positions = array();
					foreach($information->work as $position)
					{
						$item = array();
						$position = (array) $position;

						$startDate = trim($position["start_date"]);
						$startDate = explode("-", $startDate);

						$month = date("F", mktime(0, 0, 0, $startDate[1], 1, 2011));
						$startDate = array("year" => $startDate[0], "month" => $month);

						$endDate = trim($position["end_date"]);
						if($endDate)
						{
							$endDate = explode("-", $endDate);
							$month = date("F", mktime(0, 0, 0, $endDate[1], 1, 2011));
							$endDate = array("year" => $endDate[0], "month" => $month);
						}

						$item["current"] = !$endDate;

						$item["startDate"] = $startDate;
						$item["endDate"] = $item["current"] ? null : $endDate;
						$item["employerName"] = $position["employer"]["name"];
						$item["location"] = $position["location"]["name"];

						$positions[] = $item;
					}

					$positions = json_encode(array("noHistory" => false, "careers" => $positions));
				}
				else
					$positions = json_encode(array("noHistory" => true, "careers" => array()));

				$fields = array(MatchAndRankService::AGE => $age,
								MatchAndRankService::GENDER => $gender, MatchAndRankService::LANGUAGES => $languages,
								MatchAndRankService::QUALIFICATIONS => $educations,
								MatchAndRankService::CAREER => $positions);

				$user = new User();
				$user->firstName = $information->first_name;
				$user->lastName = $information->last_name;
				$user->email = $email;
				$user->type = $registrationType;
				$user->active = true;
				$user->verified = true;

				if($registrationType == User::TYPE_JOBSEEKER)
				{
					$userInformation = array();
					foreach($fields as $key => $field)
					{
						if(!$field || (is_array($field) && count($field) == 0))
							continue;

						$info = new UserInformation();
                        $info->completed = true;
						$info->type = $key;
						$info->value = $field;
						$info->User = $user;

						$userInformation[] = $info;
					}

					$user->userInformations = $userInformation;
				}

				$externalAccount = new ExternalAccount();
				$externalAccount->type = $type;
				$externalAccount->accessId = $information->id;
				$externalAccount->User = $user;

                return $this->registerAccount($externalAccount);
			}
			catch(Exception $e)
			{
				throw new Exception($e->getMessage());
			}

			return $information;
		}
	}

?>