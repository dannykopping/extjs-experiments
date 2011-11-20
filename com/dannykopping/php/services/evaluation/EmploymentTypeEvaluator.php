<?php
	require_once("IEvaluator.php");
	require_once("BaseEvaluator.php");
	require_once(dirname(__FILE__)."/../MatchAndRankService.php");
	require_once("WeightedUserVO.php");

	class EmploymentTypeEvaluator extends BaseEvaluator implements IEvaluator
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

				$data = $data[0];               // only one employment type
				if(!$data)
					return null;

				$filterData = $data;
				
				$targetType = json_decode($data->value);

				foreach($users as &$weightedUser)
				{
					$user = $weightedUser->user;
					$information = self::getInformation(MatchAndRankService::EMPLOYMENT_TYPE, $user);

					if(!$information)
					{
						if($weight == self::VERY_IMPORTANT)
						{
							$weightedUser->disqualified = true;
						}

						continue;
					}

					$employmentType = json_decode($information->value);
					$employmentType = $employmentType->type;

					if($targetType === $employmentType)
					{
						$weightedUser->weight += $weight;
						$weightedUser->explanations[MatchAndRankService::EMPLOYMENT_TYPE] = $weight;

						// data matches
						$dataMatch = new FilterUserDataMatch();
						$dataMatch->name = MatchAndRankService::EMPLOYMENT_TYPE;
						$dataMatch->possibleScore = $weight;
						$dataMatch->achievedScore = $weight;
						$dataMatch->FilterData = $filterData;

						$weightedUser->dataMatches[] = $dataMatch;
					}
					else
					{
						if($weight == self::VERY_IMPORTANT)
                        {
                            $weightedUser->disqualified = true;
                        }
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