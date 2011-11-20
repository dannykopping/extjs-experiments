<?php

    require_once(dirname(__FILE__)."/BaseEmailer.php");

    class PasswordResetEmailer extends BaseEmailer
    {
        private $content;

        const LINK = "LINK";

        private $resetMessage = <<<EOT
<td width="100%" style="padding-top: 20px;">
<p style="font-family: Arial, Helvetica, sans-serif;font-size: 20px;font-style: normal;font-weight: normal;font-variant: normal;text-transform: none;color: #282651;text-decoration: none;"><strong>GetTheJob.co.za Password Reset</strong></p>
<p style="font-family: Arial, Helvetica, sans-serif; font-size: 12px; font-style: normal; font-weight: normal; font-variant: normal; text-transform: none; color: #282651; text-decoration: none;">You have recently requested a <em>password reset</em> on <a href="http://GetTheJob.co.za">GetTheJob.co.za</a>. If you did not issue this request, you can ignore this email and your password will remain the same.</p>
<p style="font-family: Arial, Helvetica, sans-serif; font-size: 12px; font-style: normal; font-weight: normal; font-variant: normal; text-transform: none; color: #282651; text-decoration: none;">If you would like to change your password, please click <a href="{{LINK}}">here</a> or enter the following link into your browser:</p>
<p style="font-family: Arial, Helvetica, sans-serif; font-size: 12px; font-style: normal; font-weight: normal; font-variant: normal; text-transform: none; color: #282651; text-decoration: none;">{{LINK}}</p>
<br><p style="font-family: Arial, Helvetica, sans-serif; font-size: 12px; font-style: normal; font-weight: normal; font-variant: normal; text-transform: none; color: #282651; text-decoration: none;">Your GetTheJob.co.za Team</p>
</td>
EOT;
        
        public function __construct($emailAddress)
        {
            $this->initialize();

            $this->content = $this->getTemplate(self::PASSWORD_RESET);

            $linkStyle = 'style="font-family: Arial, Helvetica, sans-serif;font-size: 11px;font-style: italic;font-weight: normal;font-variant: normal;text-transform: none;color: #FFFFFF;text-decoration: underline;"';

            $sideBarContents = array();

            $salt = "gtjsaltyemail";
            $url = ConfigXml::getInstance()->config->paths->{'site-url'}.
                   "reset-password.php?e=$emailAddress&s=".sha1($emailAddress.":".$salt);

            $this->resetMessage = $this->replace(self::LINK, $url, $this->resetMessage);
            $this->content = $this->replace(self::CONTENT, $this->resetMessage, $this->content);
            $this->content = $this->replace(self::SIDEBAR, implode("\n", $sideBarContents), $this->content);

            $this->content = $this->replace(self::SIDEHEADING, "", $this->content);
            $this->content = $this->replace(self::HEADER, "GetTheJob.co.za - Password Reset", $this->content);
        }

        public function getContent()
        {
            return $this->processBeforeSending($this->content);
        }
    }
?>