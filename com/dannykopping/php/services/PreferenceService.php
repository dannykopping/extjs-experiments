<?php
	import("aerialframework.service.AbstractService");

    require_once("UserService.php");

	class PreferenceService extends AbstractService
	{
		public $modelName = "Preference";

        public function getCompanyExclusions($userId)
        {
            $loggedInUser = UserService::getLoggedInUser();

            $service = new UserService();

            if(!$service->checkOwnershipOfCV($userId))
                throw new Exception("Not owned");

            $query = Doctrine_Query::create()
                    ->select("p.value")
                    ->from("Preference p")
                    ->where("p.userId = $userId");

            $results = $query->execute();
            if(!$results || count($results) == 0)
                return null;

            $preference = $results[0];
            if(!$preference || count($preference) == 0)
                return null;

            return $preference->value;
        }
	}
?>