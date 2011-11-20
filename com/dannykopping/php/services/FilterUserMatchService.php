<?php
	import("aerialframework.service.AbstractService");
    
	require_once("MatchAndRankService.php");
	require_once("GeographicInfoService.php");
	require_once("InputLookupService.php");
	require_once("ProfessionService.php");

    require_once("../utils/UserInformationParser.php");

	class FilterUserMatchService extends AbstractService
	{
		public $modelName = "FilterUserMatch";

		public function getFriendlyInformationForFilter($filterId)
		{
            $loggedInUser = UserService::getLoggedInUser();

			$query = Doctrine_Query::create()
					->select("f.*, u.id, u.firstName, u.lastName, ui.*, dataMatch.*, v.id, pr.*")
					->from("FilterUserMatch f, f.User u, u.userInformations ui,
					            f.filterUserDataMatches dataMatch, u.video v, u.preferences pr")
					->where("f.filterId = $filterId")
                    ->andWhere("u.id NOT IN (SELECT p.purchasedUserId FROM Purchase p WHERE p.filterId = ".$filterId.")")
                    ->orderBy("f.percentage DESC");

			$query->setHydrationMode(Aerial_Core::HYDRATE_AMF_COLLECTION);
			$results = $query->execute();
			if(count($results->source) == 0)
				return null;

			foreach($results->source as &$filterUserMatch)
			{
				$loggedInUser =& $filterUserMatch->User;
				if(!$loggedInUser || $loggedInUser->id == 0)
					continue;

				$informations =& $loggedInUser->userInformations;
				if(!$informations || count($informations->source) == 0)
					continue;

                $informations = UserInformationParser::getFriendlyInformation($informations);
			}

            //return $this->sortByVideoAvailability($results->source);
            return $results;
		}

        /**
         * Float the users with related videos to the top
         *
         * @param $filterUserMatches
         * @return array
         */
        private function sortByVideoAvailability($filterUserMatches)
        {
            $withVideo = array();
            $withoutVideo = array();

            foreach($filterUserMatches as &$filterUserMatch)
            {
				$user =& $filterUserMatch->User;
				if(!$user || $user->id == 0)
					continue;

                if(count($user->video) > 0)
                    array_push($withVideo, $filterUserMatch);
                else
                    array_push($withoutVideo, $filterUserMatch);
            }

            return array_merge($withVideo, $withoutVideo);
        }
	}
?>