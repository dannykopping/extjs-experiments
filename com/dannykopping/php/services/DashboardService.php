<?php

    require_once("BillingHistoryService.php");
    require_once("UserService.php");

    require_once(ConfigXml::getInstance()->servicesPath."/../dummy/ProfileViewDummy.php");
    require_once(ConfigXml::getInstance()->servicesPath."/../dummy/MatchQualityDummy.php");

    class DashboardService
    {
        private $_connection;

        public function __construct()
        {
            $this->_connection = Bootstrapper::getInstance()->conn;
        }

        // recruiter functions

        /**
         * Gets the number of credits a company has in its balance
         *
         * @throws Exception
         * @return int
         */
        public function getCurrentBalance()
        {
            $service = new BillingHistoryService();
            $billingInfo = $service->getCurrentBalance();

            if(!$billingInfo)
                throw new Exception("Cannot load balance");

            $balance = $billingInfo->numCredits - $billingInfo->numPurchases;
            if($balance < 0)
                $balance = 0;

            return $balance;
        }

        /**
         * Returns the number of InfoPacks purchased
         *
         * @return int
         */
        public function getNumInfoPacksPurchased()
        {
            $loggedInUser = UserService::getLoggedInUser();

            $query = Doctrine_Query::create()
                    ->select("p.id")
                    ->from("Purchase p, p.Filter f")
                    ->where("f.userId = ".$loggedInUser->id);

            return $query->count();
        }

        /**
         * Gets an average value for the number of match results returned for each job specification
         *
         * @throws Exception
         * @return float|int
         */
        public function getAverageMatchesPerJobSpec()
        {
            $loggedInUser = UserService::getLoggedInUser();

            $query = Doctrine_Query::create()
                    ->select("f.id")
                    ->from("Filter f")
                    ->where("f.userId = ".$loggedInUser->id);

            $query->setHydrationMode(Doctrine_Core::HYDRATE_SCALAR);
            $results = $query->execute();

            $filterIds = array();
            foreach($results as $result)
            {
                $filterIds[] = (int) $result["f_id"];
            }

            if(count($filterIds) <= 0)
            {
                //throw new Exception("No filters");
                return 0;
            }

            // get total purchased and non-purchased matching users for each filter

            // note: a purchased user may still be hanging around in the FilterUserMatch table,
            // but the UNION function in MySQL removes all duplicates - fuck yeah!

            $allFilterIds = "(".implode(", ", $filterIds).")";
            $query = "SELECT COUNT(userId) AS total FROM
                      (SELECT fum.userId AS userId FROM FilterUserMatch fum WHERE fum.filterId IN $allFilterIds
                      UNION
                      SELECT p.purchasedUserId AS userId FROM Purchase p WHERE p.filterId IN $allFilterIds)
                      AS countALL";

			$query = $this->_connection->getDbh()->query($query);
			$results = $query->fetchAll(PDO::FETCH_ASSOC);

            if(count($results) < 1)
                throw new Exception("Could not find any matches");

            $totalMatchesAndPurchased = (int) $results[0]["total"];

            // divide the total number of matched and purchased users by the number of filters to obtain an average
            $avg = round($totalMatchesAndPurchased / count($filterIds), 2);
            return $avg;
        }

        /**
         * Gets a list of the latest 50 matched jobseekers who registered or updated their profiles
         *
         * @throws Exception
         * @return Aerial_ArrayCollection
         */
        public function getFreshMatches()
        {
            $loggedInUser = UserService::getLoggedInUser();
            $userId = $loggedInUser->id;

            $query = Doctrine_Query::create()
                    ->select("fum.updatedAt, u.id, fum.percentage, f.name")
                    ->from("FilterUserMatch fum")
                    ->innerJoin("fum.Filter f", "f.id = fum.filterId")
                    ->innerJoin("fum.User u", "fum.userId = u.id")
                    ->where("f.userId = $userId")
//                    ->andWhere("fum.percentage >= $minPercentage")
                    ->orderBy("fum.percentage DESC, fum.updatedAt DESC")
                    ->limit(50);

            $query->setHydrationMode(Aerial_Core::HYDRATE_AMF_COLLECTION);
            return $query->execute();
        }

        /**
         * Gets all the recent purchases (last 50) related to the signed-in user
         *
         * @return Doctrine_Collection
         */
        public function getRecentPurchases()
        {
            $loggedInUser = UserService::getLoggedInUser();

            $query = Doctrine_Query::create()
                    ->select("p.*, pu.id, f.id, pu.firstName, pu.lastName, f.name")
                    ->from("Purchase p, p.PurchasedUser pu, p.Filter f")
                    ->where("f.userId = ".$loggedInUser->id)
                    ->orderBy("p.updatedAt DESC")
                    ->limit(50);

            $query->setHydrationMode(Aerial_Core::HYDRATE_AMF_COLLECTION);
            return $query->execute();
        }

        /**
         * Gets a date-to-date account of the purchasing activity of InfoPacks and credits for the logged-in company
         *
         * @param int $startDate
         * @param int $endDate
         * @return array
         */
        public function getPurchaseCreditGraph($startDate=0, $endDate=0)
        {
            $loggedInUser = UserService::getLoggedInUser();
            $userId = $loggedInUser->id;
            
            // default start date - 1 month ago
            if(!$startDate || $startDate == "")
                $startDate = "'".date("Y-m-d H:i:s", strtotime("-1 month"))."'";

            // default end date - now
            if(!$endDate || $endDate == "")
                $endDate = "NOW()";

            $purchasesAndCreditsQuery = "SELECT DATE(p.createdAt) AS date,
                        CONCAT(u.firstName, ' ', u.lastName) AS purchasedUserName, f.name, NULL AS numCredits
                        FROM Purchase p
                        INNER JOIN `User` u ON (p.purchasedUserId = u.id)
                        INNER JOIN Filter f ON (p.filterId = f.id)
                        WHERE p.createdAt BETWEEN $startDate AND $endDate
                        AND f.userId = $userId

                        UNION

                        SELECT DATE(c.createdAt), NULL, NULL, c.numCredits
                        FROM Credit c
                        WHERE c.createdAt BETWEEN $startDate AND $endDate
                        AND c.userId = $userId
                        ORDER BY `date`";

            $query = $this->_connection->getDbh()->query($purchasesAndCreditsQuery);
			$purchasesAndCredits = $query->fetchAll(PDO::FETCH_ASSOC);

            $query = "SELECT DATE(MIN(`date`)) AS minDate, DATE(MAX(`date`)) AS maxDate FROM
                        ($purchasesAndCreditsQuery) as purchasesAndCredits";

            $query = $this->_connection->getDbh()->query($query);
			$limits = $query->fetchAll(PDO::FETCH_ASSOC);

            if(count($limits) <= 0)
                return array();

            $minDate = strtotime($limits[0]["minDate"]);
            $maxDate = strtotime($limits[0]["maxDate"]);

            // if the minDate and maxDate are equal, give them some distance
            if($minDate == $maxDate)
                $minDate = strtotime("-1 week", $minDate);

            $currentDate = $minDate;

            // add a day after the maximum date so that the final graph values won't go offscreen
            $maxDate = strtotime("+1 day", $maxDate);

            $dates = array();

            // fill the $dates array up with all the days between the $minDate and the $maxDate
            while($currentDate < $maxDate)
            {
                $dates[date("d M", $currentDate)] = array("users" => array(), "credits" => 0);
                $currentDate = strtotime("+1 day", $currentDate);
            }

            foreach($purchasesAndCredits as $result)
            {
                // get short representation of date
                $date = strtotime($result["date"]);
                $date = date("d M", $date);

                if($result["purchasedUserName"])
                {
                    $dates[$date]["users"][] = $result["purchasedUserName"];
                }
                else if($result["numCredits"])
                {
                    $dates[$date]["credits"] = (int) $result["numCredits"];
                }
            }

            // flatten out the data

            $graph = array();

            $credits = 0;
            foreach($dates as $date => $value)
            {
                $value["date"] = $date;

                if($value["credits"])
                    $credits += $value["credits"];

                if(count($value["users"]) > 0)
                    $credits -= count($value["users"]);

                $value["credits"] = $credits;
                $value["userCount"] = count($value["users"]);

                $graph[] = $value;
            }

            return $graph;
        }

        // jobseeker functions

        /**
         * Returns all the profile views by all companies for the logged-in user's profile
         *
         * @return int
         */
        public function getProfileViews()
        {
            $loggedInUser = UserService::getLoggedInUser();
            $userId = $loggedInUser->id;

            if($loggedInUser->type != User::TYPE_JOBSEEKER)
                throw new Exception("Not logged in");

            $query = "SELECT SUM(pv.count) AS totalViews, COUNT(DISTINCT(pv.filterId)) AS numJobSpecs,
                            COUNT(DISTINCT(u.id)) AS numCompanies, MAX(pv.updatedAt) AS lastUpdate
                        FROM ProfileView pv
                        INNER JOIN Filter f ON (pv.filterId = f.id)
                        INNER JOIN `User` u ON (f.userId = u.id)
                        WHERE pv.userId = $userId";

            $query = $this->_connection->getDbh()->query($query);
			$data = $query->fetchAll(PDO::FETCH_OBJ);

            if(count($data) == 0)
                return null;

            $data = $data[0];

            $lastUpdate = new Date();
            $lastUpdate->time = strtotime($data->lastUpdate);

            $profileViewDummy = new ProfileViewDummy();
            $profileViewDummy->totalViews = (int) $data->totalViews;
            $profileViewDummy->numJobSpecs = (int) $data->numJobSpecs;
            $profileViewDummy->numCompanies = (int) $data->numCompanies;
            $profileViewDummy->lastUpdate = $lastUpdate;

            return $profileViewDummy;
        }

        /**
         * Returns the number of times a jobseeker's InfoPack has been purchased
         *
         * @return int
         */
        public function getInfoPackPurchases()
        {
            $user = UserService::getLoggedInUser();
            $userId = $user->id;

            if($user->type != User::TYPE_JOBSEEKER)
                throw new Exception("Not logged in");

            $query = Doctrine_Query::create()
                    ->select("p.id")
                    ->from("Purchase p")
                    ->where("p.purchasedUserId = $userId")
                    ->count();

            return $query;
        }

        public function getMatchQualityGraph()
        {
            $user = UserService::getLoggedInUser();
            $userId = $user->id;

            if($user->type != User::TYPE_JOBSEEKER)
                throw new Exception("Not logged in");

            $query = Doctrine_Query::create()
                    ->select("fum.id, fum.percentage")
                    ->from("FilterUserMatch fum")
                    ->where("fum.userId = $userId");

            $quality = new MatchQualityDummy();

            $matches = $query->execute();
            foreach($matches as $match)
            {
                if($match->percentage <= 50)
                {
                    $quality->bad++;
                }
                else if($match->percentage > 50 && $match->percentage <= 80)
                {
                    $quality->good++;
                }
                else
                {
                    $quality->excellent++;
                }
            }

            if(!$quality->bad)
                $quality->bad = 0;

            if(!$quality->good)
                $quality->good = 0;

            if(!$quality->excellent)
                $quality->excellent = 0;

            return $quality;
        }

        /**
         * Gets the data (contained in a dummy) for the recruiter dashboard
         *
         * @throws Exception
         * @return DashboardDummy
         */
        public function getRecruiterDashboardData()
        {
            $loggedInUser = UserService::getLoggedInUser();

            $dashboardInfo = new DashboardDummy();
            $dashboardInfo->availableCredits = $this->getCurrentBalance();
            $dashboardInfo->numInfoPacksPurchased = $this->getNumInfoPacksPurchased();
            $dashboardInfo->avgMatchesPerJobSpec = $this->getAverageMatchesPerJobSpec();
            $dashboardInfo->freshMatches = $this->getFreshMatches();
            $dashboardInfo->recentPurchases = $this->getRecentPurchases();
            $dashboardInfo->purchasesCreditsGraph = $this->getPurchaseCreditGraph();

            return $dashboardInfo;
        }
    }
?>