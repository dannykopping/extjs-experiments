<?php
	require_once("IEvaluator.php");
	require_once("BaseEvaluator.php");
	require_once(dirname(__FILE__)."/../MatchAndRankService.php");
	require_once("WeightedUserVO.php");

	class IndustryEvaluator extends BaseEvaluator implements IEvaluator
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

				$data = $data[0];       // only one record for industry
				if(!$data)
					return null;

				$filterData = $data;
				
				$industry = $data->value;

				foreach($users as &$weightedUser)
				{
					$user = $weightedUser->user;
					$information = self::getInformation(MatchAndRankService::INDUSTRY, $user);

					if(!$information)
					{
						$weightedUser->disqualified = true;
						continue;
					}

					if($information->value != $industry)
					{
						if($weight == self::VERY_IMPORTANT)
							$weightedUser->disqualified = true;

						continue;
					}

					$weightedUser->weight += $weight;
					$weightedUser->explanations[MatchAndRankService::INDUSTRY] = $weight;

					// data matches
					$dataMatch = new FilterUserDataMatch();
					$dataMatch->name = MatchAndRankService::INDUSTRY;
					$dataMatch->possibleScore = $weight;
					$dataMatch->achievedScore = $weight;
					$dataMatch->filterDataId = $filterData->id;

					$weightedUser->dataMatches[] = $dataMatch;
				}
			}
			catch(Exception $e)
			{
				self::addError($e);
			}
		}
	}
?>