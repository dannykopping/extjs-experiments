<?php

    require_once(dirname(__FILE__)."/BaseEmailer.php");

    class JobseekerRegistrationEmailer extends BaseEmailer
    {
        private $content;

        private $welcomeMessage = <<<EOT
<td width="100%" style="padding-top: 20px;">
<p style="font-family: Arial, Helvetica, sans-serif;font-size: 20px;font-style: normal;font-weight: normal;font-variant: normal;text-transform: none;color: #282651;text-decoration: none;"><strong>Welcome to GetTheJob.co.za!</strong></p>
<p style="font-family: Arial, Helvetica, sans-serif; font-size: 12px; font-style: normal; font-weight: normal; font-variant: normal; text-transform: none; color: #282651; text-decoration: none;">Congratulations! You are now on the correct road to get the perfect job.</p>
<p style="font-family: Arial, Helvetica, sans-serif; font-size: 12px; font-style: normal; font-weight: normal; font-variant: normal; text-transform: none; color: #282651; text-decoration: none;">Please see the instructions to follow and complete on the left side of this page and then sit back and relax while we go to work for you 24/7 finding your perfect job - all for free!</p>
<p style="font-family: Arial, Helvetica, sans-serif; font-size: 12px; font-style: normal; font-weight: normal; font-variant: normal; text-transform: none; color: #282651; text-decoration: none;">From 14 November 2011 over 15 000 Recruitment Agencies and Employers who currently advertise jobs on Internet websites are being personally offered the opportunity to list ALL their available jobs onto GetTheJob.co.za at no cost whatsoever - forever.</p>
<p style="font-family: Arial, Helvetica, sans-serif; font-size: 12px; font-style: normal; font-weight: normal; font-variant: normal; text-transform: none; color: #282651; text-decoration: none;">In time your profile will be continually and automatically matched and ranked to all the suitable available jobs as and when they are listed on the website.Recruiters listing these suitable jobs will then be emailed with your profile.</p>
<p style="font-family: Arial, Helvetica, sans-serif; font-size: 12px; font-style: normal; font-weight: normal; font-variant: normal; text-transform: none; color: #282651; text-decoration: none;"><strong>A Tip from Recruiters</strong>:  "Job seekers should upload a one minute intro video as well as their own CV as this will increase their chances of making the Recruiters' Shortlist of candidates for interviews."</p>
<p style="font-family: Arial, Helvetica, sans-serif; font-size: 12px; font-style: normal; font-weight: normal; font-variant: normal; text-transform: none; color: #282651; text-decoration: none;">Your GetTheJob.co.za Team</p>
</td>
EOT;
        
        public function __construct()
        {
            $this->initialize();

            $this->content = $this->getTemplate(self::JOBSEEKER_REGISTRATION);

            $linkStyle = 'style="font-family: Arial, Helvetica, sans-serif;font-size: 11px;font-style: italic;font-weight: normal;font-variant: normal;text-transform: none;color: #FFFFFF;text-decoration: underline;"';

            $sideBarContents = array();
            $sideBarContents[] = $this->getSidebarItem("Fill in your Details", "Fill in your details to get accurately ".
                "matched to jobs and stand a chance of winning R1000!<br/><br/>".
                "<a href='".$this->siteURL."#/jobseeker/profile' $linkStyle>Go to 'My Profile' page</a>");

            $sideBarContents[] = $this->getSidebarItem("Record a Video", "Record a one minute intro video of yourself ".
                "to increase your chances of being selected by recruiters and potential employers<br/><br/>".
                "<a href='".$this->siteURL."#/jobseeker/video' $linkStyle>Go to 'My Video' page</a>");

            $sideBarContents[] = $this->getSidebarItem("Upload your Resume/CV", "If you have a Resume/CV ".
                "that you would like recruiters and potential employers to see, you can upload one on the 'My Profile' page<br/><br/>".
                "<a href='".$this->siteURL."#/jobseeker/profile' $linkStyle>Go to 'My Profile' page</a>");

            $this->content = $this->replace(self::CONTENT, $this->welcomeMessage, $this->content);
            $this->content = $this->replace(self::SIDEBAR, implode("\n", $sideBarContents), $this->content);

            $this->content = $this->replace(self::SIDEHEADING, "WHAT TO DO NEXT:", $this->content);
            $this->content = $this->replace(self::HEADER, "Welcome to GetTheJob.co.za!", $this->content);
        }

        public function getContent()
        {
            return $this->processBeforeSending($this->content);
        }
    }
?>