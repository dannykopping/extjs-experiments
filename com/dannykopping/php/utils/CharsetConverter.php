<?php

    /**
     * @see http://www.php.net/manual/en/function.utf8-encode.php#102382
     */
    class CharsetConverter
    {
        /**
         * Fixes those annoying character encodings from RTF and Word
         *
         * @see http://stackoverflow.com/questions/1262038/how-to-replace-microsoft-encoded-quotes-in-php
         *
         * @static
         * @param $string
         * @return string
         */
        public static function fixString($string)
        {
            return iconv('UTF-8', 'ASCII//TRANSLIT', $string);
        }
    }

?>