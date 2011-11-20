<?php
	require_once("../../UserService.php");

	abstract class BaseAuthorization
	{
		protected function determineRegistrationStatus($type, $information, $email, $registrationType)
		{
			$accessId = $information->id;

			$u = new User();
			$users = $u->table->findBy("email", $email);

			if(count($users) == 1)
			{
				// a user exists with that email address
				$user = $users[0];
			}

			// user exists and there's an ExternalAccount record associated to it
			if($user)
			{
				if(count($user->externalAccounts) < 1)
				{
					// no ExternalAccount record found, which means the User already exists but the User
					// has used the traditional method to sign up already

					throw new Exception("Another user using email");
				}

				$externalAccount = $user->externalAccounts[0];

				// check that the type and accessId match
				if($externalAccount->type == $type && $externalAccount->accessId == $accessId)
				{
//					$user = $externalAccount->User;
//					$user->password = null;

					// return User - process completes successfully (logged in successfully)
					throw new Exception("Already registered");
				}
				else
				{
					// type and accessId don't match
					if($externalAccount->type != $type && $externalAccount->accessId != $accessId)
					{
						// user has signed up with another provider
						throw new Exception("Another provider");
						return null;
					}

					// type matches, but not accessId
					if($externalAccount->type == $type && $externalAccount->accessId != $accessId)
					{
						// someone else has registered with that email address
						throw new Exception("Another user using email");
					}
				}
			}
			else
			{
				// no match on email, but possible matches on accessId
				$query = Doctrine_Query::create()
					->select("u.*, e.id")
					->from("ExternalAccount e, e.User u")
					->where("e.accessId = '$accessId'")
					->limit(1);

				$results = $query->execute();
				if(count($results) == 1)
				{
					throw new Exception("Another user using account");
				}
				else
				{
					// register
					return $this->importInformation($type, $information, $email, $registrationType);
				}
			}
		}

		protected function checkLogin($type, $id)
		{
			$query = Doctrine_Query::create()
					->select("e.id")
					->from("ExternalAccount e")
					->where("e.accessId = '$id'")
					->andWhere("e.type = '$type'");

			$results = $query->execute();
			if(count($results) == 0)
			{
				throw new Exception("No match");
			}
			else
			{
				$externalAccount = $results[0];
				$user = $externalAccount->User;

				unset($user->password);

				$userService = new UserService();
				$userService->setUserSession($user);
				return $user;
			}
		}

        protected function registerAccount(ExternalAccount $externalAccount)
        {
            $result = $externalAccount->trySave();

            $userService = new UserService();
            $userService->setUserSession($externalAccount->User);

            try
            {
                if($result === true)
                {
                    $userService->sendEmailer();
                    return $externalAccount->User;
                }
                else
                {
                    $externalAccount->save();
                }
            }
            catch(Exception $e)
            {
                // clear the session
                $userService->setUserSession(null);

                throw new Exception($e->getMessage());
            }

            return $externalAccount->User;
        }

		abstract protected function importInformation($type, $information, $email, $registrationType);
	}
?>