<?php
	require_once("IEvaluator.php");
	require_once("BaseEvaluator.php");
	require_once(dirname(__FILE__)."/../MatchAndRankService.php");
	require_once("WeightedUserVO.php");

	class ComputerLiteracyEvaluator extends BaseEvaluator implements IEvaluator
	{
		private static $grading = array("Basic" => 0, "Intermediate" => 1, "Advanced" => 2);

		public static function evaluate($data, $weight, &$users=null)
		{
			try
			{
				if(!$data || count($data) <= 0)
				{
					self::addError("No data");
					return null;
				}

				$data = $data[0];               // only one computer literacy
				if(!$data)
					return null;

				$filterData = $data;

				$computerPrograms = json_decode($data->value);

				$totalAssignment = 0;

				foreach($users as &$weightedUser)
				{
					$user = $weightedUser->user;
					$information = self::getInformation(MatchAndRankService::COMPUTER_LITERACY, $user);

					if(!$information)
					{
						if($weight == self::VERY_IMPORTANT)
						{
							$weightedUser->disqualified = true;
						}

						continue;
					}

					$information->value = @json_decode($information->value);

					if(!$information->value)
					{
						if($weight == self::VERY_IMPORTANT)
						{
							$weightedUser->disqualified = true;
						}

						continue;
					}

					$weightedUser->explanations[MatchAndRankService::COMPUTER_LITERACY] =
										array("max" => $weight, "achieved" => 0);

					foreach($computerPrograms as $program)
					{
						$targetSoftware = $program->skill;
						$targetProficiency = $program->proficiency;
						$targetGrade = (int) self::$grading[$targetProficiency];

						$bestNameMatch = 0;
						$bestName = "";

						foreach($information->value as $info)
						{
							$software = $info->skill;
							$proficiency = $info->proficiency;
							$grade = (int) self::$grading[$proficiency];

							// if proficiency level is equal to or greater than that required
							if($grade >= $targetGrade)
							{
								similar_text($software, $targetSoftware, $similarity);
								if($similarity > $bestNameMatch)
								{
									$bestNameMatch = $similarity;
									$bestName = $software;
								}
							}
						}

						if($bestNameMatch >= 45)
						{
							$totalAssignment += ($weight / count($computerPrograms));
							//NetDebug::trace("$bestName is closest to ".$targetSoftware);
						}
					}

					if($totalAssignment > 0)
					{
						if($totalAssignment > $weight)
							$totalAssignment = $weight;

						$weightedUser->weight += $totalAssignment;
						$weightedUser->explanations[MatchAndRankService::COMPUTER_LITERACY]["achieved"] = $totalAssignment;

						// data matches
						$dataMatch = new FilterUserDataMatch();
						$dataMatch->name = MatchAndRankService::COMPUTER_LITERACY;
						$dataMatch->possibleScore = $weight;
						$dataMatch->achievedScore = $totalAssignment;
						$dataMatch->FilterData = $filterData;

						$weightedUser->dataMatches[] = $dataMatch;
					}
					else
					{
						// no software packages' names came close
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