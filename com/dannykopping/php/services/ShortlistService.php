<?php
	import("aerialframework.service.AbstractService");

    require_once("../dummy/ShortlistDummy.php");
    require_once("UserService.php");

	class ShortlistService extends AbstractService
	{
		public $modelName = "Shortlist";

        public function getShortlists()
        {
            $loggedInUser = UserService::getLoggedInUser();

            $query = Doctrine_Query::create()
                    ->select("sl.*, slu.id")
                    ->from("Shortlist sl, sl.users slu")
                    ->where("sl.userId = ".$loggedInUser->id)
                    ->orderBy("sl.updatedAt DESC");

            $query->setHydrationMode(Aerial_Core::HYDRATE_AMF_COLLECTION);
            $existingShortlists = $query->execute();
            $existingShortlists = $existingShortlists->source;

            $shortlists = new Aerial_ArrayCollection();

            foreach($existingShortlists as $shortlist)
            {
                $shortlist->purchasedUsers = $this->getPurchasedUsers($shortlist->id);
                $shortlists->source[] = $shortlist;
            }

            return $shortlists;
        }

        public function getPurchasedUsers($shortlistId)
        {
            $loggedInUser = UserService::getLoggedInUser();

            $userId = $loggedInUser->id;
            $query = "SELECT DISTINCT(pu.id), pu.firstName, pu.lastName FROM Purchase p
                        INNER JOIN `Filter` f ON p.filterId = f.id
                        INNER JOIN `User` u ON u.id = $userId AND f.userId = u.id
                        INNER JOIN `User` pu ON pu.id = p.purchasedUserId
                        INNER JOIN `Shortlist` s ON s.userId = u.id AND s.id = $shortlistId
                        INNER JOIN `ShortlistUser` su ON su.shortlistId = s.id AND su.userId = p.purchasedUserId";

            $query = $this->connection->getDbh()->query($query);
            $results = $query->fetchAll(PDO::FETCH_OBJ);

            return $results;
        }

        public function getAnyMatchingFilterForUser($userId)
        {
            $loggedInUser = UserService::getLoggedInUser();

            $query = "SELECT f.id FROM Filter f
                        INNER JOIN FilterUserMatch fum ON fum.filterId = f.id
                        INNER JOIN `User` u ON fum.userId = u.id
                        WHERE u.id = $userId
                        AND f.userId = ".$loggedInUser->id."
                        LIMIT 1";

            $results = $this->connection->getDbh()->query($query);
            if(!$results || count($results) == 0)
                throw new Exception("No matching filter");

            $filter = $results->fetch(PDO::FETCH_ASSOC);
            if(!$filter || count($filter) == 0)
                throw new Exception("No matching filter");

            return (int) $filter["id"];
        }
	}
?>