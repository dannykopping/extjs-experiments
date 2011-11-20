<?php

    include_once(dirname(__FILE__)."/lib/mpdf/mpdf.php");

    abstract class BasePDF
    {
        protected $pdf;

        public function __construct()
        {
            $this->pdf = new mPDF("c");
            $this->pdf->SetDisplayMode("fullpage");
        }

        public function write($htmlContent)
        {
            $stylesheet = $this->getStylesheet();

            // write stylesheet
            $this->pdf->WriteHTML($stylesheet, 1);

            // write HTML contents
            $this->pdf->WriteHTML($htmlContent);
        }

        public function getContents($documentName)
        {
            // "S" will prompt mPDF to return the raw buffer
            return $this->pdf->Output($documentName, "S");
        }

        protected function replace($field, $value, $content)
        {
            if(is_object($value))
                return $content;

            return str_replace("{{".$field."}}", $value, $content);
        }

        abstract public function getTemplate();

        abstract public function getStylesheet();

        abstract public function generate();
    }

?>