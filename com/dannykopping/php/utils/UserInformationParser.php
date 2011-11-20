<?php

	import("aerialframework.service.AbstractService");

    $base = ConfigXml::getInstance()->servicesPath;

	require_once("$base/../services/MatchAndRankService.php");
	require_once("$base/../services/GeographicInfoService.php");
	require_once("$base/../services/InputLookupService.php");
	require_once("$base/../services/ProfessionService.php");

    // class extends AbstractService to get access to other services' protected functions
    // THIS CLASS SHOULD NEVER BE CALLED AS A SERVICE
    class UserInformationParser extends AbstractService
    {
        public static function getFriendlyInformation($userInformation)
        {
            foreach($userInformation->source as &$information)
            {
                $type = $information->type;

                switch ($type)
                {
                    case MatchAndRankService::AGE:
                        $ageDetails = @json_decode($information->value);
                        if (!$ageDetails)
                            $information->value = "-";

                        $birthday = $ageDetails->day . " " . $ageDetails->month . " " . $ageDetails->year;
                        $information->value = floor((time() - strtotime($birthday))/31556926);
                        break;
                    case MatchAndRankService::LOCATION:
                        if ($information->lookupId > 0) {
                            $geoService = new GeographicInfoService();
                            $location = $geoService->table->find($information->lookupId);
                            if (!$location)
                                continue;

                            $information->value = json_encode($location->toArray());
                        }
                        break;
                    case MatchAndRankService::INDUSTRY:
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

                        $jobTitles = @json_decode($information->value);
                        $titles = array();

                        if (count($jobTitles) > 0) {
                            foreach ($jobTitles as $titleId)
                            {
                                $professionService = new ProfessionService();
                                $title = $professionService->table->find($titleId);
                                if (!$title)
                                    continue;

                                $titles[] = $title->name;
                            }
                        }

                        $information->value = join("\n", $titles) . (count($jobTitles) > 1 ? "\n" : "");
                        break;
                }
            }

            return $userInformation;
        }

        public static function getPrintableData($field, $data)
        {
            if($data === NULL)
                return "...";

            $data["value"] = @json_decode($data["value"]);

            switch($field)
            {
                case MatchAndRankService::NAME:
                case MatchAndRankService::GENDER:
                case MatchAndRankService::MARITAL_STATUS:
                case MatchAndRankService::NUM_DEPENDENTS:
                case MatchAndRankService::INDUSTRY:
                case MatchAndRankService::NATIONALITY:
                case MatchAndRankService::RACE:
                case MatchAndRankService::IDENTIFICATION_NUMBER:
                    return $data["value"];
                    break;

                case MatchAndRankService::AGE:
                    $ageDetails = $data["value"];
                    if (!$ageDetails)
                        return "-";

                    $birthday = $ageDetails->day . " " . $ageDetails->month . " " . $ageDetails->year;
                    return floor((time() - strtotime($birthday))/31556926);
                    break;

                case MatchAndRankService::TRAVEL_DISTANCE:
                    $value = $data["value"];

                    return $data["value"]->distance === NULL ? "0" : $data["value"]->distance;
                    break;

                case MatchAndRankService::WILLING_TO_RELOCATE:
                    if($data["value"]->relocate === NULL ||
                       ($data["value"]->relocations === NULL) || count($data["value"]->relocations) == 0)
                    {
                        return "Not willing to relocate";
                    }

                    if($data["value"]->relocations !== NULL && count($data["value"]->relocations) > 0)
                        return "Willing to relocate to ".implode(", ", $data["value"]->relocations);

                    break;

                case MatchAndRankService::DRIVERS_LICENSE:
                case MatchAndRankService::SUPERVISORY:
                    return $data["value"] == "true" ? "Yes" : "No";
                    break;

                case MatchAndRankService::MESSAGE_TO_RECRUITERS:
                    return str_replace("\n", "<br/>", $data["value"]);
                    break;

                case MatchAndRankService::EXPERIENCE:
                    if($data["value"]->relevant === NULL || $data["value"]->workforce === NULL)
                        return "";

                    $relevantExp = $data["value"]->relevant->years." years, ".$data["value"]->relevant->months." months";
                    if($data["value"]->relevant->years == 0 && $data["value"]->relevant->months == 0)
                        $relevantExp = "No experience";
                        
                    $workforceExp = $data["value"]->workforce->years." years, ".$data["value"]->workforce->months." months";
                    if($data["value"]->workforce->years == 0 && $data["value"]->workforce->months == 0)
                        $workforceExp = "No experience";
                        
                    return "Relevant experience: ".$relevantExp."<br/>Total experience: ".$workforceExp;
                    break;

                case MatchAndRankService::SKILLS_AND_ABILITIES:
                    return @implode("<br/>", $data["value"]);
                    break;

                case MatchAndRankService::WORK_PERMIT:
                    return $data["value"] == "true" ? "<i>Valid work permit</i>" : "<i>No valid work permit</i>";
                    break;

                case MatchAndRankService::COMPUTER_LITERACY:
                    if(!$data["value"] || count($data["value"]) == 0)
                        return "None";

                    $computerSkills = array();

                    if (count($data["value"]) > 0 && is_array($data["value"]))
                    {
                        foreach($data["value"] as $item)
                            $computerSkills[] = $item->skill." (".$item->proficiency.")";
                    }

                    return implode("<br/>", $computerSkills);
                    break;

                case MatchAndRankService::LANGUAGES:
                    if(!$data["value"])
                        $data["value"] = "";

                    return @implode(", ", $data["value"]);
                    break;

                case MatchAndRankService::JOB_TITLE:
                        $jobTitles = $data["value"];
                        $titles = array();

                        if (count($jobTitles) > 0 && is_array($jobTitles))
                        {
                            foreach ($jobTitles as $titleId)
                            {
                                $professionService = new ProfessionService();
                                $title = $professionService->table->find($titleId);
                                if (!$title)
                                    continue;

                                $titles[] = $title->name;
                            }
                        }

                        return join("<br/>", $titles) . (count($jobTitles) > 1 ? "<br/>" : "");
                    break;

                case MatchAndRankService::LOCATION:
                    if(!is_object($data))
                        return "-";

                    if ($data->lookupId > 0)
                    {
                        $geoService = new GeographicInfoService();
                        $location = $geoService->table->find($data->lookupId);
                        if (!$location)
                            $data->value = "-";
                    }
                    else
                    {
                        $data->value = "-";
                        break;
                    }

                    $str = "";

                    if($location->area1 && strlen($location->area1) > 0)
                    {
                        $str .= $location->area1;
                        $str .= ($location->city && strlen($location->city) > 0) ? ", " : "";
                    }

                    if($location->city && strlen($location->city) > 0)
                        $str .= $location->city;

                    return $str;
                    break;

                case MatchAndRankService::QUALIFICATIONS:

                    $qualifications = $data["value"];
                    if(!$qualifications || count($qualifications) == 0)
                        return null;

                    if (count($qualifications) > 0 && is_array($qualifications))
                    {
                        // fields can remain the same, except inProgress/completionDate
                        foreach($qualifications as &$qualification)
                        {
                            $completion = "";
                            if($qualification->inProgress == "true")
                                $completion = "In progress";
                            else if($qualification->completionDate->year !== NULL && $qualification->completionDate->month !== NULL)
                                $completion = $qualification->completionDate->month." ".$qualification->completionDate->year;

                            unset($qualification->completionDate);
                            $qualification->completion = $completion;
                        }
                    }

                    return $qualifications;

                    break;

                case MatchAndRankService::REFERENCES:

                    $references = $data["value"];
                    if(!$references || count($references) == 0)
                        return null;

                    return $references;

                    break;

                case MatchAndRankService::CAREER:

                    $careerInfo = $data["value"];
                    if(!$careerInfo || count($careerInfo) == 0 || !is_object($careerInfo))
                        return null;

                    foreach($careerInfo->careers as &$career)
                    {
                        $date = "";
                        $startDate = $career->startDate->month." ".$career->startDate->year;
                        $endDate = $career->endDate->month." ".$career->endDate->year;

                        if($career->current == "true")
                            $date = $startDate." to present";
                        else
                            $date = $startDate." to ".$endDate;

                        $career->date = $date;

                        if($career->salary > 0)
                            $career->remuneration = $career->remuneration.": <strong>R".$career->salary."</strong> <i>$career->frequency</i>";
                        else
                            $career->remuneration = "";
                    }

                    return $careerInfo;

                    break;

                case MatchAndRankService::EMPLOYMENT_TYPE:
                    return $data["value"] ? $data["value"]->type : "";
                    break;

                case MatchAndRankService::SALARY:
                    $salaryData = $data["value"];

                    if(!$salaryData)
                        return "";

                    return "R".number_format($salaryData->netSalary)."<br/>".
                            "R".number_format($salaryData->salary)."<br/>".
                            (strlen(trim($salaryData->benefits)) == 0 ? "-" : $salaryData->benefits);

                    break;

                case MatchAndRankService::START_DATE:
                    $startDateInfo = $data["value"];

                    if($startDateInfo->type == "Start Date")
                        return date("M d Y", ($startDateInfo->startDate / 1000));

                    return $startDateInfo->type;

                    break;

                case MatchAndRankService::DISABILITY:
                    $disabilityData = $data["value"];

                    if(!$disabilityData || !$disabilityData->disabled)
                        return "None";

                    return $disabilityData->explain;

                    break;
            }
        }
        
        public static function getLabel($criterionName)
        {
            switch($criterionName)
            {
                case MatchAndRankService::AGE:
                    return "Age";
                    break;
                case MatchAndRankService::GENDER:
                    return "Gender";
                    break;
                case MatchAndRankService::DRIVERS_LICENSE:
                    return "Driver's License";
                    break;
                case MatchAndRankService::WORK_PERMIT:
                    return "Valid Work Permit";
                    break;
                case MatchAndRankService::MARITAL_STATUS:
                    return "Marital Status";
                    break;
                case MatchAndRankService::INDUSTRY:
                    return "Industry";
                    break;
                case MatchAndRankService::NATIONALITY:
                    return "Nationality";
                    break;
                case MatchAndRankService::RACE:
                    return "Employment Equity /\nAffirmative Action status";
                    break;
                case MatchAndRankService::JOB_TITLE:
                    return "Job Title(s)";
                    break;
                case MatchAndRankService::LOCATION:
                    return "Location";
                    break;
                case MatchAndRankService::DISABILITY:
                    return "Disabled?";
                    break;
                case MatchAndRankService::SUPERVISORY:
                    return "Supervisory Experience?";
                    break;
                case MatchAndRankService::SKILLS_AND_ABILITIES:
                    return "Skills & Abilities";
                    break;
                case MatchAndRankService::SALARY:
                    return "Salary (Cost to Company & Net)";
                    break;
                case MatchAndRankService::QUALIFICATIONS:
                    return "Education";
                    break;
                case MatchAndRankService::EXPERIENCE:
                    return "Experience";
                    break;
                case MatchAndRankService::EMPLOYMENT_TYPE:
                    return "Employment Type";
                    break;
                case MatchAndRankService::LANGUAGES:
                    return "Languages";
                    break;
                case MatchAndRankService::COMPUTER_LITERACY:
                    return "Computer Skills";
                    break;
                case MatchAndRankService::WORKING_HOURS:
                    return "Working Hours";
                    break;
                case MatchAndRankService::START_DATE:
                    return "Start Date/Notice Period";
                    break;
                case MatchAndRankService::NUM_DEPENDENTS:
                    return "Number of Dependents";
                    break;
                case MatchAndRankService::MESSAGE_TO_RECRUITERS:
                    return "Message to Recruiters";
                    break;
                case MatchAndRankService::CAREER:
                    return "Career History";
                    break;
                case MatchAndRankService::REFERENCES:
                    return "References";
                    break;
                case MatchAndRankService::IDENTIFICATION_NUMBER:
                    return "Identification Number";
                    break;

                default:
                    return "---";
                    break;
            }

            return "---";
        }
    }

?>