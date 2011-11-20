<?php
	require_once("IEvaluator.php");
	require_once("BaseEvaluator.php");
	require_once(dirname(__FILE__)."/../MatchAndRankService.php");
	require_once(dirname(__FILE__)."/../GeographicInfoService.php");
	require_once("WeightedUserVO.php");

	class LocationEvaluator extends BaseEvaluator implements IEvaluator
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

				$data = $data[0];               // only one location
				if(!$data)
					return null;

				$filterData = $data;

				$findRelocate = $data->value == "true";
				$targetId = (int) $data->lookupId;

				foreach($users as &$weightedUser)
				{
					$user = $weightedUser->user;
					$information = self::getInformation(MatchAndRankService::LOCATION, $user);

					if(!$information)
					{
						$weightedUser->disqualified = true;
						continue;
					}

					$locationData = json_decode($information->value);
					$distance = $locationData->distance;
					$willingToRelocate = $locationData->relocate;

					// if willing to relocate, don't search
					if($willingToRelocate && $findRelocate)
					{
						$weightedUser->weight += $weight;
						$weightedUser->explanations[MatchAndRankService::LOCATION] = $weight;

						// data matches
						$dataMatch = new FilterUserDataMatch();
						$dataMatch->name = MatchAndRankService::LOCATION;
						$dataMatch->possibleScore = $weight;
						$dataMatch->achievedScore = $weight;
						$dataMatch->filterDataId = $filterData->id;

						$weightedUser->dataMatches[] = $dataMatch;
						continue;
					}
					else
					{
						$geoService = new GeographicInfoService();
						$inRange = $geoService->isInRange($information->lookupId, $targetId, $distance);

						if($inRange)
						{
							$weightedUser->weight += $weight;
							$weightedUser->explanations[MatchAndRankService::LOCATION] = $weight;

							// data matches
							$dataMatch = new FilterUserDataMatch();
							$dataMatch->name = MatchAndRankService::LOCATION;
							$dataMatch->possibleScore = $weight;
							$dataMatch->achievedScore = $weight;
							$dataMatch->filterDataId = $filterData->id;

							$weightedUser->dataMatches[] = $dataMatch;
							continue;
						}
						else
						{
							if($weight == self::VERY_IMPORTANT)
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