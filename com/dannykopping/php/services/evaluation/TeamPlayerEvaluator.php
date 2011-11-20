<?php
	require_once("IEvaluator.php");
	require_once("BaseEvaluator.php");
	require_once(dirname(__FILE__)."/../MatchAndRankService.php");
	require_once("WeightedUserVO.php");

	class TeamPlayerEvaluator extends BaseEvaluator implements IEvaluator
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

				$data = $data[0];               // only one team player
				if(!$data)
					return null;

				$filterData = $data;
				
				$teamPlayer = json_decode($data->value);
				foreach($users as &$weightedUser)
				{
					$user = $weightedUser->user;
					$information = self::getInformation(MatchAndRankService::TEAM_PLAYER, $user);

					if(!$information)
					{
						$weightedUser->disqualified = true;
						continue;
					}

					$isTeamPlayer = json_decode($information->value);

					if($teamPlayer && $isTeamPlayer)
					{
						$weightedUser->weight += $weight;
						$weightedUser->explanations[MatchAndRankService::TEAM_PLAYER] = $weight;

						// data matches
						$dataMatch = new FilterUserDataMatch();
						$dataMatch->name = MatchAndRankService::TEAM_PLAYER;
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