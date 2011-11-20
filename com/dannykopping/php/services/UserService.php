<?php
	import("aerialframework.service.AbstractService");

    require_once(ConfigXml::getInstance()->servicesPath."/../utils/UserInformationParser.php");
    require_once(ConfigXml::getInstance()->servicesPath."/DashboardService.php");
    require_once(ConfigXml::getInstance()->servicesPath."/FinancialTransactionService.php");

    require_once(ConfigXml::getInstance()->servicesPath."/../email/JobseekerRegistrationEmailer.php");
    require_once(ConfigXml::getInstance()->servicesPath."/../email/RecruiterRegistrationEmailer.php");
    require_once(ConfigXml::getInstance()->servicesPath."/../email/PasswordResetEmailer.php");
    require_once(ConfigXml::getInstance()->servicesPath."/../email/SummaryReportEmailer.php");

    require_once(ConfigXml::getInstance()->servicesPath."/../dummy/DashboardDummy.php");
    require_once(ConfigXml::getInstance()->servicesPath."/../dummy/ProfileCompletenessDummy.php");
    require_once(ConfigXml::getInstance()->servicesPath."/../utils/Email.php");

    require_once(ConfigXml::getInstance()->servicesPath."/../pdf/CurriculumVitaePDF.php");

	class UserService extends AbstractService
	{
		public $modelName = "User";

        private $originalSession;

		public function save($object, $returnCompleteObject = false)
		{
			$savedUser = parent::save($object, $returnCompleteObject);
			$savedUser->password = "";

            $this->setUserSession($savedUser);

            // signing up as company
			if(is_undefined($object["id"]) && $object["type"] == User::TYPE_RECRUITER)
			{
				// 3 free credits
				$credit = new Credit();
				$credit->numCredits = 3;
				$credit->userId = $savedUser->id;

                $service = new FinancialTransactionService();

                $financialTransaction = new FinancialTransaction();
                $financialTransaction->amount = 0;
                $financialTransaction->numCredits = 3;
                $financialTransaction->bank = "None";
                $financialTransaction->reference = $service->generateReference($savedUser->id);
                $financialTransaction->userId = $savedUser->id;

                $billingHistory = new BillingHistory();
                $billingHistory->type = BillingHistory::TYPE_CREDIT;
                $billingHistory->numCredits = 3;
                $billingHistory->comment = "Sign-up credit";
                $billingHistory->FinancialTransaction = $financialTransaction;
                $billingHistory->userId = $savedUser->id;

                $financialTransaction->billingHistories[] = $billingHistory;

                $credit->FinancialTransaction = $financialTransaction;

                @$credit->save();
			}

            if(is_undefined($object["id"]))
                $this->sendEmailer($object["type"]);

			return $savedUser;
		}

        public function deactivateAccount()
        {
            $loggedInUser = $this->getLoggedInUser();
            $loggedInUser->active = false;

            $loggedInUser->save();
            $this->destroyUserSession();
        }

        /**
         * Send registration email
         *
         * @param $type
         * @return bool
         */
        public function sendEmailer($type)
        {
            $config = $this->getEmailDetails();

			$email = new Email($config);

            $loggedInUser = $this->getLoggedInUser();

			$email->from('no-reply@getthejob.co.za', 'GetTheJob.co.za');
			$email->to($loggedInUser->email);

            $emailer = $type == User::TYPE_JOBSEEKER ? new JobseekerRegistrationEmailer() : new RecruiterRegistrationEmailer();

			$email->subject('Welcome to GetTheJob.co.za, '.$loggedInUser->firstName);
			$email->message($emailer->getContent());

			return $email->send();
        }

        /**
         * Send bug report to development team
         *
         * @param $errorMessage
         * @param $userMessage
         * @param $page
         * @param bool $auto
         * @return bool
         */
        public function sendBugReport($errorMessage, $userMessage, $page, $auto=true)
        {
            $config = $this->getEmailDetails();
            $config['mailtype'] = 'text';

			$email = new Email($config);

            $loggedInUser = $this->getLoggedInUser(false);

			$email->from('development@getthejob.co.za', 'Exception Reporter - GetTheJob.co.za Development Team');
			$email->to("development@getthejob.co.za");

			$email->subject("Exception Report".($auto ? " (AUTO)" : ""));
			$email->message("Exception:\n".$errorMessage."\n\n-------------\n\n"
                            ."Page: ".$page."\n\n"
                            ."User message: ".$userMessage."\n\n"
                            ."Signed in user: ".@print_r($loggedInUser->toArray(), true));

			return $email->send();
        }

        /**
         * Sends password reset email
         *
         * @throws Exception
         * @param $emailAddress
         * @return bool
         */
        public function sendPasswordResetEmail($emailAddress)
        {
            // Check that the provided email address exists in the database
            $query = Doctrine_Query::create()
                    ->select("u.id")
                    ->from("User u")
                    ->where("u.email = '".$emailAddress."'")
                    ->limit(1);

            $count = $query->count();
            if($count <= 0)
                throw new Exception("No email address found");

            $config = $this->getEmailDetails();

			$email = new Email($config);

			$email->from('no-reply@getthejob.co.za', 'GetTheJob.co.za');
			$email->to($emailAddress);

            $emailer = new PasswordResetEmailer($emailAddress);

			$email->subject('GetTheJob.co.za - Password Reset');
			$email->message($emailer->getContent());

			return $email->send();
        }

        public function getUsersLike($search, $id)
        {
            $query = Doctrine_Query::create()
                    ->select("u.id, u.firstName, u.lastName, u.email")
                    ->from("User u")
                    ->where("u.firstName LIKE '%".$search["firstName"]."%'")
                    ->orWhere("u.lastName LIKE '%".$search["lastName"]."%'")
                    ->orWhere("u.id = $id");

            $query->setHydrationMode(Doctrine_Core::HYDRATE_ARRAY);
            return $query->execute();
        }

        public function sendSummaryEmail($emailAddress, $firstName)
        {
            $config = $this->getEmailDetails();

			$email = new Email($config);

			$email->from('no-reply@getthejob.co.za', 'GetTheJob.co.za');
			$email->to($emailAddress);

            $emailer = new SummaryReportEmailer($emailAddress, $firstName);

			$email->subject('GetTheJob.co.za - Summary Report');
			$email->message($emailer->getContent());

			return $email->send();
        }

        public function setNewPassword($emailAddress, $userId, $password)
        {
            $query = Doctrine_Query::create()
                    ->select("u.*")
                    ->from("User u")
                    ->where("u.email = '".$emailAddress."'")
                    ->andWhere("u.id = $userId")
                    ->limit(1);

            $results = $query->execute();
            if(count($results) < 1 || !$results)
                throw new Exception("No matching user");

            $user = $results[0];

            // SHA1 always returns a 40 character password - if the password is not 40 chars long, something is very wrong
            if(strlen($password) != 40)
            {
                $this->sendBugReport("URGENT: POTENTIAL HACKING ATTEMPT ON USER ID $userId",
                                        "ATTEMPT TO SET PASSWORD AS ".$password, "", false);
                throw new Exception("Invalid password");
            }

            $user->password = $password;
            $result = $user->trySave();

            if($result === true)
            {
                return true;
            }
            else
            {
                $user->save();
            }
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

        public function updateProfile($object)
        {
            $loggedInUser = $this->getLoggedInUser();
            $object["id"] = $loggedInUser->id;

            $preferencesQuery = Doctrine_Query::create()
                                ->select("p.*")
                                ->from("Preference p")
                                ->where("p.userId = ".$loggedInUser->id)
                                ->andWhere("p.type = 'companyExclusions'");

            $preferences = $preferencesQuery->execute();

            if(count($preferences) > 0)
                $existingPref = $preferences[0];

            if(count($object["preferences"]) > 0 && $existingPref && $existingPref->id > 0)
            {
                $newPref =& $object["preferences"][0];
                $newPref["id"] = $existingPref->id;
            }

            // save the changes
            $this->save($object, true);

            // fetch the full user (because the updatedUser will only have certain properties, not all)
            $query = Doctrine_Query::create()
                    ->select("u.*, p.*")
                    ->from("User u, u.preferences p")
                    ->where("u.id = ".$loggedInUser->id)
                    ->limit(1);

            $query->setHydrationMode(Aerial_Core::HYDRATE_AMF_COLLECTION);
            $results = $query->execute();

            if(count($results) <= 0)
            {
                throw new Exception("Cannot find user");
            }

            $fullUser = $results[0];

            // update the session
            $this->setUserSession($fullUser);

            return $fullUser;
        }
		
		public function saveMultiple($users)
		{
			$results = array();
			foreach($users as $user)
			{
				$results[] = $this->save($user);
			}

			return $results;
		}

		public function login($email, $hashedPassword)
		{
			$query = Doctrine_Query::create()
					->select("u.*")
                    ->from("User u")
                    ->where("u.email = '".$email."'")
                    ->addWhere("u.password = '$hashedPassword'")
                    ->limit(1);

			$query->setHydrationMode(Doctrine_Core::HYDRATE_RECORD);
			$users = $query->execute();

			foreach($users as &$user)
				unset($user->password);

			if(count($users) == 0)
				throw new Exception("No match");

            $user = $users[0];

            if(!$user->active)
            {
                $user->active = true;
                @$user->save();
            }

			$this->setUserSession($user);

			return $user;
		}

        /**
         * Checks whether the requested user's CV is owned by the logged in company
         *
         * @param $purchasedUserId
         * @return mixed If the user's CV is owned, the User is returned with UserInformation, 
         */
        public function checkOwnershipOfCV($purchasedUserId)
        {
            $loggedInUser = self::getLoggedInUser();

            $query = Doctrine_Query::create()
                    ->select("p.*")
                    ->from("Purchase p, p.Filter f")
                    ->where("f.userId = ".$loggedInUser->id)
                    ->andWhere("p.purchasedUserId = $purchasedUserId");

            if($query->count() == 0)
                return false;

            $query = Doctrine_Query::create()
                    ->select("u.*, ui.*")
                    ->from("User u, u.userInformations ui")
                    ->where("u.id = $purchasedUserId");

            $query->setHydrationMode(Aerial_Core::HYDRATE_AMF_COLLECTION);
            $results = $query->execute();

            if(!$results)
                return false;

            $loggedInUser = $results[0];
            unset($loggedInUser->password);

            $informations =& $loggedInUser->userInformations;
            if(!$informations || count($informations->source) == 0)
                return $loggedInUser;

            $informations = UserInformationParser::getFriendlyInformation($informations);
            $loggedInUser->userInformations = $informations;

            return $loggedInUser;
        }

		public function setUserSession($user)
		{
			@session_start();

			$_SESSION["userSession"] = $user;
		}

        /**
         * Fakes the existence of a session or temporarily replaces an existing session to perform a task
         *
         * @param $tempUser
         *
         * @return void
         */
        public function setTemporaryUserSession($tempUser)
        {
            $this->originalSession = $this->getUserSession();
            $this->setUserSession($tempUser);
        }

        public function restoreOriginalUserSession()
        {
            if($this->originalSession)
            {
                $this->setUserSession($this->originalSession);
                $this->originalSession = null;
            }
        }

		public function getUserSession()
		{
			@session_start();

			return $_SESSION["userSession"];
		}

		public function destroyUserSession()
		{
			@session_start();

			unset($_SESSION["userSession"]);
		}

        /**
         * References the current session to return the logged in User instance if it exists
         *
         * @static
         * @throws Exception
         * @param bool $throwError
         * @return User The logged in user
         */
        public static function getLoggedInUser($throwError=true)
        {
            $user = self::getUserSession();

            if(!$user && $throwError)
                throw new Exception("Not logged in");

            return $user;
        }

        /**
         * Gets the data (contained in a dummy) for the jobseeker dashboard
         *
         * @throws Exception
         * @return DashboardDummy
         */
        public function getDashboardData()
        {
            $service = new DashboardService();

            $info = new DashboardDummy();
            $info->profileViews = $service->getProfileViews();
            $info->infoPackPurchases = $service->getInfoPackPurchases();
            $info->matchQuality = $service->getMatchQualityGraph();

            return $info;
        }

        /**
         * Gets the profile data for the signed-in jobseeker
         *
         * @return Doctrine_Collection
         */
        public function getJobseekerProfile()
        {
            $user = self::getLoggedInUser();

            $query = Doctrine_Query::create()
                    ->select("u.*, p.*")
                    ->from("User u, u.preferences p")
                    ->where("u.id = ".$user->id)
                    ->limit(1);

            $query->setHydrationMode(Aerial_Core::HYDRATE_AMF_COLLECTION);
            $results = $query->execute();

            if(!$results)
                return false;

            $user = $results[0];
            return $user;
        }

        /**
         * Returns the total profile completeness of a Jobseeker's profile if percent
         *
         * @param bool $percentOnly
         * @return float
         */
        public function getProfileCompleteness($percentOnly=true)
        {
            // fields which we cannot determine whether they have been filled in or not
            $ignoredFields= array(MatchAndRankService::WORKING_HOURS, MatchAndRankService::DISABILITY,
                                        MatchAndRankService::SUPERVISORY, MatchAndRankService::DRIVERS_LICENSE,
                                        MatchAndRankService::WORK_PERMIT, MatchAndRankService::NUM_DEPENDENTS,
                                        MatchAndRankService::MESSAGE_TO_RECRUITERS, MatchAndRankService::NATIONALITY);

            // the number of UserInformation fields per user
            $numFields = 26 - count($ignoredFields);

            $user = self::getLoggedInUser();

            if($user->type != User::TYPE_JOBSEEKER)
                throw new Exception("Not logged in");

            // 70% completeness for profile fields
            $query = Doctrine_Query::create()
                    ->select("((SUM(ui.completed) / $numFields) * 70) AS percentage")
                    ->from("UserInformation ui")
                    ->where("ui.userId = ".$user->id)
                    ->andWhereNotIn("ui.type", $ignoredFields);

            $query->setHydrationMode(Doctrine_Core::HYDRATE_SINGLE_SCALAR);
            $result = $query->execute();

            $profilePercentage = (float) $result;
            if($profilePercentage > 70)
                $profilePercentage = 70;

            $percentage = $profilePercentage;

            $query = Doctrine_Query::create()
                    ->select("v.id")
                    ->from("Video v")
                    ->where("v.userId = ".$user->id);

            $hasVideo = $query->count() >= 1;

            if($hasVideo)
                $percentage += 20;

            $query = Doctrine_Query::create()
                    ->select("d.id")
                    ->from("Document d")
                    ->where("d.userId = ".$user->id);

            $hasDocument = $query->count() >= 1;

            if($hasDocument)
                $percentage += 10;

            $percentage = ceil($percentage);

            if($percentOnly)
                return $percentage > 100 ? 100 : $percentage;
            else
            {
                $dummy = new ProfileCompletenessDummy();
                $dummy->total = $percentage;
                $dummy->profilePercentage = ceil($profilePercentage);
                $dummy->videoPercentage = $hasVideo ? 20 : 0;
                $dummy->documentPercentage = $hasDocument ? 10 : 0;

                return $dummy;
            }
        }

        /**
         * Gets an array of UserInformation fields that have not been completed
         *
         * @param bool $prettyPrint Whether to get the human readable name of the field
         *
         * @throws Exception
         * @return array
         */
        public function getIncompleteFields($prettyPrint=false)
        {
            $user = self::getLoggedInUser();

            if($user->type != User::TYPE_JOBSEEKER)
                throw new Exception("Not logged in");

            $query = Doctrine_Query::create()
                    ->select("ui.type")
                    ->from("UserInformation ui")
                    ->where("ui.completed = false")
                    ->andWhere("ui.userId = ".$user->id);

            $results = $query->execute();
            $fields = array();

            if(!$results || count($results) == 0)
                return array();

            foreach($results as $record)
                $fields[] = $prettyPrint ? UserInformationParser::getLabel($record->type) : $record->type;

            return $fields;
        }

        /**
         * Gets the number of registered and ACTIVE users
         *
         * @param $code
         * @return int
         */
        public function getUserCount($code)
        {
            if($code != "gtjedoc761")
                die("Better luck next time, buddy.");

            $query = Doctrine_Query::create()
                    ->select("u.id")
                    ->from("User u")
                    ->where("u.active = TRUE");

            return (int) $query->count();
        }

        /**
         * Gets the activity per hour of all user registrations
         *
         * @param $code
         * @param null $date
         * @return PDOStatement
         */
        public function getActivityByHour($code, $date=null)
        {
            if($code != "gtjedoc761")
                die("Better luck next time, buddy.");

            if(!$date)
                $date = date("Y-m-d", time());

            $query = "SELECT HOUR(u.createdAt) AS `hour`, COUNT(u.id) AS `count`
                        FROM `User` u
                        WHERE u.createdAt BETWEEN \"".$date." 00:00:00\" AND \"".$date." 23:59:59\"
                        GROUP BY `hour`
                        ORDER BY `hour`";

            $hours = array();
            for($x = 0; $x < 24; $x++)
                $hours[$x] = 0;

            $results = $this->connection->getDbh()->query($query);
            foreach($results as $result)
            {
                $hour = (int) $result["hour"];
                $hours[$hour] = (int) $result["count"];
            }

            $info = array();
            foreach($hours as $hour => $count)
            {
                $info[] = array("hour" => $hour, "count" => $count);
            }

            return $info;
        }

        /**
         * Get users that are
         *
         * @param $code
         * @param null $date
         * @return array
         */
        public function getEligiblePrizewinners($code, $date=null)
        {
            if($code != "gtjedoc761")
                die("Better luck next time, buddy.");

            if(!$date)
                $date = date("Y-m-d", time());
            
            $query = "SELECT u.id, CONCAT(u.firstName, ' ', u.lastName) as `name`,
                    CEILING(((LEAST(SUM(ui.completed), 18) / 18) * 70) + (COUNT(DISTINCT(d.id)) * 10) + (COUNT(DISTINCT(v.id)) * 20)) AS percentage
                    FROM `User` u
                    INNER JOIN `UserInformation` ui ON ui.userId = u.id
                    LEFT JOIN `Document` d ON d.userId = u.id
                    LEFT JOIN `Video` v ON v.userId = u.id
                    WHERE u.createdAt BETWEEN \"".$date." 00:00:00\" AND \"".$date." 23:59:59\"
                    AND ui.type NOT IN ('workingHours','disability','supervisory','driversLicense','workPermit','numDependents','messageToRecruiters','nationality')
                    GROUP BY ui.userId
                    ORDER BY percentage DESC";

            $results = $this->connection->getDbh()->query($query);

            $eligible = array();

            foreach($results as $result)
            {
                $userId = (int) $result["id"];
                $percentage = (int) $result["percentage"];
                $name = $result["name"];

                if($percentage < 80)
                    continue;

                $query = Doctrine_Query::create()
                        ->select("ui.*")
                        ->from("UserInformation ui, ui.User u")
                        ->where("u.id = $userId")
                        ->andWhereIn("ui.type", array(MatchAndRankService::AGE, MatchAndRankService::LOCATION,
                                                    MatchAndRankService::RACE))
                        ->groupBy("ui.type")
                        ->orderBy("ui.updatedAt");

                $eligibleUser = new stdClass();
                $eligibleUser->id = $userId;
                $eligibleUser->name = $name;
                $eligibleUser->percentage = $percentage;

                $userInfo = $query->execute();
                foreach($userInfo as $userInformation)
                {
                    $info = json_decode($userInformation->value);

                    switch($userInformation->type)
                    {
                        case MatchAndRankService::RACE:
                            $eligibleUser->race = $info;
                            break;
                        case MatchAndRankService::AGE:
                            $birthday = $info->day . " " . $info->month . " " . $info->year;
                            $eligibleUser->age = floor((time() - strtotime($birthday))/31556926);
                            break;
                        case MatchAndRankService::LOCATION:
                            $location = new GeographicInfo();
                            $eligibleUser->location = $location->table->find($userInformation->lookupId);
                            $eligibleUser->location = @$eligibleUser->location->city;

                            break;
                    }
                }

                $eligible[] = $eligibleUser;
            }

            return $eligible;
        }

        public function shareGetTheJobProfile($sharedUserId, $toAddress, $ccAddresses)
        {
            $config = $this->getEmailDetails();

			$email = new Email($config);

			$email->from('no-reply@getthejob.co.za', 'GetTheJob.co.za');
			$email->to($toAddress);
            $email->cc($ccAddresses);

            $loggedInUser = self::getLoggedInUser();

			$email->subject("GetTheJob.co.za - Resume/CV Share");
            $email->message($loggedInUser->firstName." ".$loggedInUser->lastName." has shared a document with you.<br/>
                                Please download the attached file to view the shared Resume/CV");

            $tempName = tempnam(sys_get_temp_dir(), "gtjcv").".pdf";
            $tempFile = fopen($tempName, "w");

            $cv = new CurriculumVitaePDF();
            $cv->userId = $sharedUserId;

            $contents = $cv->generate();

            fwrite($tempFile, $contents);
            fclose($tempFile);

            $user = $cv->getUser();
            $email->attach($tempName, $user->firstName."-".$user->lastName."-Curriculum-Vitae.pdf");

			$sent = $email->send();

            // remove temporary file
            @unlink($tempName);

            return $sent;
        }

        public function shareUploadedDocument($sharedUserId, $toAddress, $ccAddresses)
        {
            $config = $this->getEmailDetails();

			$email = new Email($config);

			$email->from('no-reply@getthejob.co.za', 'GetTheJob.co.za');
			$email->to($toAddress);
            $email->cc($ccAddresses);

            $loggedInUser = self::getLoggedInUser();

			$email->subject("GetTheJob.co.za - Resume/CV Share");
            $email->message($loggedInUser->firstName." ".$loggedInUser->lastName." has shared a document with you.<br/>
                                Please download the attached file to view the shared Resume/CV");

            $tempName = tempnam(sys_get_temp_dir(), "gtjcv").".pdf";
            $tempFile = fopen($tempName, "w");

            $sharedUser = new User();
            $sharedUser = $sharedUser->table->find($sharedUserId);

            if(!$sharedUser)
                throw new Exception("No shared user");

            $documents = $sharedUser->documents;
            if(count($documents) <= 0)
                throw new Exception("No document");

            $document = $documents[0];

            try
            {
                $content = gzuncompress($document->content);
            }
            catch (Exception $e)
            {
                throw new Exception("File contains errors");
            }

            fwrite($tempFile, $content);
            fclose($tempFile);

            $email->attach($tempName, $document->filename);

			$sent = $email->send();

            // remove temporary file
            @unlink($tempName);

            return $sent;
        }
	}
?>