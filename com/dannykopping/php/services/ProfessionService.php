<?php
	import("aerialframework.service.AbstractService");

	class ProfessionService extends AbstractService
	{
		public $modelName = "Profession";

		public function findProfessions($search, $excludedIDs)
		{
			$search = trim($search);
			$where = "";

			if(count($excludedIDs) > 0)
				$where = "id != ".join(" AND id != ", $excludedIDs)." AND";

            // check for active filters that contain the searched-for job title
            $numJobsSubQuery = "(SELECT COUNT(fd.id) FROM FilterData fd WHERE fd.filterCriterionId IN
                                (SELECT fc.id FROM FilterCriterion fc, Filter f WHERE fc.name = 'jobTitle'
                                  AND f.id = fc.filterId AND f.active = TRUE) AND fd.value REGEXP
                                '([[:punct:]](' + p.id + ')[[:punct:]])')";

            // search for professions which match the search
			$query = "SELECT *, ROUND((MATCH (p.name) AGAINST ('*$search*' IN BOOLEAN MODE) * 2
						+ MATCH (p.category) AGAINST ('*$search*' IN BOOLEAN MODE) * 1) / 3 * 100, 2) as relevance,
						$numJobsSubQuery AS numJobs
						FROM Profession p WHERE $where MATCH (p.name, p.category)
						AGAINST ('*$search*' IN BOOLEAN MODE) ORDER BY relevance DESC LIMIT 0, 10";

			$results = $this->connection->getDbh()->query($query);

            $p = new Profession();
            $professions = array();

            foreach($results as $result)
            {
                $profession = new stdClass();
                $profession->_explicitType = $p->_explicitType;     // fake the object type

                // assign the properties
                $profession->id = $result["id"];
                $profession->category = $result["category"];
                $profession->name = $result["name"];
                $profession->numJobs = $result["numJobs"];

                $professions[] = $profession;
            }

            return $professions;
		}

        public function findSimilar($search)
        {
            $search = str_replace(",", " ", $search);
            $pieces = preg_split ("/(\s|\/|\&)+/", $search);

            $likeClausePieces = array();
            $jaroClausePieces = array();
            foreach($pieces as $piece)
            {
                // ignore strings shorter than 3 characters
                if(strlen($piece) < 3)
                    continue;

                $likeClausePieces[] = "`name` LIKE \"%$piece\"";
                $jaroClausePieces[] = "`jaro_winkler_similarity`(`name`, \"$piece\")";
            }

            $likeClausePieces = array_unique($likeClausePieces);
            $jaroClausePieces = array_unique($jaroClausePieces);

            $jaroClause = "";

            if(count($jaroClausePieces) > 1)
                $jaroClause = "GREATEST(".implode(", ", $jaroClausePieces).")";
            else
                $jaroClause = $jaroClausePieces[0];

            $query = "SELECT id, $jaroClause * 25 AS score
                    FROM (SELECT id, `name` FROM Profession WHERE ".implode(" OR ", $likeClausePieces).") AS likeMatches
                    ORDER BY score DESC LIMIT 10";

            $results = $this->connection->getDbh()->query($query);
            $p = $results->fetchAll(PDO::FETCH_OBJ);

            return $p;
        }

		public function findUniqueCategories()
		{
			$query = Doctrine_Query::create()
					->select("p.category")
					->from("Profession p")
					->distinct(true)
					->orderBy("p.category");

			$query->setHydrationMode(Doctrine_Core::HYDRATE_SCALAR);
			return $query->execute();
		}
	}
?>