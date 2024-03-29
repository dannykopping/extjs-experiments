<?php
	class VideoService
	{
		public function mergeFiles($userId, $salt)
		{
			$streamsPath = realpath(ConfigXml::getInstance()->config->paths->{"red5-recordings"});

			$videoFile = "$streamsPath/".sha1($userId)."-".$salt."_Video.flv";
			$audioFile = "$streamsPath/".sha1($userId)."-".$salt."_Audio.flv";

			$targetAudio = $this->audioFLVtoMP3($audioFile, $salt);

			return $this->mergeVideoAndMP3($videoFile, $audioFile, $targetAudio, $salt);
		}

		private function audioFLVtoMP3($audioFile, $salt)
		{
			if(!realpath($audioFile))
				throw new Exception("No audio file found.");

			// convert audio FLV to mp3
			$targetAudio = dirname(realpath($audioFile))."/$salt-audio.mp3";

			try
			{
				exec("ffmpeg -y -i $audioFile -ac 2 -ab 128kb $targetAudio");
			}
			catch(Exception $e)
			{
				throw new Exception("Audio conversion failed");
			}

			$targetAudio = realpath($targetAudio);
			if(!$targetAudio)
				throw new Exception("Audio conversion failed");

			return $targetAudio;
		}

		private function mergeVideoAndMP3($videoFile, $audioFile, $targetAudio, $salt)
		{
			if(!realpath($videoFile))
				throw new Exception("No video file found.");

			$basename = basename($videoFile);
			$filename = substr($basename, 0, strrpos($basename, "_Video."));

			$targetVideo = dirname(realpath($videoFile))."/merged/$filename.flv";

			try
			{
				exec("ffmpeg -i $videoFile -itsoffset 00:00:0 -i $targetAudio -acodec copy -vcodec copy $targetVideo");
			}
			catch(Exception $e)
			{
				throw new Exception("Audio conversion failed");
			}

			if(!realpath($targetVideo))
				throw new Exception("Merge process failed");

			try
			{
				// delete originals if everything succeeded
				exec("rm -f $videoFile $audioFile $targetAudio");
			}
			catch(Exception $e)
			{
				// fail silently if originals can't be deleted
			}

			return "$filename.flv";
		}

		public function deleteVideo($videoFile)
		{
			$streamsPath = realpath(ConfigXml::getInstance()->config->paths->{"red5-recordings"});
			$videoFile = realpath("$streamsPath/$videoFile");

			// if no video exists, return false
			if(!$videoFile)
				return false;

			try
			{
				exec("rm -f $videoFile");
				exec("rm -f $videoFile.meta");      // metadata file generated by Red5 (just being pedantic by deleting it)
			}
			catch(Exception $e)
			{
				return false;
			}

			// if the file has been deleted, return true
			if(!realpath($videoFile))
				return true;
		}
	}
?>