<?php
	import("aerialframework.service.AbstractService");

    require_once("UserService.php");

	class DocumentService extends AbstractService
	{
		public $modelName = "Document";

        /**
         * Checks whether the signed-in user had uploaded a résumé document
         *
         * @return bool
         */
        public function hasUploadedResume()
        {
            $loggedInUser = UserService::getLoggedInUser();

            $documentCount = Doctrine_Query::create()
                    ->select("d.id")
                    ->from("Document d")
                    ->where("d.userId = ".$loggedInUser->id)
                    ->count();

            return $documentCount > 0;
        }

        /**
         * Gets the uploaded document name (if a document exists)
         *
         * @return bool
         */
        public function getUploadedDocumentName()
        {
            $loggedInUser = UserService::getLoggedInUser();

            $documents = $this->table->findBy("userId", $loggedInUser->id);
            if(count($documents) <= 0 || !$documents)
                return null;

            $document = $documents[0];
            return $document->filename;
        }

        /**
         * Deletes the uploaded document (if a document exists)
         *
         * @return bool
         */
        public function deleteUploadedDocument()
        {
            $loggedInUser = UserService::getLoggedInUser();

            // fire and forget
            @$loggedInUser->documents->delete();
        }

        /**
         * Gets the user's uploaded document in an uncompressed format
         *
         * @return Document
         */
        public function getUncompressedDocument()
        {
            $loggedInUser = UserService::getLoggedInUser();

            $documents = $this->table->findBy("userId", $loggedInUser->id);
            if(count($documents) <= 0 || !$documents)
                return null;

            $document = $documents[0];
            $document->content = gzuncompress($document->content);

            return $document;
        }
	}
?>