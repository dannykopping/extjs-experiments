<?php
    import("aerialframework.service.AbstractService");
    require_once("MatchAndRankService.php");
    require_once("GeographicInfoService.php");
    require_once("InputLookupService.php");
    require_once("ProfessionService.php");
    require_once("UserService.php");

    require_once("../utils/CharsetConverter.php");

    class UserInformationService extends AbstractService
    {
        public $modelName = "UserInformation";

        public function saveMultiple($userInformations, $returnComplete = false)
        {
            //$existing = $this->table->findBy("userId", $userId);
            //@$existing->delete();

            $results = array();
            $jobTitleInfo = null;

            foreach ($userInformations as $information)
            {
                // fix all latin characters - make them all UTF8 compatible
                $type = $information["type"];
                $value =& $information["value"];

                $value = CharsetConverter::fixString($value);

                if($type == MatchAndRankService::JOB_TITLE)
                {
                    $information = $this->parseJobTitles($information);
                    $jobTitleInfo = $information;
                }

                $results[] = $this->save($information, true);
            }

            $user = UserService::getLoggedInUser();
            $userId = $user->id;

            if (!$user)
                return $this->getFriendlyInformation($userId);

            // if no job title UserInformation record was found, don't try run Match & Rank
            if(!$jobTitleInfo)
                return $this->getFriendlyInformation($userId);

            $jobTitles = json_decode($jobTitleInfo["value"]);

            // at this point, match and rank should be run from jobseeker > filters
            $mr = new MatchAndRankService();
            $mr->userToFilter($userId, $jobTitles);

            return $this->getFriendlyInformation($userId);
        }

        private function parseJobTitles($information)
        {
            $value = $information["value"];

            // $value is an array of strings
            $professionNames = json_decode($value);

            if(count($professionNames) == 0)
            {
                $information["value"] = null;
                return $information;
            }

            foreach($professionNames as &$professionName)
                $professionName = strtolower($professionName);

            // unlikely, but remove any duplicates
            $professionNames = array_unique($professionNames);

            // find the IDs of all the exact matches in the databases
            $query = Doctrine_Query::create()
                    ->select("p.id, p.name")
                    ->from("Profession p")
                    ->whereIn("LOWER(p.name)", $professionNames);

            $foundProfessions = $query->execute();
            $notFound = array();

            $professionIDs = array();

            foreach($foundProfessions as $profession)
                $professionIDs[] = (int) $profession->id;

            // find the professions that are not in the database
            foreach($professionNames as $name)
            {
                $found = false;
                foreach($foundProfessions as $profession)
                {
                    if(strtolower($profession->name) == $name)
                    {
                        $found = true;
                        break;
                    }
                }

                if(!$found)
                    $notFound[] = $name;
            }

            // add all professions not found in the database
            foreach($notFound as $professionName)
            {
                $profession = new Profession();
                $profession->name = ucwords($professionName);

                $profession->save();
                $professionIDs[] = (int) $profession->id;
            }

            $information["value"] = json_encode($professionIDs);
            return $information;
        }

        public function getFriendlyInformation($userId)
        {
            $userInformation = $this->find(array("userId" => $userId), null, null, null, null);
            $userInformation = $userInformation->source;

            foreach ($userInformation as &$information)
            {
                $type = $information->type;

                switch ($type)
                {
                    case MatchAndRankService::LOCATION:
                        if ($information->lookupId > 0) {
                            $geoService = new GeographicInfoService();
                            $location = $geoService->table->find($information->lookupId);
                            if (!$location)
                                continue;

                            $information->value = json_decode($information->value);

                            if (!$information->value)
                                $information->value = new stdClass();

                            $location = $location->toArray();
                            unset($location["_explicitType"]);

                            $information->value->location = $location;
                            $information->value = json_encode($information->value);
                        }
                        break;
                    case MatchAndRankService::SKILLS_AND_ABILITIES:
                        if ($information->lookupId > 0) {
                            $inputLookupService = new InputLookupService();
                            $industry = $inputLookupService->table->find($information->lookupId);
                            if (!$industry)
                                continue;

                            $information->value = json_encode($industry->value);
                        }
                        break;
                    case MatchAndRankService::JOB_TITLE:
                        $ids = json_decode($information->value);
                        if (count($ids) == 0) {
                            $information->value = json_encode(array());
                            continue;
                        }

                        $query = Doctrine_Query::create()
                                ->select("p.*")
                                ->from("Profession p");

                        foreach ($ids as $id)
                        {
                            $query->orWhere("p.id = $id");
                        }

                        $professions = $query->execute();
                        $data = array();

                        foreach ($professions as $profession)
                        {
                            if (!$profession)
                                continue;

                            $profession = $profession->toArray();
                            unset($profession["_explicitType"]);

                            $data[] = $profession;
                        }

                        $information->value = json_encode($data);
                        break;
                }
            }

            return $userInformation;
        }

        /**
         * Gets the requested user's printable CV data
         *
         * @throws Exception
         * @param $userId
         * @return array
         */
        public function getPrintableData($userId)
        {
            $loggedInUser = UserService::getLoggedInUser();

            $userService = new UserService();

            $user = new User();
            $user = $user->table->find($userId);

            $data = array();

            $query = Doctrine_Query::create()
                    ->select("ui.*")
                    ->from("UserInformation ui, ui.User u")
                    ->where("ui.userId = " . $userId);

            $query->setHydrationMode(Aerial_Core::HYDRATE_AMF_COLLECTION);
            $info = $query->execute();

            $info->source[] = array("type" => MatchAndRankService::NAME,
                                    "value" => json_encode($user->firstName . " " . $user->lastName));

            foreach ($info->source as $infoElement)
            {
                // relocation and travel distance need to use the same data as location
                if ($infoElement["type"] == MatchAndRankService::LOCATION) {
                    $info->source[] = array("type" => MatchAndRankService::WILLING_TO_RELOCATE,
                                            "value" => $infoElement["value"]);

                    $info->source[] = array("type" => MatchAndRankService::TRAVEL_DISTANCE,
                                            "value" => $infoElement["value"]);
                }
            }

            foreach ($info->source as $infoElement)
            {
                // relocation and travel distance need to use the same data as location
                if ($infoElement["type"] != MatchAndRankService::LOCATION) {
                    $info->source[] = array("type" => MatchAndRankService::WILLING_TO_RELOCATE,
                                            "value" => $infoElement["value"]);

                    $info->source[] = array("type" => MatchAndRankService::TRAVEL_DISTANCE,
                                            "value" => $infoElement["value"]);
                }

                $data[$infoElement["type"]] =
                        UserInformationParser::getPrintableData($infoElement["type"], $infoElement);
            }

            // add misc properties
            $data["email"] = $user->email;

            // if no data was found for one or more fields, set them to blank
            $fields = array(MatchAndRankService::INDUSTRY, MatchAndRankService::JOB_TITLE, MatchAndRankService::AGE,
                            MatchAndRankService::GENDER, MatchAndRankService::MARITAL_STATUS, MatchAndRankService::NUM_DEPENDENTS,
                            MatchAndRankService::SKILLS_AND_ABILITIES, MatchAndRankService::JOB_DESCRIPTION,
                            MatchAndRankService::LOCATION, MatchAndRankService::SALARY, MatchAndRankService::QUALIFICATIONS,
                            MatchAndRankService::COMPUTER_LITERACY, MatchAndRankService::EXPERIENCE, MatchAndRankService::SUPERVISORY,
                            MatchAndRankService::LANGUAGES, MatchAndRankService::RACE, MatchAndRankService::DISABILITY,
                            MatchAndRankService::NATIONALITY, MatchAndRankService::TEAM_PLAYER, MatchAndRankService::EMPLOYMENT_TYPE,
                            MatchAndRankService::START_DATE, MatchAndRankService::WORKING_HOURS, MatchAndRankService::TRAVEL_DISTANCE,
                            MatchAndRankService::WILLING_TO_RELOCATE, MatchAndRankService::NAME, MatchAndRankService::IDENTIFICATION_NUMBER,
                            MatchAndRankService::DRIVERS_LICENSE, MatchAndRankService::WORK_PERMIT, MatchAndRankService::REFERENCES,
                            MatchAndRankService::MESSAGE_TO_RECRUITERS, MatchAndRankService::CAREER);

            foreach ($fields as $field)
            {
                if (!isset($data[$field])) {
                    $empty = new stdClass();
                    $empty->value = "";
                    $data[$field] = UserInformationParser::getPrintableData($field, json_encode($empty));
                }
            }

            return $data;
        }
    }

?>