<?php
	require_once("IEvaluator.php");
	require_once("BaseEvaluator.php");
	require_once(dirname(__FILE__)."/../MatchAndRankService.php");
	require_once("WeightedUserVO.php");

	class RaceEvaluator extends BaseEvaluator implements IEvaluator
	{
		public static function evaluate($data, $weight, &$users=null)
		{
			try
			{
				if(!$data || count($data) <= 0)
				{
					self::addError("No data");
					return null;
				}

				$data = $data[0];               // only one race
				if(!$data)
					return null;

				$filterData = $data;

				$affirmativeAction = json_decode($data->value) != "White";

				foreach($users as &$weightedUser)
				{
					$user = $weightedUser->user;
					$information = self::getInformation(MatchAndRankService::RACE, $user);

					if(!$information)
					{
						$weightedUser->disqualified = true;
						continue;
					}

					$isAffirmative = json_decode($information->value) != "White";

					if($affirmativeAction && $isAffirmative)
					{
						$weightedUser->weight += $weight;
						$weightedUser->explanations[MatchAndRankService::RACE] = $weight;

						// data matches
						$dataMatch = new FilterUserDataMatch();
						$dataMatch->name = MatchAndRankService::RACE;
						$dataMatch->possibleScore = $weight;
						$dataMatch->achievedScore = $weight;
						$dataMatch->filterDataId = $filterData->id;

						$weightedUser->dataMatches[] = $dataMatch;
					}
					else
					{
						if($weight == self::VERY_IMPORTANT && $affirmativeAction)       // only disqualify if affirmative action is needed
							$weightedUser->disqualified = true;
					}
				}
			}
			catch(Exception $e)
			{
				self::addError($e);
			}
		}
	}
?>