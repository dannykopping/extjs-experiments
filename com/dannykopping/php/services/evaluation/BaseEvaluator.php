<?php
    class BaseEvaluator
    {
        public static $errors = array();
		public static $warnings = array();

		const NOT_IMPORTANT = 0;
		const MILDLY_IMPORTANT = 1;
		const IMPORTANT = 2;
		const MODERATELY_IMPORTANT = 3;
		const VERY_IMPORTANT = 4;

		public static function addError($data)
		{
			self::$errors[] = $data;

			//NetDebug::trace("Error in ".__CLASS__);
			self::printProblem($data);
		}

		protected static function addWarning($data)
		{
			self::$warnings[] = $data;

			//NetDebug::trace("Warning in ".__CLASS__);
			self::printProblem($data);
		}

		private static function printProblem($data)
		{
			if(is_scalar($data))
			{
				//NetDebug::trace($data);
			}
			else
			{
				//NetDebug::trace(">>>TRACE:");
				//foreach($data as $d)
					//NetDebug::trace($d);
			}
		}

		protected static function getInformation($field, User $user)
		{
			$attributes = $user->userInformations;
			foreach($attributes as $attribute)
			{
				if($attribute->type == $field)
					return $attribute;
			}

			return null;
		}
    }
?>