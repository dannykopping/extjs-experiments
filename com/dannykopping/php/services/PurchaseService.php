<?php
	import("aerialframework.service.AbstractService");

    require_once("BillingHistoryService.php");
    require_once("UserService.php");

	class PurchaseService extends AbstractService
	{
		public $modelName = "Purchase";

        /**
         * Adds new purchase record indicating that a purchase has been made
         *
         * @throws Exception
         * @param $purchasedUserId
         * @param $filterId
         *
         * @return User
         */
        public function addNewPurchase($purchasedUserId, $filterId)
        {
            $user = UserService::getLoggedInUser();

            // first check to see if the Company has a sufficient credits
            if(!$this->userHasEnoughCredits())
                throw new Exception("Insufficient credits");

            $newPurchase = new Purchase();
            $newPurchase->purchasedUserId = $purchasedUserId;
            $newPurchase->filterId = $filterId;

            // remove the associated FilterUserMatch record
            $filterUserMatch = $this->getFilterUserMatch($purchasedUserId, $filterId);
            if($filterUserMatch)
                @$filterUserMatch->delete();

            $user = new User();
            $user = $user->table->find($purchasedUserId);

            if(!$user)
                throw new Exception("Invalid user");

            unset($user->password);

            if(parent::save($newPurchase, false, false))
                return $user;
            else
                throw new Exception("Could not complete the transaction.");
        }

        /**
         * Finds the associated FilterUserMatch record
         *
         * @param $purchasedUserId
         * @param $filterId
         *
         * @return FilterUserMatch
         */
        private function getFilterUserMatch($purchasedUserId, $filterId)
        {
            $query = Doctrine_Query::create()
                    ->select("fum.*")
                    ->from("FilterUserMatch fum")
                    ->where("fum.userId = $purchasedUserId")
                    ->andWhere("fum.filterId = $filterId")
                    ->limit(1);

            $results = $query->execute();
            if(count($results) == 0)
                return null;

            return $results[0];
        }

        /**
         * Checks to see if the company has sufficient credits to complete this transaction
         *
         * @return void
         */
        private function userHasEnoughCredits()
        {
            $user = UserService::getLoggedInUser();

            $billingService = new BillingHistoryService();
            $balanceInfo = $billingService->getCurrentBalance();

            $numCredits = $balanceInfo->numCredits;
            $numPurchases = $balanceInfo->numPurchases;

            return $numPurchases < $numCredits;
        }

        /**
         * Returns the purchased User's related job spec id
         *
         * @param $userId
         * @return int
         */
        public function getPurchasedUserFilter($userId)
        {
            $loggedInUser = UserService::getLoggedInUser();

            $query = Doctrine_Query::create()
                    ->select("p.filterId")
                    ->from("Purchase p, p.Filter f")
                    ->where("p.purchasedUserId = $userId")
                    ->andWhere("f.userId = ".$loggedInUser->id)
                    ->limit(1);

            $query->setHydrationMode(Aerial_Core::HYDRATE_AMF_COLLECTION);
            $results = $query->execute();

            if(!$results || count($results) == 0)
                throw new Exception("No filter id");

            $matchingFilter = $results[0];
            if(!$matchingFilter || count($matchingFilter) == 0)
                throw new Exception("No filter id");

            return (int) $matchingFilter->filterId;
        }

        public function getNumPurchases()
        {
            $loggedInUser = UserService::getLoggedInUser();

            $query = Doctrine_Query::create()
                    ->select("p.purchasedUserId")
                    ->from("Purchase p")
                    ->innerJoin("p.Filter f", "f.id = p.filterId")
                    ->where("f.userId = ".$loggedInUser->id);

            return $query->count();
        }
    }
?>