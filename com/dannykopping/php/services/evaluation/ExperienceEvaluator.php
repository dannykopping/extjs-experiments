<?php
	require_once("IEvaluator.php");
	require_once("BaseEvaluator.php");
	require_once(dirname(__FILE__)."/../MatchAndRankService.php");
	require_once("WeightedUserVO.php");

	class ExperienceEvaluator extends BaseEvaluator implements IEvaluator
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

				$data = $data[0];               // only one experience
				if(!$data)
					return null;

				$filterData = $data;
				$data->value = json_decode($data->value);

				if(!$data->value)
					return;

				$relevantMonths = $data->value->relevant * 12;
				$workforceMonths = $data->value->workforce * 12;

				foreach($users as &$weightedUser)
				{
					$user = $weightedUser->user;
					$information = self::getInformation(MatchAndRankService::EXPERIENCE, $user);

					if(!$information)
					{
						$weightedUser->disqualified = true;
						continue;
					}

					$information->value = json_decode($information->value);

					if(!$information->value)
						continue;

					$relevant = $information->value->relevant->months + ($information->value->relevant->years * 12);
					$workforce = $information->value->workforce->months + ($information->value->workforce->years * 12);

					// allow for 10% variance
					if($relevant >= ($relevantMonths - ($relevantMonths / 10)) && $workforce >= ($workforceMonths - ($workforceMonths / 10)))
					{
						$weightedUser->weight += $weight;
						$weightedUser->explanations[MatchAndRankService::EXPERIENCE] = $weight;

						// data matches
						$dataMatch = new FilterUserDataMatch();
						$dataMatch->name = MatchAndRankService::EXPERIENCE;
						$dataMatch->possibleScore = $weight;
						$dataMatch->achievedScore = $weight;
						$dataMatch->filterDataId = $filterData->id;

						$weightedUser->dataMatches[] = $dataMatch;
					}
					else
					{
						if($weight == self::VERY_IMPORTANT)
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