<?php
	require_once("IEvaluator.php");
	require_once("BaseEvaluator.php");
	require_once(dirname(__FILE__)."/../MatchAndRankService.php");
	require_once("WeightedUserVO.php");

	class QualificationsEvaluator extends BaseEvaluator implements IEvaluator
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

				$data = $data[0];               // only one qualifications
				if(!$data)
					return null;

				$filterData = $data;

				$targetQualifications = json_decode($data->value);

				foreach($users as &$weightedUser)
				{
					$user = $weightedUser->user;
					$information = self::getInformation(MatchAndRankService::QUALIFICATIONS, $user);

					if(!$information)
					{
						$weightedUser->disqualified = true;
						continue;
					}

					$totalAssignments = 0;

					$weightedUser->explanations[MatchAndRankService::QUALIFICATIONS] =
							array("max" => $weight, "achieved" => 0);

					$qualifications = json_decode($information->value);

					foreach($targetQualifications as $targetQualification)
					{
						foreach($qualifications as $qualification)
						{
							similar_text($qualification->qualification, $targetQualification->qualification, $similarity);

							if($similarity > 50 && $qualification->level == $targetQualification->level)
							{
								$totalAssignments += $weight / count($targetQualifications);
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
						$weightedUser->explanations[MatchAndRankService::QUALIFICATIONS] =
									array("max" => $weight, "achieved" => $totalAssignments);

						// data matches
						$dataMatch = new FilterUserDataMatch();
						$dataMatch->name = MatchAndRankService::QUALIFICATIONS;
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