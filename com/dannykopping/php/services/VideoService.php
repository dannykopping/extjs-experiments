<?php
	import("aerialframework.service.AbstractService");

    require_once("UserService.php");

	class VideoService extends AbstractService
	{
		public $modelName = "Video";

		public function save($object, $returnCompleteObject = false)
		{
			$userId = $object["userId"];

			$user = new User();
			$user = $user->table->find($userId);

			if(!$user)
				throw new Exception("Could not find user: $userId");

			// clear previous videos
			$user->video->clear();
			$user->save();

			return parent::save($object, $returnCompleteObject);
		}

        public function clearVideo()
        {
            $user = UserService::getLoggedInUser();

            if($user->type != User::TYPE_JOBSEEKER)
                throw new Exception("Not logged in");

            $video = $user->video;
            if($video)
                return $video->delete();
        }

        /**
         * Checks whether the signed-in user had uploaded a video
         *
         * @return bool
         */
        public function hasUploadedVideo()
        {
            $loggedInUser = UserService::getLoggedInUser();

            $videoCount = Doctrine_Query::create()
                    ->select("v.id")
                    ->from("Video v")
                    ->where("v.userId = ".$loggedInUser->id)
                    ->count();

            return $videoCount > 0;
        }
	}
?>