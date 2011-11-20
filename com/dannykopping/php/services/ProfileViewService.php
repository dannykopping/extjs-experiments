<?php
	import("aerialframework.service.AbstractService");

    require_once("UserService.php");

	class ProfileViewService extends AbstractService
	{
		public $modelName = "ProfileView";

        /**
         * Captures a recruiter's viewing of a jobseeker's profile
         *
         * @throws Exception
         * @param $userId
         * @param $filterId
         *
         * @return boolean
         */
        public function logProfileView($userId, $filterId)
        {
            $loggedInUser = UserService::getLoggedInUser();

            // check to see if a record already exists
            $query = Doctrine_Query::create()
                    ->select("pv.*")
                    ->from("ProfileView pv")
                    ->where("pv.userId = $userId")
                    ->andWhere("pv.filterId = $filterId")
                    ->limit(1);

            $results = $query->execute();

            if(count($results) > 0)
            {
                // no record
                $profileViewRecord = $results[0];
                $profileViewRecord->count = ((int) $profileViewRecord->count) + 1;
            }
            else
            {
                // record found
                $profileViewRecord = new ProfileView();
                $profileViewRecord->count = 1;
                $profileViewRecord->userId = $userId;
                $profileViewRecord->filterId = $filterId;
            }

            return parent::save($profileViewRecord, false, false) !== null;
        }
	}
?>