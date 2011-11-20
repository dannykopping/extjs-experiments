<?php
	import("aerialframework.service.AbstractService");

	class GeographicInfoService extends AbstractService
	{
		public $modelName = "GeographicInfo";

		public function isInRange($sourceGeo, $targetId, $distance)
		{
			$sourceGeo = $this->table->find($sourceGeo);

			if(!$sourceGeo || !$targetId)
				return false;

			$lat = $sourceGeo->lat;
			$lng = $sourceGeo->lng;

			$query = "SELECT id, (((ACOS(SIN($lat * PI() / 180) * SIN(lat * PI() / 180) + COS($lat * PI() / 180) *
						COS(lat * PI() / 180) * COS(($lng - lng) * PI() / 180)) * 180 / PI()) * 60 * 1.1515 * 1.609344))
						AS distance
						FROM GeographicInfo WHERE id = $targetId
						HAVING (distance >= 0 AND distance <= ($distance + 5))
						ORDER BY distance ASC";

			$query = $this->connection->getDbh()->query($query);
			$results = $query->fetchAll(PDO::FETCH_OBJ);
			
			return count($results) > 0;
		}
        
        public function findPlace($search)
        {
            $query = Doctrine_Query::create()
                    ->select("gi.city, gi.area1, gi.zip, gi.lat, gi.lng")
                    ->distinct(true)
                    ->from("GeographicInfo gi")
                    ->where("gi.city LIKE '".$search."%'")
                    ->orWhere("gi.area1 LIKE '".$search."%'")
                    ->orWhere("gi.area2 LIKE '".$search."%'")
                    ->orderBy("LENGTH(gi.area1), gi.zip ASC")
                    ->limit(20);

            $query->setHydrationMode(Doctrine_Core::HYDRATE_RECORD);
            return $query->execute();
        }
	}
?>