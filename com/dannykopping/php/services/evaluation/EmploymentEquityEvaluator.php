<?php
	require_once("IEvaluator.php");
	require_once("BaseEvaluator.php");
	require_once(dirname(__FILE__)."/../MatchAndRankService.php");
	require_once("WeightedUserVO.php");

	class EmploymentEquityEvaluator extends BaseEvaluator implements IEvaluator
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

				$data = $data[0];               // only one employment equity
				$employmentEquity = json_decode($data->value);
				foreach($users as &$weightedUser)
				{
					$user = $weightedUser->user;
					$information = self::getInformation(MatchAndRankService::EMPLOYMENT_EQUITY, $user);

					if(!$information)
					{
						$weightedUser->disqualified = true;
						continue;
					}

					$isEmploymentEquity = json_decode($information->value);

					if($employmentEquity && $isEmploymentEquity)
					{
						$weightedUser->weight += $weight;
						$weightedUser->explanations[MatchAndRankService::EMPLOYMENT_EQUITY] = $weight;
					}
					else
					{
						if($weight == self::VERY_IMPORTANT && $employmentEquity)       // only disqualify if employment equity is needed
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