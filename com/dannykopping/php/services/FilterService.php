<?php
	import("aerialframework.service.AbstractService");
	require_once("MatchAndRankService.php");
	require_once("GeographicInfoService.php");
	require_once("InputLookupService.php");
	require_once("ProfessionService.php");
	require_once("MatchAndRankService.php");
	require_once("UserService.php");

	class FilterService extends AbstractService
	{
		public $modelName = "Filter";

		public function save($object, $returnCompleteObject = false)
		{
			if(is_undefined($object["id"]) || $object["id"] <= 0)                // new filter
				return parent::save($object, $returnCompleteObject);
			else
			{
				$criterion = new FilterCriterion();
				$data = new FilterData();

                die("HI");

				// remove existing criteria
				$existing = $criterion->table->findBy("filterId", $object["id"]);
				foreach($existing as $cr)
				{
					@$cr->filterDatas->delete();
				}

				@$existing->delete();
	
				return parent::save($object, $returnCompleteObject);
			}
		}

        public function getFilters()
        {
            $user = UserService::getLoggedInUser(true);

            $query = Doctrine_Query::create()
                    ->select("f.*")
                    ->from("Filter f")
                    ->where("f.userId = ".$user->id)
                    ->andWhere("f.active = true");

            $query->setHydrationMode(Aerial_Core::HYDRATE_AMF_COLLECTION);
            return $query->execute();
        }

		private function getSource($obj)
		{
			if(!$obj)
				return null;

			if(count($obj->source) <= 0)
				return array();

			return $obj->source;
		}

		public function getFriendlyInformation($id)
		{
			$filter = $this->getSource($this->find(null, 1, null, null,
			                      array("Filter(*, id=$id).filterCriterions(*)",
										"filterCriterions(*).filterDatas(*)")));

			if(!$filter)
				return null;

			$criteria = $this->getSource($filter[0]->filterCriterions);

			foreach($criteria as &$criterion)
			{
				$type = $criterion->name;

				$data = $this->getSource($criterion->filterDatas);

				foreach($data as $filterData)
				{
					switch($type)
					{
						case MatchAndRankService::LOCATION:
							if($filterData->lookupId > 0)
							{
								$geoService = new GeographicInfoService();
								$location = $geoService->table->find($filterData->lookupId);
								if(!$location)
									continue;
	
								$relocate = json_decode($filterData->value);

								$filterData->value = new stdClass();
	
								$location = $location->toArray();
								unset($location["_explicitType"]);
	
								$filterData->value->location = $location;
								$filterData->value->relocate = $relocate;

								$filterData->value = json_encode($filterData->value);
							}
							break;
						case MatchAndRankService::SKILLS_AND_ABILITIES:
							if($filterData->lookupId > 0)
							{
								$inputLookupService = new InputLookupService();
								$industry = $inputLookupService->table->find($filterData->lookupId);
								if(!$industry)
									continue;
	
								$filterData->value = json_encode($industry->value);
							}
							break;
						case MatchAndRankService::JOB_TITLE:
							$jobTitles = json_decode($filterData->value);
                            $professions = array();

                            foreach($jobTitles as $jobTitle)
                            {
                                $professions[] = array("id" => 0, "name" => $jobTitle);
                            }

                            $filterData->value = json_encode($professions);
							break;
					}
				}
			}

			return $filter;
		}

        public function getPurchasesByFilters()
        {
            $loggedInUser = UserService::getLoggedInUser();

            $query = Doctrine_Query::create()
                    ->select("p.*, f.*, pu.firstName, pu.lastName")
                    ->from("Filter f, f.User u, f.purchases p, p.PurchasedUser pu")
                    ->where("u.id = ".$loggedInUser->id);

            $query->setHydrationMode(Aerial_Core::HYDRATE_AMF_COLLECTION);
            return $query->execute();
        }
	}
?>