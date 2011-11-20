<?php
	require_once("IEvaluator.php");
	require_once("BaseEvaluator.php");
	require_once(dirname(__FILE__)."/../MatchAndRankService.php");
	require_once("WeightedUserVO.php");

	class JobTitleEvaluator extends BaseEvaluator implements IEvaluator
	{
        /**
         * Deprecated.
         *
         * @static
         * @param $data
         * @param $weight
         * @param null $users
         * @return array|null
         */
		public static function evaluate($data, $weight, &$users=null)
		{
			try
			{
				// load users here only
				if(!$data || count($data) <= 0)
				{
					self::addError("No data");
					return null;
				}

				$data = $data[0];       // only one record for job title
				if(!$data)
					return null;

				$filterData = $data;

				// find ACTIVE users who match at least one title, that haven't been matched to this filter
                // and that have not be purchased already

				$query = "SELECT u.id FROM User u, UserInformation ui
							WHERE u.id NOT IN
							(SELECT userId FROM FilterUserMatch WHERE filterId = ".MatchAndRankService::$filterId.")
							AND u.id NOT IN (SELECT p.purchasedUserId FROM Purchase p ".
                            "WHERE p.filterId = ".MatchAndRankService::$filterId.")
							AND ui.type = 'jobTitle' AND u.active = 1 AND ui.userId = u.id AND u.type = 'Jobseeker'
							AND ui.value REGEXP '([[:punct:]]([--JOBTITLES--])[[:punct:]])' GROUP BY u.id";

				$jobTitles = @json_decode($data->value);

				$whereClause = array();
				foreach($jobTitles as $titleId)
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

				$users = array();
				foreach($results as $user)
				{
					$weighted = new WeightedUserVO();
					$weighted->user = $user;
					$weighted->weight = $weight;
					$weighted->explanations[MatchAndRankService::JOB_TITLE] = $weight;

					$dataMatch = new FilterUserDataMatch();
					$dataMatch->name = MatchAndRankService::JOB_TITLE;
					$dataMatch->possibleScore = 0;
					$dataMatch->achievedScore = 0;
					$dataMatch->FilterData = $filterData;

					$weighted->dataMatches[] = $dataMatch;

					$users[] = $weighted;
				}

				return $users;
			}
			catch(Exception $e)
			{
				self::addError($e);
			}
		}
	}
?>