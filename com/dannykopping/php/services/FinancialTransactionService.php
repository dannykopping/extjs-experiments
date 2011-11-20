<?php
	import("aerialframework.service.AbstractService");

	require_once(ConfigXml::getInstance()->servicesPath."/../utils/Email.php");
	require_once("BillingHistoryService.php");
	require_once("CreditService.php");
	require_once("UserService.php");

	class FinancialTransactionService extends AbstractService
	{
		public $modelName = "FinancialTransaction";


		/**
		 * Returns the transactions that have either been cancelled or not (depending on the "cancelled" param)
		 *
		 * @param bool $completed
		 * @param bool $cancelled
		 * @param null $searchTerm
		 *
		 * @return Doctrine_Collection
		 */
		public function getTransactions($completed=true, $cancelled=true, $searchTerm=null)
		{
			$query = Doctrine_Query::create()
					->select("ftx.*, u.id, u.firstName, u.lastName, u.email, bhx.*")
					->from("FinancialTransaction ftx, ftx.User u, ftx.billingHistories bhx");

			$subQueryElements = array();
			if(!$completed)
				$subQueryElements[] = "'".BillingHistory::TYPE_PAYMENT."'";

			if(!$cancelled)
				$subQueryElements[] = "'".BillingHistory::TYPE_CANCELLED."'";

			if(count($subQueryElements) > 0)
				$subQuery = " AND (bh.type = ".implode(" OR bh.type = ", $subQueryElements).")";

			if($subQuery)
				$query->addWhere("(SELECT COUNT(bh.id) FROM FinancialTransaction ft, BillingHistory bh WHERE
								bh.financialTransactionId = ftx.id".$subQuery.") < 1");

			if($searchTerm != null)
			{
				$query->addWhere("ftx.reference LIKE '".$searchTerm."%'");
			}

			$query->orderBy("ftx.updatedAt DESC");

			$query->setHydrationMode(Aerial_Core::HYDRATE_AMF_COLLECTION);
			$results = $query->execute();

			// figure out which records have an associated cancellation record or a completion record
			foreach($results->source as &$result)
			{
				$numCancelled = $this->countHistoriesOfTypes($result->id, array(BillingHistory::TYPE_CANCELLED));
				if($numCancelled >= 1)
					$result->isCancelled = true;


				$numPayments = $this->countHistoriesOfTypes($result->id, array(BillingHistory::TYPE_PAYMENT));
				if($numPayments >= 1)
					$result->isCompleted = true;
			}

			return $results;
		}

		public function addPendingTransaction($object)
		{
            $loggedInUser = UserService::getLoggedInUser();

			// cast the incoming data as an object
			$object = (object) $object;

			$object->amount = $this->getAmountForCredits($object->numCredits);
            $object->userId = $loggedInUser->id;

			// generate a new reference
			if(!$object->reference || strlen($object->reference) < 12)
				$object->reference = $this->generateReference($loggedInUser->id);

			if($this->save((array) $object))
			{
                $message = $this->newTransactionTemplate;
                $message = str_replace("{{AMOUNT}}", number_format((float) $object->amount, 2), $message);
                $message = str_replace("{{REFERENCE}}", $object->reference, $message);

				return $this->sendConfirmation($loggedInUser->email, $message);
			}
		}

        /**
         * Generates a unique transaction reference
         *
         * @return string
         */
		public function generateReference()
		{
            $loggedInUser = UserService::getLoggedInUser();

			// "X" is used as a separator between the companyId and the random padding
			$reference = $loggedInUser->id."X";
			$reference = $this->padWithRandom($reference, 12 - strlen($reference));

			// check if no such reference exists
			$query = Doctrine_Query::create()
						->select("ft.id")
						->from("FinancialTransaction ft")
						->where("ft.reference = '$reference'")
						->limit(1);

			$results = $query->execute();
			if(count($results) > 0)                                 // a match was found
				return $this->generateReference();                  // run the function again and return the value

			return $reference;                                      // no match was found, return created reference
		}

		/**
		 * Pad a string with random hexadecimal values
		 *
		 * @param $input
		 * @param int $length
		 * @return string
		 */
		private function padWithRandom($input, $length=12)
		{
			for($i = 0; $i < $length; $i++)
			{
				$random = mt_rand(0, 15);
				$char = dechex($random);

				$input .= $char;
			}

			return strtoupper($input);
		}


		/**
		 *
		 * -------------------------------------------
	 *                  CREDITS
		 * -------------------------------------------
		 *
		 * Add in credits when transaction is completed, subtract when reversed or cancelled
		 * ONLY ALLOW ONE OF EACH!!!
		 *
		 */



		/**
		 * Completes a transaction and add a "payment" billing history entry
		 *
		 * @throws Exception
		 * @param $object
		 * @return null
		 */
		public function completeTransaction($object)
		{
            $loggedInUser = UserService::getLoggedInUser();
			$transaction = $this->save($object, true);

			if(!$transaction)
			{
				throw new Exception("Transaction could not be completed.");
				return;
			}

			// delete the existing credit records associated with this transaction
			$transaction->credits->delete();

			$billingEntry = new BillingHistory();
			$billingEntry->type = BillingHistory::TYPE_PAYMENT;
			$billingEntry->comment = "Payment received.";
			$billingEntry->numCredits = $transaction->numCredits;

			$billingEntry->userId = $transaction->userId;
			$billingEntry->financialTransactionId = $transaction->id;

			$creditEntry = new Credit();
			$creditEntry->numCredits = $transaction->numCredits;
			$creditEntry->financialTransactionId = $transaction->id;
			$creditEntry->userId = $transaction->userId;

			$billingService = new BillingHistoryService();
			$creditService = new CreditService();

			if($billingService->save($billingEntry, false, false) && $creditService->save($creditEntry, false, false))
			{
				$user = new User();
				$user = $user->table->find($transaction->userId);
				if(!$user)
					return false;

                $message = $this->completedTransactionTemplate;
                $message = str_replace("{{AMOUNT}}", number_format((float) $transaction->amount, 2), $message);

				return $this->sendConfirmation($user->email, $message);
			}
            else
				throw new Exception("Transaction could not be completed.");
		}

        /**
         * Get email authentication and configuration details
         *
         * @return array
         */
        private function getEmailDetails()
        {
            $config['protocol'] = (string) ConfigXml::getInstance()->config->email->{'protocol'};
			$config['smtp_host'] = (string) ConfigXml::getInstance()->config->email->{'smtp-host'};
			$config['smtp_user'] = (string) ConfigXml::getInstance()->config->email->{'smtp-user'};
			$config['smtp_pass'] = (string) ConfigXml::getInstance()->config->email->{'smtp-pass'};
			$config['smtp_port'] = (string) ConfigXml::getInstance()->config->email->{'smtp-port'};

			$config['charset'] = 'iso-8859-1';
			$config['wordwrap'] = TRUE;
            $config['mailtype'] = 'html';

            return $config;
        }

		/**
		 * Sends a confirmation email
		 *
		 * @param $address
		 * @param $message
		 * @return boolean
		 */
		public function sendConfirmation($address, $message)
		{
			$config = $this->getEmailDetails();

			$email = new Email($config);
			
			$email->from('accounts@getthejob.co.za', 'GetTheJob.co.za - Accounts Department');
			$email->to($address);
			
			$email->subject('GetTheJob.co.za - Transaction Notification');
			$email->message($message);

			return $email->send();
		}

		/**
		 * Check if the FinancialTransaction record has a corresponding BillingHistory record of certain types
		 *
		 * (example: FinancialTransaction records can only have ONE "payment" record associated with it)
		 *
		 * @param $transactionId
		 * @param $types
		 *
		 * @return Doctrine_Collection
		 *
		 */
		private function getHistoriesOfTypes($transactionId, $types)
		{
			$query = Doctrine_Query::create()
					->select("bh.id")
					->from("BillingHistory bh")
					->where("bh.financialTransactionId = $transactionId");

			// problematic - need to group all ORs because the "WHERE financialTransactionId = x" is essential
			foreach($types as $type)
					$query->orWhere("bh.type = '$type'");

			return $query->execute();
		}

		/**
		 * Check if the FinancialTransaction record has a corresponding BillingHistory record of certain types
		 *
		 * (example: FinancialTransaction records can only have ONE "payment" record associated with it)
		 *
		 * @param $transactionId
		 * @param $types
		 *
		 * @return Doctrine_Collection
		 *
		 */
		public function countHistoriesOfTypes($transactionId, $types)
		{
			$query = Doctrine_Query::create()
					->select("COUNT(bh.id)")
					->from("BillingHistory bh")
					->where("bh.financialTransactionId = $transactionId");

			foreach($types as $type)
					$query->andWhere("bh.type = '$type'");

			$query->setHydrationMode(Doctrine_Core::HYDRATE_SINGLE_SCALAR);
			return (int) $query->execute();
		}

		/**
		 * Calculates the cost of a number of credits according to the sliding scale of pricing
		 *
		 * 0    -   99  => 150
		 * 100  -   199 => 130
		 * 200  -   299 => 110
		 * 300+         => 90
		 *
		 * @param $credits
		 * @return Rand value of credits
		 */
		public function getAmountForCredits($credits)
		{
			// 0 - 99 cost R150 ea.
			$factor = 150;

			if($credits >= 100 && $credits <= 199)
			{
				$factor = 140;
			}
			else if($credits >= 200 && $credits <= 299)
			{
				$factor = 130;
			}
			else if($credits >= 300 && $credits <= 399)
			{
				$factor = 120;
			}
			else if($credits >= 400 && $credits <= 499)
			{
				$factor = 110;
			}
			else if($credits >= 500 && $credits <= 599)
			{
				$factor = 100;
			}
			else if($credits >= 600)
			{
				$factor = 90;
			}

			return $credits * $factor;
		}

        public function getTotalSpent()
        {
            $loggedInUser = UserService::getLoggedInUser();
            $userId = $loggedInUser->id;

            $query = "SELECT * FROM (SELECT bh.* FROM BillingHistory bh
                        INNER JOIN FinancialTransaction ft ON (bh.financialTransactionId = ft.id)
                        WHERE bh.userId = $userId
                        ORDER BY bh.updatedAt DESC) AS subQuery
                        GROUP BY financialTransactionId";

            $billingHistory = $this->connection->getDbh()->query($query);
            $billingHistory = $billingHistory->fetchAll(PDO::FETCH_OBJ);

            $total = 0;
            foreach($billingHistory as $billingHistoryItem)
            {
                $type = $billingHistoryItem->type;

                // don't include reversals, cancellations or credits
                if($type == BillingHistory::TYPE_REVERSAL || $type == BillingHistory::TYPE_CANCELLED || 
                        $type == BillingHistory::TYPE_CREDIT)
                    continue;

                $total += $this->getAmountForCredits($billingHistoryItem->numCredits);
            }

            return $total;
        }

		/**
		 * Reverses a transaction and add a "reversal" billing history entry
		 *
		 * @throws Exception
		 * @param $object
		 * @param $comment
		 * @return null
		 */
		public function reverseTransaction($object, $comment)
		{
			$transaction = $this->save($object, true);

			if(!$transaction)
			{
				throw new Exception("Transaction could not be reversed.");
				return;
			}

			// delete the existing credit records associated with this transaction
			$transaction->credits->delete();

			$billingEntry = new BillingHistory();
			$billingEntry->type = BillingHistory::TYPE_REVERSAL;
			$billingEntry->comment = $comment;

			$billingEntry->userId = $transaction->userId;
			$billingEntry->financialTransactionId = $transaction->id;

			$service = new BillingHistoryService();
			if($service->save($billingEntry, false, false))
			{
				$user = new User();
				$user = $user->table->find($transaction->userId);
				if(!$user)
					return false;

                $message = $this->cancelledTransactionTemplate;
                $message = str_replace("{{REASON}}", str_replace("\n", "<br/>", $comment), $message);
                $message = str_replace("{{AMOUNT}}", number_format((float) $transaction->amount, 2), $message);

				return $this->sendConfirmation($user->email, $message);
			}
		}

		/**
		 * Cancels a transaction and add a "cancelled" billing history entry
		 *
		 * @throws Exception
		 * @param $object
		 * @param $comment
		 * @return null
		 */
		public function cancelTransaction($object, $comment)
		{
			$transaction = $this->save($object, true);

			if(!$transaction)
			{
				throw new Exception("Transaction could not be cancelled.");
				return;
			}

			$billingEntry = new BillingHistory();
			$billingEntry->type = BillingHistory::TYPE_CANCELLED;
			$billingEntry->comment = $comment;

			$billingEntry->userId = $transaction->userId;
			$billingEntry->financialTransactionId = $transaction->id;

			$service = new BillingHistoryService();
			if($service->save($billingEntry, false, false))
			{
				$user = new User();
				$user = $user->table->find($transaction->userId);
				if(!$user)
					return false;

                $message = $this->cancelledTransactionTemplate;
                $message = str_replace("{{REASON}}", str_replace("\n", "<br/>", $comment), $message);
                $message = str_replace("{{AMOUNT}}", number_format((float) $transaction->amount, 2), $message);

				return $this->sendConfirmation($user->email, $message);
			}
		}

        private $newTransactionTemplate = <<<EOD
<strong>Thank you for your business!</strong>
<br/>
<p>Your purchase of R{{AMOUNT}} has been added for processing.</p>
<p><strong>Payment Details</strong></p>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
<tr>
  <td width="150px"><div align="left"><strong>Bank</strong></div></td>
  <td>ABSA</td>
</tr>
<tr>
  <td><div align="left"><strong>Branch</strong></div></td>
  <td>Rosebank Central Branch</td>
</tr>
<tr>
  <td><div align="left"><strong>Branch Code</strong></div></td>
  <td>632005</td>
</tr>
<tr>
  <td><div align="left"><strong>Account Number</strong></div></td>
  <td>0000-0040-7838-0836</td>
</tr>
<tr>
  <td><div align="left"><strong>Account Holder</strong></div></td>
  <td>Get The Job (Pty) Ltd</td>
</tr>
<tr>
  <td height="34"><div align="left"><strong>Your Reference</strong></div></td>
  <td><strong>{{REFERENCE}}</strong></td>
</tr>
</table>
<p>To access your credits immediately to purchase InfoPacks, please email proof of payment to <a href="mailto:admin@getthejob.co.za">admin@getthejob.co.za</a> with your reference number (<strong>{{REFERENCE}}</strong>)</p>
EOD;

        private $completedTransactionTemplate = <<<EOD
<p>Your purchase of R{{AMOUNT}} has been successfully processed. Your new credits are now available for use.</p>
<p>You can view your billing history by navigating to the <strong><a href="http://getthejob.co.za/app/#/recruiter/billing">Billing</a></strong> page.</p>
<p><strong>Thank you for your business!</strong> </p>
EOD;

        private $cancelledTransactionTemplate = <<<EOD
<p>Your purchase of R{{AMOUNT}} has been cancelled.</p>
<p>Reason given:<br>
<strong>&quot;{{REASON}}&quot;</strong></p>
<p>You can view your billing history by navigating to the <strong><a href="http://getthejob.co.za/app/#/recruiter/billing">Billing</a></strong> page.</p>
<p><strong>Thank you for your business!</strong> </p>
EOD;

        private $reversedTransactionTemplate = <<<EOD
<p>Your purchase of R{{AMOUNT}} has been reversed.</p>
<p>Reason given:<br>
<strong>&quot;{{REASON}}&quot;</strong></p>
<p>You can view your billing history by navigating to the <strong><a href="http://getthejob.co.za/app/#/recruiter/billing">Billing</a></strong> page.</p>
<p><strong>Thank you for your business!</strong> </p>
EOD;
	}
?>