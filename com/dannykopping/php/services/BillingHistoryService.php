<?php
	import("aerialframework.service.AbstractService");

	require_once("FinancialTransactionService.php");
	require_once("PurchaseService.php");
	require_once("UserService.php");

    require_once(ConfigXml::getInstance()->servicesPath."/../dummy/BillingDummy.php");

	class BillingHistoryService extends AbstractService
	{
		public $modelName = "BillingHistory";

		public function getHistory()
		{
            $loggedInUser = UserService::getLoggedInUser();

			$query = Doctrine_Query::create()
					->select("ft.*, bh.*")
					->from("FinancialTransaction ft, ft.billingHistories bh")
					->where("ft.userId = ".$loggedInUser->id)
					->orderBy("ft.updatedAt DESC, bh.updatedAt DESC");

			$query->setHydrationMode(Aerial_Core::HYDRATE_AMF_COLLECTION);
			return $query->execute();
		}

		public function getPending()
		{
            $loggedInUser = UserService::getLoggedInUser();

			$query = Doctrine_Query::create()
					->select("bh.*")
					->from("BillingHistory bh")
					->where("bh.userId = ".$loggedInUser->id)
					->groupBy("bh.financialTransactionId")
					->orderBy("bh.updatedAt DESC");

			$query->setHydrationMode(Aerial_Core::HYDRATE_AMF_COLLECTION);
			return $query->execute();
		}

		/**
         * Calculates the recruiter's account balance
         *
         * @throws Exception
         * @return BillingDummy
         */
		public function getCurrentBalance()
		{
            $loggedInUser = UserService::getLoggedInUser();

			$query = "SELECT SUM(cr.numCredits) as totalCredits
						FROM Credit cr
						WHERE cr.userId = ".$loggedInUser->id."
						ORDER BY cr.updatedAt";

			$query = $this->connection->getDbh()->query($query);
			$results = $query->fetchAll(PDO::FETCH_OBJ);

			$financialService = new FinancialTransactionService();
			$totalSpent = $financialService->getTotalSpent();

            $purchaseService = new PurchaseService();

			$numCredits = count($results) > 0 ? $results[0]->totalCredits : 0;
			$numPurchases = $purchaseService->getNumPurchases();

            $dummy = new BillingDummy();
            $dummy->numCredits = (int) $numCredits;
            $dummy->numPurchases = (int) $numPurchases;
            $dummy->totalSpent = (int) $totalSpent;

			return $dummy;
		}
	}
?>