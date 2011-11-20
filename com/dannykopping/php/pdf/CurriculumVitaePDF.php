<?php

    require_once("BasePDF.php");

    require_once(ConfigXml::getInstance()->servicesPath . "/UserService.php");
    require_once(ConfigXml::getInstance()->servicesPath . "/../utils/UserInformationParser.php");

    class CurriculumVitaePDF extends BasePDF
    {
        const JOBSEEKER_NAME = "JOBSEEKER_NAME";

        const LABEL = "LABEL";
        const DATA = "DATA";
        const ITEMS = "ITEMS";

        const PATH = "PATH";

        const EDUCATION_ITEM = "educationItem";
        const REFERENCE_ITEM = "referenceItem";
        const CAREER_ITEM = "careerItem";

        public $userId;

        private $educationItemRenderer = <<<EOD

            <tr>
                <td style="font-weight: bold;" class="tableItem">{{qualification}}</td>
                <td class="tableItem">{{institution}}</td>
                <td class="tableItem">{{level}}</td>
                <td class="tableItem">{{type}}</td>
                <td class="tableItem">{{subjects}}</td>
                <td class="tableItem">{{completion}}</td>
            </tr>
EOD;

        private $referenceItemRenderer = <<<EOD

            <tr>
                <td style="font-weight: bold;" class="tableItem">{{fullName}}</td>
                <td class="tableItem">{{company}}</td>
                <td class="tableItem">{{relationship}}</td>
                <td class="tableItem">{{contact}}</td>
            </tr>
EOD;

        private $careerItemRenderer = <<<EOD
        <tr>
            <td class="label">
                <p>Position</p>
            </td>
            <td class="data">
                <p><strong>{{title}}</strong> <i>{{level}}, {{type}}</i></p>
            </td>
        </tr>

        <tr>
            <td class="label">
                <p>Date</p>
            </td>
            <td class="data">
                <p>{{date}}</p>
            </td>
        </tr>

        <tr>
            <td class="label">
                <p>Duties &amp; Responsibilities</p>
            </td>
            <td class="data">
                <p>{{duties}}</p>
            </td>
        </tr>

        <tr>
            <td class="label">
                <p>Reason for Leaving</p>
            </td>
            <td class="data">
                <p>{{reasonForLeaving}}</p>
            </td>
        </tr>

        <tr>
            <td class="label">
                <p>Company</p>
            </td>
            <td class="data">
                <p>{{employerName}}</p>
            </td>
        </tr>

        <tr>
            <td class="label">
                <p>Location</p>
            </td>
            <td class="data">
                <p>{{location}}</p>
            </td>
        </tr>

        <tr>
            <td class="label">
                <p>Industry</p>
            </td>
            <td class="data">
                <p>{{industry}}</p>
            </td>
        </tr>

        <tr>
            <td class="label">
                <p>Remuneration</p>
            </td>
            <td class="data">
                <p>{{remuneration}}</p>
            </td>
        </tr>

        <tr>
            <td class="label">
                <p>Benefits</p>
            </td>
            <td class="data">
                <p>{{benefits}}</p>
            </td>
        </tr>

        <tr><td colspan="2" class="separator" height="1">&nbsp;</td></tr>
EOD;

        private $items;

        public function __construct()
        {
            parent::__construct();

            $footer = '<div align="center" style="font-size: 12px">{PAGENO}</div>';
            $this->pdf->SetHTMLFooter($footer);

            $this->pdf->SetTitle("Curriculum Vitae");
            $this->pdf->SetAuthor("GetTheJob.co.za");
            $this->pdf->SetCreator("GetTheJob.co.za");
        }

        public function generate($userId=null)
        {
            if($userId !== NULL)
                $this->userId = $userId;

            $service = new UserService();

//            $loggedInUser = UserService::getLoggedInUser();
            if (!$service->checkOwnershipOfCV($this->userId))
                die("CV not purchased");

            $data = array();

            $query = Doctrine_Query::create()
                    ->select("ui.*")
                    ->from("UserInformation ui, ui.User u")
                    ->where("ui.userId = " . $this->userId);

            $user = $this->getUser();

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

            $this->setData($data);
            $this->write($this->getTemplate());

            $contents = $this->getContents("test.pdf");
            return $contents;
        }

        public function getUser()
        {
            $user = new User();
            $user = $user->table->find($this->userId);

            return $user;
        }

        public function getTemplate()
        {
            return file_get_contents(dirname(__FILE__)."/templates/curriculum-vitae.html");
        }

        public function getStylesheet()
        {
            return file_get_contents(dirname(__FILE__)."/templates/curriculum-vitae.css");
        }

        public function setData($data)
        {
            $this->items = $data;
        }

        public function write($htmlContent)
        {
            foreach($this->items as $key => $value)
            {
                switch($key)
                {
                    case MatchAndRankService::QUALIFICATIONS:
                        $htmlContent = $this->replace($key, $this->parseEducation($value), $htmlContent);
                        break;
                    case MatchAndRankService::REFERENCES:
                        $htmlContent = $this->replace($key, $this->parseReferences($value), $htmlContent);
                        break;
                    case MatchAndRankService::CAREER:
                        $htmlContent = $this->replace($key, $this->parseCareer($value), $htmlContent);
                        break;
                    default:
                        $htmlContent = $this->replace($key, $value, $htmlContent);
                        break;
                }
            }

            // replace filepath
            $htmlContent = $this->replace(self::PATH, dirname(__FILE__), $htmlContent);

            parent::write($htmlContent);
        }

        private function getItemRenderer($type)
        {
            switch($type)
            {
                case self::EDUCATION_ITEM:
                    return $this->educationItemRenderer;
                    break;

                case self::REFERENCE_ITEM:
                    return $this->referenceItemRenderer;
                    break;

                case self::CAREER_ITEM:
                    return $this->careerItemRenderer;
                    break;

                default:
                    return null;
                    break;
            }
        }

        private function parseEducation($educations)
        {
            $output = "";

            if(!$educations || count($educations) == 0)
                return "";

            foreach($educations as $education)
            {
                $itemRenderer = $this->getItemRenderer(self::EDUCATION_ITEM);
                foreach($education as $key => $value)
                    $itemRenderer = $this->replace($key, $value, $itemRenderer);

                $output .= $itemRenderer."\n";
            }

            return $output;
        }

        private function parseReferences($references)
        {
            $output = "";

            if(!$references || count($references) == 0)
                return "";

            foreach($references as $reference)
            {
                $itemRenderer = $this->getItemRenderer(self::REFERENCE_ITEM);
                foreach($reference as $key => $value)
                    $itemRenderer = $this->replace($key, $value, $itemRenderer);

                $output .= $itemRenderer."\n";
            }

            return $output;
        }

        private function parseCareer($careerInfo)
        {
            $output = "";

            if(!$careerInfo || count($careerInfo) == 0)
                return "";

            $noHistoryCheck = false;
            if(strlen(trim($careerInfo->careers[0]->title)) == 0)
                $noHistoryCheck = true;

            if($careerInfo->noHistory == "true" || $noHistoryCheck)
                return "<tr><td colspan='2'>No career history</td></tr>";

            foreach($careerInfo->careers as $career)
            {
                $itemRenderer = $this->getItemRenderer(self::CAREER_ITEM);
                foreach($career as $key => $value)
                    $itemRenderer = $this->replace($key, $value, $itemRenderer);

                $output .= $itemRenderer."\n";
            }

            return $output;
        }
    }

?>