<?php
	require_once("evaluation/JobTitleEvaluator.php");
	require_once("evaluation/IndustryEvaluator.php");
	require_once("evaluation/SkillsAndAbilitiesEvaluator.php");
	require_once("evaluation/LocationEvaluator.php");
	require_once("evaluation/SalaryEvaluator.php");
	require_once("evaluation/QualificationsEvaluator.php");
	require_once("evaluation/ComputerLiteracyEvaluator.php");
	require_once("evaluation/ExperienceEvaluator.php");
	require_once("evaluation/SupervisoryEvaluator.php");
	require_once("evaluation/LanguagesEvaluator.php");
	require_once("evaluation/EmploymentEquityEvaluator.php");
	require_once("evaluation/DisabilityEvaluator.php");
	require_once("evaluation/TeamPlayerEvaluator.php");
	require_once("evaluation/EmploymentTypeEvaluator.php");
	require_once("evaluation/StartDateEvaluator.php");
	require_once("evaluation/RaceEvaluator.php");

	require_once(dirname(__FILE__)."/../utils/MySQLUtil.php");

    require_once("UserService.php");

	/**
	 * Two kinds of match&rank
	 *
	 * Info => Filter
	 * Filter => Info
	 *
	 * Info is information filled in by users
	 * Filters are created by recruiters
     *
	 *
	 * |--------------------------------------|
	 * | 0 = Not important                    |
	 * | 1 = Mildly important                 |
	 * | 2 = Important                        |
	 * | 3 = Moderately important             |
	 * | 4 = Very important (disqualifying)   |
	 * | 5 = Job title special weighting      |
     * |--------------------------------------|
	 */
	class MatchAndRankService
	{
        const INDUSTRY = "industry";
        const JOB_TITLE = "jobTitle";
        const AGE = "age";
        const GENDER = "gender";
        const MARITAL_STATUS = "maritalStatus";
        const NUM_DEPENDENTS = "numDependents";
        const SKILLS_AND_ABILITIES = "skillsAndAbilities";
        const JOB_DESCRIPTION = "jobDescription";
        const LOCATION = "location";
        const SALARY = "salary";
        const QUALIFICATIONS = "education";
        const COMPUTER_LITERACY = "computerLiteracy";
        const EXPERIENCE = "experience";
        const SUPERVISORY = "supervisory";
        const LANGUAGES = "languages";
		const RACE = "race";
        const DISABILITY = "disability";
        const NATIONALITY = "nationality";
        const TEAM_PLAYER = "teamPlayer";
        const EMPLOYMENT_TYPE = "employmentType";
        const START_DATE = "startDate";
        const WORKING_HOURS = "workingHours";
        const TRAVEL_DISTANCE = "travelDistance";
        const WILLING_TO_RELOCATE = "willingToRelocate";
		
		const NAME = "name";
        const IDENTIFICATION_NUMBER = "identificationNumber";
        const DRIVERS_LICENSE = "driversLicense";
        const WORK_PERMIT = "workPermit";
        const REFERENCES = "references";
        const MESSAGE_TO_RECRUITERS = "messageToRecruiters";
        const CAREER = "career";

		const DISQUALIFIED = "disqualified";

		const NON_RANK = "nonRank";
		const RANK = "rank";
		const CONDITIONAL_RANK = "conditional";
		const DESCRIPTIVE = "descriptive";

		private static $users;

		private static $rankModes = array(
			self::INDUSTRY => self::RANK,
			self::JOB_TITLE => self::RANK,
			self::SKILLS_AND_ABILITIES => self::RANK,
			self::LOCATION => self::RANK,
			self::SALARY => self::RANK,
			self::QUALIFICATIONS => self::RANK,
			self::EXPERIENCE => self::RANK,
			self::SUPERVISORY => self::RANK,
			self::LANGUAGES => self::RANK,
			self::COMPUTER_LITERACY => self::RANK,
			self::DISABILITY => self::CONDITIONAL_RANK,
			self::RACE => self::CONDITIONAL_RANK,
			self::EMPLOYMENT_TYPE => self::RANK,
			self::TEAM_PLAYER => self::RANK,
			self::START_DATE => self::RANK,
			self::TRAVEL_DISTANCE => self::RANK,
			self::WILLING_TO_RELOCATE => self::RANK,
			self::JOB_DESCRIPTION => self::DESCRIPTIVE,
			self::WORKING_HOURS => self::DESCRIPTIVE,
			self::NATIONALITY => self::DESCRIPTIVE,
			self::AGE => self::NON_RANK,
			self::GENDER => self::NON_RANK,
			self::MARITAL_STATUS => self::NON_RANK,
			self::NUM_DEPENDENTS => self::NON_RANK,
		);

		private static $weightCounts = array("0" => 0,"1" => 0,"2" => 0,"3" => 0,"4" => 0,"5" => 0);
		private static $total = 0;

		private static $jobTitleWeighting = 0;

		public static $filterId;

        public $jobTitleScores;

		public function filterToInfo($filterId, $clear=false)
		{
            // check if the requisite functions are available in MySQL
            MySQLUtil::checkJaroWinkler();

            $user = UserService::getLoggedInUser();

            self::$filterId = $filterId;

			if($clear)
				$this->clearMatches($filterId);

            return $this->matchAndRank($filterId);
		}

        /**
         * Match and rank a single user to all filters that have one or more of the same job titles
         *
         * @param int $userId
         * @param array $jobTitles
         * @return void
         */
        public function userToFilter($userId, $jobTitles)
        {
            if(!$jobTitles || count($jobTitles) == 0)
                return null;

            // remove all FilterUserMatch records related to this userId
            $removeExistingQuery = Doctrine_Query::create()
                                    ->delete("FilterUserMatch fum")
                                    ->where("fum.userId = $userId");

            // finds filters to which user has not been matched (inactive for now)
            // ... AND (fum.userId != $userId OR ISNULL(fum.userId))
            
            $removeExistingQuery->execute();

            // query to get all filters that match a jobseekers title(s), excluding those from which the user has
            // already been purchased
            $query = "SELECT DISTINCT(f.id) FROM Filter f LEFT JOIN FilterUserMatch fum ON (fum.filterId = f.id) WHERE f.id IN
                            (SELECT fc.filterId FROM FilterData fd
                            INNER JOIN FilterCriterion fc ON (fd.filterCriterionId = fc.id)
                            WHERE fd.value REGEXP '([[:punct:]]([--JOBTITLES--])[[:punct:]])'
                            AND fc.name = 'jobTitle')
                      AND f.id NOT IN (SELECT p.filterId FROM Purchase p WHERE p.purchasedUserId = $userId)";

            // replace jobtitles placeholder with pipe-delimited list of job titles
            $query = str_replace("[--JOBTITLES--]", join("|", $jobTitles), $query);

            $query = Bootstrapper::getInstance()->conn->getDbh()->query($query);
            $results = $query->fetchAll(PDO::FETCH_OBJ);

            if(count($results) == 0)
                return null;

            $filterIds = array();
            foreach($results as $result)
                $filterIds[] = $result->id;

            // now that we have an array of filters, let's get the FilterCriterion records associated with them
            // and then run the match and rank algorithm

            foreach($filterIds as $filterId)
            {
                $u = new User();
                $u = $u->table->find($userId);

                $filterDataQuery = Doctrine_Query::create()
                                    ->select("fc.id, fd.*")
                                    ->from("FilterCriterion fc, fc.filterDatas fd")
                                    ->where("fc.filterId = $filterId")
                                    ->andWhere("fc.name = '".self::JOB_TITLE."'")
                                    ->limit(1);

                $filterData = $filterDataQuery->execute();

                if(!$filterData || count($filterData) <= 0)
                    continue;

                $filterData = $filterData[0]->filterDatas;
                if(!$filterData || count($filterData) <= 0)
                    continue;

                $jobTitleFilterData = $filterData[0];
                $this->matchAndRank($filterId, array($u), $jobTitleFilterData);
            }
        }

        private function matchAndRank($filterId, $users=null, $filterData=null)
        {
			$filters = $this->getFilterWithOrderedWeights($filterId);
			if(!$filters || count($filters) == 0)
			{
				throw new Exception("No filter exists with id of $filterId");
			}

            // prepare filter criteria

			$filter = $filters[0];
			$criteria = $filter->filterCriterions;
			$criteria = $this->orderJobTitleToTop($criteria);

			if(!$criteria || count($criteria) == 0)
			{
				throw new Exception("No criteria");
			}

            // calculate the total weight
			$totalWeight = 0;
			foreach($criteria as $criterion)
			{
				if($criterion->name != self::JOB_TITLE)
					$totalWeight += $criterion->weight;
			}

            // evaluate the job title criterion separately because it influences all the other criteria
            $jobTitleCriterion = $criteria[0];

            // if a list of users is already provided, don't try get a list of users
            if(!$users || count($users) <= 0)
                $this->evaluateJobTitles($jobTitleCriterion);
            else
            {
                self::$users = $this->prepareUsers($users, $filterData);
            }

            // step through each criterion and evaluate whether the users fit the parameters
			$noMatches = false;
			foreach($criteria as $criterion)
			{
				if(!is_object($criterion))
					continue;

				$type = $criterion->name;
				if(!$type)
				{
					$_errors[] = array("error" => "No criterion type named $type found.", "data" => $criterion);
					return self::DISQUALIFIED;
				}

				$this->evaluate($criterion->filterDatas, (int) $criterion->weight, $type);

				if(count(self::$users) == 0)
				{
					$noMatches = true;
					break;
				}

				$this->cleanUsers();
				//NetDebug::trace("After evaluating $type, there are ".count(self::$users)." users");
			}

			if($noMatches)
				return null;

			self::evaluateWeightings();

            // sort all the users by weight
			self::$users = self::subvalSort(self::$users, "weight");
			if(!self::$users)
				return null;

            // create all the FilterUserMatch records
			foreach(self::$users as $weightedUser)
			{
                $jobTitleMatchPercentage = $this->getJobTitlePercentage($weightedUser->user);

				if($totalWeight > 0)
				{
					$percentage = round(($weightedUser->weight / $totalWeight) * 75, 2);
					if($percentage > 75)        // only score up to 75% for all filters, job title can fill the other 25%
						$percentage = 75;

					$percentage += $jobTitleMatchPercentage;
				}
				else
					$percentage = 100;

				$filterMatch = new FilterUserMatch();
				$filterMatch->filterId = $filterId;
				$filterMatch->userId = $weightedUser->user->id;
				$filterMatch->percentage = $percentage;

				$dataMatches = $weightedUser->dataMatches;
				foreach($dataMatches as $dataMatch)
				{
					if($dataMatch->name == self::JOB_TITLE)
					{
						$dataMatch->possibleScore = $totalWeight * ($jobTitleMatchPercentage / 100);
						$dataMatch->achievedScore = $totalWeight * ($jobTitleMatchPercentage / 100);
					}

					$dataMatch->FilterUserMatch = $filterMatch;
					$dataMatch->save();
				}

				$filterMatch->save();
			}

			return self::$users;
        }

        /**
         * Get all users matching the job title(s)
         *
         * @param $criterion
         * @return void
         */
        private function evaluateJobTitles($criterion)
        {
            if($criterion->name != self::JOB_TITLE)
                throw new Exception("Criterion is not a job title criterion.");

            self::$users = $this->getUsersByJobTitle($criterion->filterDatas, 0);       // 0 weight for job title
        }

        /**
         * Gets all the users in the database that match the job title criterion
         *
         * @param $data
         * @param $weight
         * @return array|null An array of users
         */
        private function getUsersByJobTitle($data, $weight)
        {
            try
            {
                // load users here only
				if(!$data || count($data) <= 0)
				{
					JobTitleEvaluator::addError("No data");
					return null;
				}

				$data = $data[0];       // only one record for job title
				if(!$data)
					return null;

				$filterData = $data;

                $values = json_decode($filterData->value);
                $professionService = new ProfessionService();

                $similarTitles = $professionService->findSimilar(implode(" ", $values));

                $similarTitleIDs = array();
                $similarTitleScores = array();

                if(!$similarTitles || count($similarTitles) == 0)
                    return null;

                foreach($similarTitles as $similarTitle)
                {
                    $similarTitleIDs[] = $similarTitle->id;
                    $similarTitleScores[(string) $similarTitle->id] = $similarTitle->score;
                }

                $this->jobTitleScores = $similarTitleScores;
                
                // find ACTIVE users who match at least one title, that haven't been matched to this filter
                // and that have not be purchased already

                $filterId = self::$filterId;
				$query = "SELECT u.id FROM UserInformation ui
                            INNER JOIN `User` u ON ui.userId = u.id
                            LEFT JOIN `Purchase` p ON ui.userId = p.purchasedUserId AND p.filterId = $filterId
                            WHERE ui.value REGEXP '([[:punct:]]([--JOBTITLES--])[[:punct:]])'
                            AND ui.type = 'jobTitle'
                            AND u.active = 1
                            AND u.type = 'Jobseeker'
                            AND p.purchasedUserId IS NULL
                            GROUP BY u.id";

				$whereClause = array();
				foreach($similarTitleIDs as $titleId)
				{
					$whereClause[] = $titleId;
				}

				$query = str_replace("[--JOBTITLES--]", join("|", $whereClause), $query);
				$query = Bootstrapper::getInstance()->conn->getDbh()->query($query);
				$results = $query->fetchAll(PDO::FETCH_OBJ);

				if(count($results) == 0)
					return null;

				$query = Doctrine_Query::create()
						->select("u.id, ui.*")
						->from("User u, u.userInformations ui");

				// we now have all the ids of the relevant users
				foreach($results as $user)
					$query->orWhere("u.id = ".$user->id);

				$results = $query->execute();

				$users = $this->prepareUsers($results, $filterData);
				return $users;
			}
			catch(Exception $e)
			{
				JobTitleEvaluator::addError($e);
                return null;
			}

            return null;
        }

        /**
         * Returns the highest job title percentage match of the given user
         *
         * @param $user
         * @return int
         */
        private function getJobTitlePercentage($user)
        {
            if(!$user || !$user->userInformations || count($user->userInformations) == 0)
                return 0;

            $userInfo = $user->userInformations;

            foreach($userInfo as $userInformation)
            {
                if($userInformation->type == MatchAndRankService::JOB_TITLE)
                {
                    $jobTitleIDs = json_decode($userInformation->value);

                    $highestPercentage = 0;

                    if(!$jobTitleIDs || count($jobTitleIDs) == 0)
                    {
                        $highestPercentage = 0;
                        break;
                    }

                    foreach($jobTitleIDs as $jobTitleID)
                    {
                        if(!isset($this->jobTitleScores[$jobTitleID]))
                            continue;

                        $percentage = (int) $this->jobTitleScores[$jobTitleID];
                        if($percentage > $highestPercentage)
                            $highestPercentage = $percentage;
                    }
                }
            }

            return $highestPercentage;
        }

        /**
         * Evaluates all the weights and updates the total
         *
         * @static
         * @return void
         */
		private static function evaluateWeightings()
		{
			$total = 0;

			foreach(self::$weightCounts as $weight => $count)
				$total += ($weight * 20) * $count;

			self::$total = $total;
		}

        /**
         * Sorts an array based on one key
         *
         * @static
         * @param $a
         * @param $subkey
         * @return array|null
         */
		private static function subvalSort($a,$subkey)
		{
			foreach($a as $k=>$v) {
				$v = (array) $v;

				$b[$k] = strtolower($v[$subkey]);
			}

			if(!is_array($b))
				return null;

			arsort($b);
			foreach($b as $key=>$val) {
				$val = (array) $val;

				$c[] = $a[$key];
			}
			return $c;
		}

        /**
         * Sort all the criteria with the Job Title criterion coming up first (order of evaluation)
         *
         * @param $criteria
         * @return array
         */
		private function orderJobTitleToTop($criteria)
		{
			$newCriteria = array("");
			foreach($criteria as $criterion)
			{
                // ignore non-matching or descriptive criteria
                $rankMode = self::$rankModes[$criterion->name];

                if($rankMode == self::DESCRIPTIVE || $rankMode == self::NON_RANK)
                    continue;

				if($criterion->name != self::JOB_TITLE)
					$newCriteria[] = $criterion;
				else
					$newCriteria[0] = $criterion;
			}

			return $newCriteria;
		}

        /**
         * Evaluate whether a user's data matches the criterion
         *
         * @param $data
         * @param $weight
         * @param $type
         * @return
         */
		private function evaluate($data, $weight, $type)
		{
			if(self::$rankModes[$type] != self::RANK && self::$rankModes[$type] != self::CONDITIONAL_RANK)
				return;
			
			//NetDebug::trace("Mode: ".self::$rankModes[$type]. " > ".$type);
			$hasEvaluator = true;

			switch($type)
			{
				case self::JOB_TITLE:
					$weight = 0;		// assign 0 weight to job titles (special category)
					break;
				case self::INDUSTRY:
					IndustryEvaluator::evaluate($data, $weight, self::$users);
					break;
				case self::SKILLS_AND_ABILITIES:
					SkillsAndAbilitiesEvaluator::evaluate($data, $weight, self::$users);
					break;
				case self::LOCATION:
					LocationEvaluator::evaluate($data, $weight, self::$users);
					break;
				case self::SALARY:
					SalaryEvaluator::evaluate($data, $weight, self::$users);
					break;
				case self::QUALIFICATIONS:
					QualificationsEvaluator::evaluate($data, $weight, self::$users);
					break;
				case self::COMPUTER_LITERACY:
					ComputerLiteracyEvaluator::evaluate($data, $weight, self::$users);
					break;
				case self::EXPERIENCE:
					ExperienceEvaluator::evaluate($data, $weight, self::$users);
					break;
				case self::SUPERVISORY:
					SupervisoryEvaluator::evaluate($data, $weight, self::$users);
					break;
				case self::LANGUAGES:
					LanguagesEvaluator::evaluate($data, $weight, self::$users);
					break;
				case self::RACE:
					RaceEvaluator::evaluate($data, $weight, self::$users);
					break;
				case self::DISABILITY:
					DisabilityEvaluator::evaluate($data, $weight, self::$users);
					break;
				case self::TEAM_PLAYER:
					TeamPlayerEvaluator::evaluate($data, $weight, self::$users);
					break;
				case self::EMPLOYMENT_TYPE:
					EmploymentTypeEvaluator::evaluate($data, $weight, self::$users);
					break;
				case self::START_DATE:
					StartDateEvaluator::evaluate($data, $weight, self::$users);
					break;
				default:
					$hasEvaluator = false;
					break;
			}

			if($hasEvaluator)
			{
				if(!self::$weightCounts[$weight])
					self::$weightCounts[$weight] = 0;

				self::$weightCounts[$weight]++;
			}
		}

        /**
         * Removes all the disqualified users from the users list
         *
         * @return
         */
		private function cleanUsers()
		{
			$newUsers = array();

			if(count(self::$users) == 0)
				return;

			foreach(self::$users as $weightedUser)
			{
				if(!$weightedUser->disqualified)
					$newUsers[] = $weightedUser;
			}

			self::$users = $newUsers;
		}

        /**
         * Clears any existing matches in the database
         *
         * @param $filterId
         * @return Doctrine_Collection
         */
        private function clearMatches($filterId)
        {
            $matchQuery = Doctrine_Query::create()
                            ->select("f.*")
                            ->from("FilterUserMatch f")
                            ->where("f.filterId = $filterId");

            $matches = $matchQuery->execute();
            return $matches->delete();
        }

        /**
         * get criteria from specific filter, ordered by weights (highest first)
         *
         * @param $filterId
         * @return Doctrine_Collection
         */
        private function getFilterWithOrderedWeights($filterId)
        {
			$query = Doctrine_Query::create()
					->select("f.*, fc.*, fd.*")
					->from("Filter f, f.filterCriterions fc, fc.filterDatas fd")
					->where("f.id = $filterId")
					->orderBy("fc.weight DESC");

			return $query->execute();
        }

        /**
         * Prepares a number of WeightedUser instances based on a number of User records and a job title FilterData record
         *
         * @param $users
         * @param $filterData
         * @return array
         */
        private function prepareUsers($users, $filterData)
        {
            $weightedUsers = array();

            foreach($users as $user)
            {
                $weighted = new WeightedUserVO();
                $weighted->user = $user;
                $weighted->weight = 0;
                $weighted->explanations[MatchAndRankService::JOB_TITLE] = 0;

                $dataMatch = new FilterUserDataMatch();
                $dataMatch->name = MatchAndRankService::JOB_TITLE;
                $dataMatch->possibleScore = 0;
                $dataMatch->achievedScore = 0;
                $dataMatch->FilterData = $filterData;

                $weighted->dataMatches[] = $dataMatch;

                $weightedUsers[] = $weighted;
            }

            return $weightedUsers;
        }
    }
?>