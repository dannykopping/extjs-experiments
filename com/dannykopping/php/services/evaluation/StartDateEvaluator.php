<?php
	require_once("IEvaluator.php");
	require_once("BaseEvaluator.php");
	require_once(dirname(__FILE__)."/../MatchAndRankService.php");
	require_once("WeightedUserVO.php");

	class StartDateEvaluator extends BaseEvaluator implements IEvaluator
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

				$data = $data[0];               // only one start date
				if(!$data)
					return null;

				$filterData = $data;
				
				$targetDate = json_decode($data->value);
				$targetDate = $targetDate / 1000;           // flash keeps microseconds, so divide by 1000

				// immediate - $targetDate <= $currentDate
				// 30 days - $targetDate <= $currentDate + 30 days
				// calendar month - $targetDate <= next day of next month
				// start date - $targetDate <= $startDate

				foreach($users as &$weightedUser)
				{
					$user = $weightedUser->user;
					$information = self::getInformation(MatchAndRankService::START_DATE, $user);

					if(!$information)
					{
						$weightedUser->disqualified = true;
						continue;
					}

					$startDetails = json_decode($information->value);
					if(!$startDetails)
						continue;

					$match = false;

					$type = $startDetails->type;

					$startDate = $startDetails->startDate;
					$startDate = $startDate / 1000;

					switch($type)
					{
						case "Immediately":
						case "30 Days":
							$match = true;
							break;
						case "Calendar Month":
							if($targetDate <= strtotime("first day of next month"))
								$match = true;
							break;
						case "Start Date":
							if($startDate <= $targetDate)
								$match = true;
							break;
					}

					if($match)
					{
						$weightedUser->weight += $weight;
						$weightedUser->explanations[MatchAndRankService::START_DATE] = $weight;

						// data matches
						$dataMatch = new FilterUserDataMatch();
						$dataMatch->name = MatchAndRankService::START_DATE;
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