<?php
	require_once("IEvaluator.php");
	require_once("BaseEvaluator.php");
	require_once(dirname(__FILE__)."/../MatchAndRankService.php");
	require_once("WeightedUserVO.php");

	class DisabilityEvaluator extends BaseEvaluator implements IEvaluator
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

				$data = $data[0];               // only one disability
				if(!$data)
					return null;

				$filterData = $data;
				
				$disability = json_decode($data->value);

				foreach($users as &$weightedUser)
				{
					$user = $weightedUser->user;
					$information = self::getInformation(MatchAndRankService::DISABILITY, $user);

					if(!$information)
					{
						$weightedUser->disqualified = true;
						continue;
					}

					$details = json_decode($information->value);
					if(!$details)
						continue;

					$isDisability = $details->disabled;

					if($disability && $isDisability)
					{
						$weightedUser->weight += $weight;
						$weightedUser->explanations[MatchAndRankService::DISABILITY] = $weight;

						// data matches
						$dataMatch = new FilterUserDataMatch();
						$dataMatch->name = MatchAndRankService::DISABILITY;
						$dataMatch->possibleScore = $weight;
						$dataMatch->achievedScore = $weight;
						$dataMatch->FilterData = $filterData;

						$weightedUser->dataMatches[] = $dataMatch;
					}
					else
					{
						if($weight == self::VERY_IMPORTANT && $disability)       // only disqualify if disability is needed
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