<?php

namespace App\Controllers;

use App\Common\Controller;

class UsersController extends Controller {

    private function checkForbiddenUsername($username)
    {
        $username = trim($username);
        if ($username == 'admin') {
            $this->buildErrorResponse(409, 'common.COULD_NOT_BE_CREATED');
        }
    }

    private function checkIfUsernameAlreadyExists($username)
    {
        // checks if user already exists
        $conditions = 'username = :username:';
        $parameters = array(
            'username' => trim($username),
        );
        $user = Users::findFirst(
            array(
                $conditions,
                'bind' => $parameters,
            )
        );
        if ($user) {
            $this->buildErrorResponse(409, 'profile.ANOTHER_USER_ALREADY_REGISTERED_WITH_THIS_USERNAME');
        }
    }

    private function createUser($email, $new_password, $username, $firstname, $lastname, $level, $phone, $mobile, $address, $city, $country, $birthday, $authorised = 0)
    {
        $user = new Users();
        $user->email = trim($email);
        $user->username = trim($username);
        $user->firstname = trim($firstname);
        $user->lastname = trim($lastname);
        $user->level = trim($level);
        $user->phone = trim($phone);
        $user->mobile = trim($mobile);
        $user->address = trim($address);
        $user->city = trim($city);
        $user->country = trim($country);
        $user->birthday = trim($birthday);
        $user->authorised = trim($authorised);
        $user->password = password_hash($new_password, PASSWORD_BCRYPT);
        $this->tryToSaveData($user, 'common.COULD_NOT_BE_CREATED');
        return $user;
    }

    private function findUserLastAccess($user)
    {
        $conditions = 'username = :username:';
        $parameters = array(
            'username' => $user['username'],
        );
        $last_access = UsersAccess::find(
            array(
                $conditions,
                'bind' => $parameters,
                'columns' => 'date, ip, domain, browser',
                'order' => 'id DESC',
                'limit' => 10,
            )
        );
        if ($last_access) {
            $array = array();
            $user_last_access = $last_access->toArray();
            foreach ($user_last_access as $key_last_access => $value_last_access) {
                $this_user_last_access = array(
                    'date' => $this->utc_to_iso8601($value_last_access['date']),
                    'ip' => $value_last_access['ip'],
                    'domain' => $value_last_access['domain'],
                    'browser' => $value_last_access['browser'],
                );
                $array[] = $this_user_last_access;
            }
            $user = empty($array) ? $this->array_push_assoc($user, 'last_access', '') : $this->array_push_assoc($user, 'last_access', $array);
            return $user;
        }
    }

    private function updateUser($user, $firstname, $lastname, $birthday, $email, $level, $phone, $mobile, $address, $city, $country, $authorised = 0)
    {
        $user->firstname = trim($firstname);
        $user->lastname = trim($lastname);
        $user->birthday = trim($birthday);
        $user->email = trim($email);
        $user->level = trim($level);
        $user->phone = trim($phone);
        $user->mobile = trim($mobile);
        $user->address = trim($address);
        $user->city = trim($city);
        $user->country = trim($country);
        $user->authorised = trim($authorised);
        $this->tryToSaveData($user, 'common.COULD_NOT_BE_UPDATED');
        return $user;
    }

    private function setNewPassword($new_password, $user)
    {
        $user->password = password_hash($this->request->getPut('new_password'), PASSWORD_BCRYPT);
        $this->tryToSaveData($user, 'common.COULD_NOT_BE_UPDATED');
    }

    private function checkIfHeadersExist($headers)
    {
        return (!isset($headers['Authorization']) || empty($headers['Authorization'])) ? $this->buildErrorResponse(403, 'common.HEADER_AUTHORIZATION_NOT_SENT') : true;
    }

    private function findUser($credentials)
    {
        $username = $credentials['username'];
        $conditions = 'username = :username:';
        $parameters = array(
            'username' => $username,
        );
        $user = Users::findFirst(
            array(
                $conditions,
                'bind' => $parameters
            )
        );
        return (!$user) ? $this->buildErrorResponse(404, 'login.USER_IS_NOT_REGISTERED') : $user;
    }

    private function getUserPassword($credentials)
    {
        return $credentials['password'];
    }

    private function checkIfUserIsNotBlocked($user)
    {
        $block_expires = strtotime($user->block_expires);
        $now = strtotime($this->getNowDateTime());
        return ($block_expires > $now) ? $this->buildErrorResponse(403, 'login.USER_BLOCKED') : true;
    }

    private function checkIfUserIsAuthorized($user)
    {
        return ($user->authorised == 0) ? $this->buildErrorResponse(403, 'login.USER_UNAUTHORIZED') : true;
    }

    private function addOneLoginAttempt($user)
    {
        $user->login_attempts = $user->login_attempts + 1;
        $this->tryToSaveData($user);
        return $user->login_attempts;
    }

    private function addXMinutesBlockToUser($minutes, $user)
    {
        $user->block_expires = $this->getNowDateTimePlusMinutes($minutes);
        if ($this->tryToSaveData($user)) {
            $this->buildErrorResponse(400, 'login.TOO_MANY_FAILED_LOGIN_ATTEMPTS');
        }
    }

    private function checkPassword($password, $user)
    {
        if (!password_verify($password, $user->password)) {
            $login_attempts = $this->addOneLoginAttempt($user);
            ($login_attempts <= 4) ? $this->buildErrorResponse(400, 'login.WRONG_USER_PASSWORD') : $this->addXMinutesBlockToUser(120, $user);
        }
    }

    private function checkIfPasswordNeedsRehash($password, $user)
    {
        $options = [
            'cost' => 10, // the default cost is 10, max is 12.
        ];
        if (password_needs_rehash($user->password, PASSWORD_DEFAULT, $options)) {
            $newHash = password_hash($password, PASSWORD_DEFAULT, $options);
            $user->password = $newHash;
            $this->tryToSaveData($user);
        }
    }

    private function buildUserData($user)
    {
        $user_data = array(
            'id' => $user->id,
            'username' => $user->username,
            'email' => $user->email,
            'firstname' => $user->firstname,
            'lastname' => $user->lastname
        );
        return $user_data;
    }

    private function buildTokenData($user)
    {
        // issue at time and expires (token)
        $iat = strtotime($this->getNowDateTime());
        $exp = strtotime('+' . $this->tokenConfig['expiration_time'] . ' seconds', $iat);
        $token_data = array(
            'iss' => $this->tokenConfig['iss'],
            'aud' => $this->tokenConfig['aud'],
            'iat' => $iat,
            'exp' => $exp,
            'username_username' => $user->username,
            'username_firstname' => $user->firstname,
            'username_lastname' => $user->lastname,
            'username_level' => $user->level,
            'rand' => rand() . microtime()
        );
        return $token_data;
    }

    private function resetLoginAttempts($user)
    {
        $user->login_attempts = 0;
        $this->tryToSaveData($user);
    }

    private function registerNewUserAccess($user)
    {
        $headers = $this->request->getHeaders();
        $newAccess = new UsersAccess();
        $newAccess->username = $user->username;
        $newAccess->ip = (isset($headers['Http-Client-Ip']) || !empty($headers['Http-Client-Ip'])) ? $headers['Http-Client-Ip'] : $this->request->getClientAddress();
        $newAccess->domain = (isset($headers['Http-Client-Domain']) || !empty($headers['Http-Client-Domain'])) ? $headers['Http-Client-Domain'] : gethostbyaddr($this->request->getClientAddress());
        $newAccess->country = (isset($headers['Http-Client-Country']) || !empty($headers['Http-Client-Country'])) ? $headers['Http-Client-Country'] : (isset($_SERVER['HTTP_CF_IPCOUNTRY'])) ? $_SERVER['HTTP_CF_IPCOUNTRY'] : 'XX';
        $newAccess->browser = $this->request->getUserAgent();
        $newAccess->date = $this->getNowDateTime();
        $this->tryToSaveData($newAccess);
    }
}