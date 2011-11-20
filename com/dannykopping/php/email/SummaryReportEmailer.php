<?php

    require_once(dirname(__FILE__)."/BaseEmailer.php");

    require_once(dirname(__FILE__)."/../services/UserService.php");
    require_once(dirname(__FILE__)."/../services/DocumentService.php");
    require_once(dirname(__FILE__)."/../services/VideoService.php");

    class SummaryReportEmailer extends BaseEmailer
    {
        private $content;

        private $emailAddress;

        const FIRST_NAME = "FIRST_NAME";
        const DAY_COUNT = "DAY_COUNT";

        private $summaryMessage = <<<EOT
<td width="100%" style="padding-top: 20px;">
<p style="font-family: Arial, Helvetica, sans-serif;font-size: 20px;font-style: normal;font-weight: normal;font-variant: normal;text-transform: none;color: #282651;text-decoration: none;"><strong>GetTheJob.co.za - Your Updated Report<br/>{{DATE}}</strong></p>
<p style="font-family: Arial, Helvetica, sans-serif; font-size: 12px; font-style: normal; font-weight: normal; font-variant: normal; text-transform: none; color: #282651; text-decoration: none;">{{FIRST_NAME}}, are you still looking for a job or a better job?</p>
<p style="font-family: Arial, Helvetica, sans-serif; font-size: 12px; font-style: normal; font-weight: normal; font-variant: normal; text-transform: none; color: #282651; text-decoration: none;">Currently, only jobseekers are being invited to register on GetTheJob.co.za.</p>
<p style="font-family: Arial, Helvetica, sans-serif; font-size: 12px; font-style: normal; font-weight: normal; font-variant: normal; text-transform: none; color: #282651; text-decoration: none;">From 14 November 2011 over <strong>15 000 Recruitment Agencies</strong> and Employers who currently advertise jobs on Internet websites are being personally offered the opportunity to list ALL their available jobs onto GetTheJob.co.za at no cost whatsoever - forever.</p>
<p style="font-family: Arial, Helvetica, sans-serif; font-size: 12px; font-style: normal; font-weight: normal; font-variant: normal; text-transform: none; color: #282651; text-decoration: none;">In time your profile will be <strong>continually and automatically matched and ranked</strong> to all the <strong>suitable available jobs</strong> as and when they are listed on the website. Recruiters listing these suitable jobs will then be emailed with your profile.</p>
<p style="font-family: Arial, Helvetica, sans-serif; font-size: 12px; font-style: normal; font-weight: normal; font-variant: normal; text-transform: none; color: #282651; text-decoration: none;">For reasons of confidentiality we only disclose the winners first name and the first initial of their surname as well as their area of residence. Thanks to the many Jobseekers who have already completed their registration details in full and we look forward to promoting them to Recruiters from the 14 November 2011.</p>
<p style="font-family: Arial, Helvetica, sans-serif; font-size: 12px; font-style: normal; font-weight: normal; font-variant: normal; text-transform: none; color: #282651; text-decoration: none;"><strong>A Tip from Recruiters</strong>:  "Job seekers should upload a one minute intro video as well as their own CV as this will increase their chances of making the Recruiters' Shortlist of candidates for interviews."</p>
<p style="font-family: Arial, Helvetica, sans-serif; font-size: 12px; font-style: normal; font-weight: normal; font-variant: normal; text-transform: none; color: #282651; text-decoration: none;">Best wishes</p><p style="font-family: Arial, Helvetica, sans-serif; font-size: 12px; font-style: normal; font-weight: normal; font-variant: normal; text-transform: none; color: #282651; text-decoration: none;">Your GetTheJob.co.za Team</p>
<p style="font-family: Arial, Helvetica, sans-serif; font-size: 12px; font-style: normal; font-weight: normal; font-variant: normal; text-transform: none; color: #282651; text-decoration: none;">PS: Please add no-reply@getthejob.co.za to your email contacts to ensure you receive important updated information about your job hunting progress</p>
</td>
EOT;

        public function __construct($emailAddress, $firstName)
        {
            $this->initialize();

            $this->emailAddress = $emailAddress;

            $this->content = $this->getTemplate(self::SUMMARY_REPORT);

            $sideBarContents = $this->getSidebarItems();

            $start_ts = strtotime(date("d M Y"));
            $end_ts = strtotime("14 November 2011");
            $diff = $end_ts - $start_ts;
            $dayCount = round($diff / 86400);

            $this->summaryMessage = $this->replace(self::FIRST_NAME, $firstName, $this->summaryMessage);
            $this->summaryMessage = $this->replace(self::DAY_COUNT, $dayCount, $this->summaryMessage);
            $this->content = $this->replace(self::CONTENT, $this->summaryMessage, $this->content);
            $this->content = $this->replace(self::SIDEBAR, implode("\n", $sideBarContents), $this->content);

            $this->content = $this->replace(self::SIDEHEADING, "", $this->content);
            $this->content = $this->replace(self::HEADER, "GetTheJob.co.za - Your Updated Report", $this->content);
        }

        public function getContent()
        {
            return $this->processBeforeSending($this->content);
        }

        private function getSidebarItems()
        {
            $linkStyle = 'style="font-family: Arial, Helvetica, sans-serif;font-size: 11px;font-style: italic;font-weight: normal;font-variant: normal;text-transform: none;color: #FFFFFF;text-decoration: underline;"';

            $sideBarContents = array();

            $query = Doctrine_Query::create()
                    ->select("u.*")
                    ->from("User u")
                    ->where("u.email = '".$this->emailAddress."'")
                    ->limit(1);

            $results = $query->execute();

            if(count($results) <= 0 || !$results)
                return array();

            $user = $results[0];
            $userService = new UserService();
            $userService->setTemporaryUserSession($user);

            $incompleteFields = $userService->getIncompleteFields(true);
            $completeness = $userService->getProfileCompleteness(true);

            foreach($incompleteFields as $key => $value)
                $incompleteFields[$key] = "- ".$value;

            $documentService = new DocumentService();
            $hasUploadedResume = $documentService->hasUploadedResume();

            $videoService = new VideoService();
            $hasUploadedVideo = $videoService->hasUploadedVideo();

            $userService->restoreOriginalUserSession();

            if(count($incompleteFields) > 0)
            {
                $incompleteFields = implode("<br/>", $incompleteFields);

                $sideBarContents[] = $this->getSidebarItem("Fill in your Details (".$completeness."% complete)",
                   "The following fields in your profile are incomplete:<br/><br/>$incompleteFields<br/><br/>".
                   "<a href='".$this->siteURL."#/jobseeker/profile' $linkStyle>Go to 'My Profile' page</a>");
            }

            if(!$hasUploadedResume)
            {
                $sideBarContents[] = $this->getSidebarItem("Upload your Resume/CV", "You have not yet uploaded ".
                   "your resume/cv document. Add an extra 10% to your profile by uploading one.<br/><br/>".
                   "<a href='".$this->siteURL."#/jobseeker/profile' $linkStyle>Go to 'My Profile' page</a>");
            }

            if(!$hasUploadedVideo)
            {
                $sideBarContents[] = $this->getSidebarItem("Record a Video", "You have not yet recorded ".
                   "a one-minute intro video. Add an extra 20% to your profile by recording one.<br/><br/>".
                   "<a href='".$this->siteURL."#/jobseeker/video' $linkStyle>Go to 'My Video' page</a>");
            }

            if(count($incompleteFields) == 0 && $hasUploadedResume && $hasUploadedVideo)
            {
                $sideBarContents[] = $this->getSidebarItem("Your Profile is 100% Complete",
                   "Your profile is 100% complete! You can now sit back and relax while we find you the perfect job.");
            }

            return $sideBarContents;
        }
    }
?>