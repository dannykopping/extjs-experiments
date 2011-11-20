<?php
	require_once("IEvaluator.php");
	require_once("BaseEvaluator.php");
	require_once(dirname(__FILE__)."/../MatchAndRankService.php");
	require_once("WeightedUserVO.php");

	class LanguagesEvaluator extends BaseEvaluator implements IEvaluator
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

				$data = $data[0];               // only one experience
				if(!$data)
					return null;

				$filterData = $data;

				$data->value = json_decode($data->value);

				$advantageousLanguages = $data->value->advantageous;
				$primaryLanguages = $data->value->primary;

				foreach($users as &$weightedUser)
				{
					$user = $weightedUser->user;
					$information = self::getInformation(MatchAndRankService::LANGUAGES, $user);

					if(!$information)
					{
						$weightedUser->disqualified = true;
						continue;
					}

					$information->value = json_decode($information->value);

					$primaryCount = 0;
					$advantageousCount = 0;

					$languages = $information->value;
					foreach($languages as $language)
					{
						$primarySearch = array_search($language, $primaryLanguages, true);
						$advantageousSearch = array_search($language, $advantageousLanguages, true);

						if($primarySearch !== FALSE && $primarySearch >= 0)
						{
							$primaryCount++;
						}
						else if($advantageousSearch !== FALSE && $advantageousSearch >= 0)
						{
							$advantageousCount++;
						}
					}

					if($primaryCount == 0 && $advantageousCount == 0)
					{
						if($weight == self::VERY_IMPORTANT)
						{
							$weightedUser->disqualified = true;
						}
					}

					$total = 0;

					if(count($primaryLanguages) > 0)
					{
						$primaryValue = ($weight * 0.7) / count($primaryLanguages);             // 70% for primary languages
					}
					else
					{
						$primaryValue = 0.7 * $weight;
						$total += $primaryValue;
					}

					if(count($advantageousLanguages) > 0)
					{
						$advantageousValue = ($weight * 0.3) / count($advantageousLanguages);   // 30% for advantageous languages
					}
					else
					{
						$advantageousValue = 0.3 * $weight;
						$total += $advantageousValue;
					}

					$total += ($primaryValue * $primaryCount) + ($advantageousValue * $advantageousCount);

					if($total > $weight)
						$total = $weight;

					$weightedUser->weight += $total;
					$weightedUser->explanations[MatchAndRankService::LANGUAGES] =
							array("primary" => $primaryValue * $primaryCount,
									"advantageous" => $advantageousValue * $advantageousCount);

					// data matches
					$dataMatch = new FilterUserDataMatch();
					$dataMatch->name = MatchAndRankService::LANGUAGES;
					$dataMatch->possibleScore = $weight;
					$dataMatch->achievedScore = $total;
					$dataMatch->filterDataId = $filterData->id;

					$weightedUser->dataMatches[] = $dataMatch;
				}
			}
			catch(Exception $e)
			{
				self::addError($e);
			}
		}
	}
?>