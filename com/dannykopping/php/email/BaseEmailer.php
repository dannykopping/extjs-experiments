<?php

    require_once(dirname(__FILE__)."/EmailElements.php");

    class BaseEmailer
    {
        const JOBSEEKER_REGISTRATION = "jobseeker-registration";
        const RECRUITER_REGISTRATION = "recruiter-registration";
        const PASSWORD_RESET = "password-reset";
        const SUMMARY_REPORT = "summary-report";

        const HOST = "HOST";

        const DATE = "DATE";
        const HEADER = "HEADER";

        const SIDEHEADING = "SIDEHEADING";

        const TITLE = "TITLE";
        const CONTENT = "CONTENT";
        const SIDEBAR = "SIDEBAR";


        protected $templatesDirectory;

        protected $templateContents;
        protected $host;
        protected $siteURL;

        public function initialize()
        {
            $this->host = "http://".ConfigXml::getInstance()->config->paths->host;
            $this->siteURL = ConfigXml::getInstance()->config->paths->{"site-url"};
            $this->templatesDirectory = realpath(dirname(__FILE__) . "/../templates/");
        }

        protected function getTemplate($name)
        {
            switch ($name)
            {
                case self::JOBSEEKER_REGISTRATION:
                    return file_get_contents($this->templatesDirectory . "/base.php");
                    break;
                case self::RECRUITER_REGISTRATION:
                    return file_get_contents($this->templatesDirectory . "/base.php");
                    break;
                case self::PASSWORD_RESET:
                    return file_get_contents($this->templatesDirectory . "/base.php");
                    break;
                case self::SUMMARY_REPORT:
                    return file_get_contents($this->templatesDirectory . "/base.php");
                    break;
                default:
                    throw new Exception("Could not find template");
                    break;
            }
        }

        /**
         * Handle general things like date and hostname
         *
         * @param $content
         * @return mixed
         */
        protected function processBeforeSending($content)
        {
            $content = $this->replace(self::DATE, date("d F Y", time()), $content);

            return $this->replace(self::HOST, $this->host, $content);
        }

        protected function getContentItem($title, $content)
        {
            $raw = EmailElements::CONTENT_ITEM;

            $raw = $this->replace(self::TITLE, $title, $raw);
            $raw = $this->replace(self::CONTENT, $content, $raw);

            return $raw;
        }

        protected function getSidebarItem($title, $content, $appendSeparator=true)
        {
            $raw = EmailElements::SIDEBAR_ITEM;

            $raw = $this->replace(self::TITLE, $title, $raw);
            $raw = $this->replace(self::CONTENT, $content, $raw);

            if($appendSeparator)
            {
                $raw .= "\n".EmailElements::SIDEBAR_ITEM_SEPARATOR;
            }

            return $raw;
        }

        protected function replace($field, $value, $content)
        {
            return str_replace("{{".$field."}}", $value, $content);
        }
    }

?>