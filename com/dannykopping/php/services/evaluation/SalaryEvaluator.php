<?php
	require_once("IEvaluator.php");
	require_once("BaseEvaluator.php");
	require_once(dirname(__FILE__)."/../MatchAndRankService.php");
	require_once(dirname(__FILE__)."/../GeographicInfoService.php");
	require_once("WeightedUserVO.php");

	class SalaryEvaluator extends BaseEvaluator implements IEvaluator
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

				$data = $data[0];               // only one salary
				if(!$data)
					return null;

				$filterData = $data;
				
				$maximum = (int) json_decode($data->value);

				foreach($users as &$weightedUser)
				{
					$user = $weightedUser->user;
					$information = self::getInformation(MatchAndRankService::SALARY, $user);

					if(!$information)
					{
						$weightedUser->disqualified = true;
						continue;
					}

					$details = json_decode($information->value);
					$salary = $details->salary;
					$netSalary = $details->netSalary;

					if($salary <= $maximum || $netSalary <= $maximum)
					{
						$weightedUser->weight += $weight;
						$weightedUser->explanations[MatchAndRankService::SALARY] = $weight;

						// data matches
						$dataMatch = new FilterUserDataMatch();
						$dataMatch->name = MatchAndRankService::SALARY;
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