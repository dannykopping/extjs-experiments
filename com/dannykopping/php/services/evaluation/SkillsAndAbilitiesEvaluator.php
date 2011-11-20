<?php
	require_once("IEvaluator.php");
	require_once("BaseEvaluator.php");
	require_once(dirname(__FILE__)."/../MatchAndRankService.php");
	require_once("WeightedUserVO.php");

	class SkillsAndAbilitiesEvaluator extends BaseEvaluator implements IEvaluator
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

				$data = $data[0];               // only one skills and abilities
				if(!$data)
					return null;

				$filterData = $data;

				$skillsAndAbilities = json_decode($data->value);

				$totalAssignments = 0;
				foreach($users as &$weightedUser)
				{
					$totalAssignments = 0;

					$weightedUser->explanations[MatchAndRankService::SKILLS_AND_ABILITIES] =
							array("max" => $weight, "achieved" => 0);

					$user = $weightedUser->user;
					$information = self::getInformation(MatchAndRankService::SKILLS_AND_ABILITIES, $user);

					if(!$information)
					{
						$weightedUser->disqualified = true;
						continue;
					}

					$userSkills = json_decode($information->value);

					foreach($skillsAndAbilities as $skillAbility)
					{
						foreach($userSkills as $userSkill)
						{
							similar_text($userSkill, $skillAbility, $similarity);

							if($similarity > 80)
							{
								$totalAssignments += $weight / count($skillsAndAbilities);
								break;
							}
						}
					}

					if($totalAssignments == 0)
					{
						if($weight == self::VERY_IMPORTANT)
						{
							$weightedUser->disqualified = true;
						}
					}
					else
					{
						$weightedUser->weight += $totalAssignments;
						$weightedUser->explanations[MatchAndRankService::SKILLS_AND_ABILITIES] =
									array("max" => $weight, "achieved" => $totalAssignments);

						// data matches
						$dataMatch = new FilterUserDataMatch();
						$dataMatch->name = MatchAndRankService::SKILLS_AND_ABILITIES;
						$dataMatch->possibleScore = $weight;
						$dataMatch->achievedScore = $totalAssignments;
						$dataMatch->filterDataId = $filterData->id;

						$weightedUser->dataMatches[] = $dataMatch;
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