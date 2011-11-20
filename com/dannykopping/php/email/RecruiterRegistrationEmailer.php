<?php

    require_once(dirname(__FILE__)."/BaseEmailer.php");

    class RecruiterRegistrationEmailer extends BaseEmailer
    {
        private $content;

        private $welcomeMessage = <<<EOT
<td width="100%" style="padding-top: 20px;">
<p style="font-family: Arial, Helvetica, sans-serif;font-size: 20px;font-style: normal;font-weight: normal;font-variant: normal;text-transform: none;color: #282651;text-decoration: none;"><strong>Welcome to GetTheJob.co.za!</strong></p>
<p style="font-family: Arial, Helvetica, sans-serif; font-size: 12px; font-style: normal; font-weight: normal; font-variant: normal; text-transform: none; color: #282651; text-decoration: none;">As a Recruiter we are going to revolutionise your job advertising and job search functions - all for <strong>free</strong>.</p>
<p style="font-family: Arial, Helvetica, sans-serif; font-size: 12px; font-style: normal; font-weight: normal; font-variant: normal; text-transform: none; color: #282651; text-decoration: none;">Advertise <strong>ALL</strong> your available jobs at <strong>no cost</strong> whatsoever - forever!</p>
<p style="font-family: Arial, Helvetica, sans-serif; font-size: 12px; font-style: normal; font-weight: normal; font-variant: normal; text-transform: none; color: #282651; text-decoration: none;">Use our <strong>free</strong> revolutionary, scientific <strong>Match+Rank<sup>TM </sup> Technology </strong>to find a selection of suitable candidates and then fine tune this selection via our                    &quot;<strong>Fine Tune</strong>&quot;  button to determine the importance of various criteria.Your fine tuned shortlist of each candidate's profile appears in milliseconds - all for free.</p>
<p style="font-family: Arial, Helvetica, sans-serif; font-size: 12px; font-style: normal; font-weight: normal; font-variant: normal; text-transform: none; color: #282651; text-decoration: none;">Your shortlist shows each handpicked candidate's profile and after spending from R90 - R150 for the candidate's InfoPack you will see their complete profile, including their name and contact details, and - if uploaded - a one minute intro video and their own CV.</p>
<p style="font-family: Arial, Helvetica, sans-serif; font-size: 12px; font-style: normal; font-weight: normal; font-variant: normal; text-transform: none; color: #282651; text-decoration: none;">For a total cost of R90 - R150 the perfect candidate can be yours in milliseconds.</p>
<p style="font-family: Arial, Helvetica, sans-serif; font-size: 12px; font-style: normal; font-weight: normal; font-variant: normal; text-transform: none; color: #282651; text-decoration: none;">Your own personal dashboard keeps you up to date 24/7 and we start by offering you <strong>3 free InfoPacks</strong>.</p>
<p style="font-family: Arial, Helvetica, sans-serif; font-size: 12px; font-style: normal; font-weight: normal; font-variant: normal; text-transform: none; color: #282651; text-decoration: none;">Welcome to the Revolution in Recruitment!</p>
<p style="font-family: Arial, Helvetica, sans-serif; font-size: 12px; font-style: normal; font-weight: normal; font-variant: normal; text-transform: none; color: #282651; text-decoration: none;">If you have any queries please email us on <a href="mailto:recruiters@getthejob.co.za">recruiters@getthejob.co.za</a></p>
<p style="font-family: Arial, Helvetica, sans-serif; font-size: 12px; font-style: normal; font-weight: normal; font-variant: normal; text-transform: none; color: #282651; text-decoration: none;">Thank you</p>
<p style="font-family: Arial, Helvetica, sans-serif; font-size: 12px; font-style: normal; font-weight: normal; font-variant: normal; text-transform: none; color: #282651; text-decoration: none;">Your GetTheJob.co.za Team.</p>
<p style="font-family: Arial, Helvetica, sans-serif; font-size: 12px; font-style: normal; font-weight: normal; font-variant: normal; text-transform: none; color: #282651; text-decoration: none;">PS: We are building up our Job seeker database and expect to develop it extensively during the months ahead. Your understanding is appreciated.</p>
</td>
EOT;
        
        public function __construct()
        {
            $this->initialize();

            $this->content = $this->getTemplate(self::JOBSEEKER_REGISTRATION);

            $linkStyle = 'style="font-family: Arial, Helvetica, sans-serif;font-size: 11px;font-style: italic;font-weight: normal;font-variant: normal;text-transform: none;color: #FFFFFF;text-decoration: underline;"';

            $sideBarContents = array();
            $sideBarContents[] = $this->getSidebarItem("1. Create a Job Specification", "Create a job specification to find ".
                "your <strong>perfect jobseeker</strong>. With 15 criteria to choose from, you'll be able to the perfect jobseeker in <strong>seconds!</strong><br/><br/>".
                "<a href='".$this->siteURL."#/recruiter/job-specifications' $linkStyle>Go to 'Job Specifications' page</a>");

            $sideBarContents[] = $this->getSidebarItem("2. View your Matches", "With our revolutionary <strong>Match+Rank<sup>TM </sup></strong> technology, ".
                "the most relevant jobseekers based on your job specifications will <strong>come to you</strong><br/><br/>".
                "<a href='".$this->siteURL."#/recruiter/match-results' $linkStyle>Go to 'Match Results' page</a>");

            $sideBarContents[] = $this->getSidebarItem("3. Review your Purchases", "Once you have found the perfect jobseekers ".
                "and purchased their InfoPack, you can view them all in one convenient page.<br/><br/>".
                "<a href='".$this->siteURL."#/recruiter/purchases' $linkStyle>Go to 'Purchases' page</a>");

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