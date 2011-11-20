<?php
	require_once("../BaseAuthorization.php");
	require_once("../../MatchAndRankService.php");

	class LinkedInAuthorization extends BaseAuthorization
	{
		public function checkRegistration($token, $secret, $expiresIn, $authExpiresIn, $email=null, $registrationType=null, $login=false)
		{
			$config['callbackUrl']          = '';
			$config['appKey']               = 'lJ0asd3akAISJSTxgyElA6aZqlyqxHtMggNVe8A3Z1UqF039b3T05Dj1eFiQz0N3';
			$config['appSecret']            = '2YUwL6iKGXiSx2GQQaQu3vpBA9hHDf1FSqql9Yh01WmswJwCWE0tDfiXcUH05tIL';

			include_once "linkedin_3.1.1.class.php";

			$linkedin = new LinkedIn($config);

			$access = array("oauth_token" => "$token",
							"oauth_token_secret" => "$secret",
							"oauth_expires_in" => "$expiresIn",
							"oauth_authorization_expires_in" => "$authExpiresIn");

			$linkedin->setTokenAccess($access);

			$response = $linkedin->profile("~:(id,first-name,last-name,industry,date-of-birth,".
												"languages:(language),".
												"skills:(skill),".
												"educations:(school-name,field-of-study,start-date,end-date,degree,activities),".
												"positions:(title,summary,start-date,end-date,is-current,company))");
			if ($response['success'] === TRUE)
			{
				$response['linkedin'] = new SimpleXMLElement($response['linkedin']);
				$returnObj = new stdClass();

				foreach($response["linkedin"] as $key => $value)
				{
					$numElements = count($value->children());

					if($numElements == 0)
						$returnObj->$key = (string) $value;

					switch($key)
					{
						case "date-of-birth":
							$returnObj->age = array("year" => (string) $value->year,
													"month" => (string) $value->month,
													"date" => (string) $value->day);
							break;
						case "languages":
							$languages = $value;
							$returnObj->languages = array();

							foreach($languages as $key => $value)
							{
								$returnObj->languages[] = (string) $value->language->name;
							}
							break;
						case "skills":
							$skills = $value;
							$returnObj->skills = array();

							foreach($skills as $key => $value)
							{
								$returnObj->skills[] = (string) $value->skill->name;
							}
							break;
						case "educations":
							$educations = $value;
							$returnObj->educations = array();

							foreach($educations as $key => $value)
							{
								$returnObj->educations[] = array(
									"school-name" => (string) $value->{"school-name"},
									"field-of-study" => (string) $value->{"field-of-study"},
									"start-date" => (string) $value->{"start-date"}->year,
									"end-date" => (string) $value->{"end-date"}->year,
									"degree" => (string) $value->{"degree"},
									"activities" => (string) $value->{"activities"}
									);
							}

							if(count($returnObj->educations) == 0)
								unset($returnObj->educations);

							break;
						case "positions":
							$positions = $value;
							$returnObj->positions = array();

							foreach($positions as $key => $value)
							{
								//title,summary,start-date,end-date,is-current,company
								$returnObj->positions[] = array(
									"title" => (string) $value->{"title"},
									"summary" => (string) $value->{"summary"},
									"start-date" => (string) $value->{"start-date"}->year,
									"end-date" => (string) $value->{"end-date"}->year,
									"is-current" => (string) $value->{"is-current"},
									"company" => (string) $value->{"company"}->name
									);
							}

							if(count($returnObj->positions) == 0)
								unset($returnObj->positions);
							break;
					}
				}

				if($login)
					return $this->checkLogin("linkedin", $returnObj->id);
				else
					return $this->determineRegistrationStatus("linkedin", $returnObj, $email, $registrationType);
			}
			else
			{
				$response['linkedin'] = new SimpleXMLElement($response['linkedin']);
				throw new Exception((string) $response["linkedin"]->message);
			}
		}

		protected function importInformation($type, $information, $email, $registrationType)
		{
			try
			{
				$name = json_encode($information->{"first-name"}." ".$information->{"last-name"});
				$industry = json_encode(trim($information->{"industry"}));

				if($information->age)
				{
					$month = date("F", mktime(0, 0, 0, $information->age["month"], 1, 2011));
					$age = json_encode(array("year" => $information->age["year"],
					                "month" => $month,
					                "day" => $information->age["date"]));
				}

				if($information->languages)
				{
					$languages = json_encode($information->languages);
				}

				if($information->skills)
				{
					$skills = json_encode($information->skills);
				}

				if($information->educations && count($information->educations) > 0)
				{
					$educations = array();
					foreach($information->educations as $education)
					{
						$item = array();
						$item["institution"] = $education["school-name"];
						$item["subjects"] = $education["activities"];

						$endDate = trim($education["end-date"]);
						$item["inProgress"] = (!$endDate || $endDate == "");

						$item["completionDate"] = array("year" => $item["inProgress"] ? null : $endDate);
						$item["qualification"] = $education["degree"]." (".$education["field-of-study"].")";

						$educations[] = $item;
					}

					$educations = json_encode($educations);
				}

				if($information->positions && count($information->positions) > 0)
				{
					$positions = array();
					foreach($information->positions as $position)
					{
						$item = array();
						$item["title"] = $position["title"];
						
						$endDate = trim($position["end-date"]);
						$item["current"] = $position["is-current"] == "true";

						$item["startDate"] = array("year" => $position["start-date"]);
						$item["endDate"] = array("year" => $item["current"] ? null : $endDate);
						$item["employerName"] = $position["company"];

						$positions[] = $item;
					}

					$positions = json_encode(array("noHistory" => false, "careers" => $positions));
				}
				else
					$positions = json_encode(array("noHistory" => true, "careers" => array()));

				$fields = array(MatchAndRankService::INDUSTRY => $industry,
								MatchAndRankService::AGE => $age, MatchAndRankService::LANGUAGES => $languages,
								MatchAndRankService::SKILLS_AND_ABILITIES => $skills,
								MatchAndRankService::QUALIFICATIONS => $educations,
								MatchAndRankService::CAREER => $positions);

				$user = new User();
				$user->firstName = $information->{"first-name"};
				$user->lastName = $information->{"last-name"};
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