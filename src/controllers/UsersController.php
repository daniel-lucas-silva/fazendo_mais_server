<?php

namespace App\Controllers;

use App\Common\Controller;
use App\Models\Users;
use App\Models\UserAccess;
use App\ResponseException;
use Exception;
use Phalcon\Mvc\Model;

class UsersController extends Controller {

    /**
     * @param string $username
     */
    private function checkForbiddenUsername($username)
    {
        $username = trim($username);
        if ($username == 'admin') {
            $this->buildErrorResponse(409, 'common.COULD_NOT_BE_CREATED');
        }
    }

    /**
     * @param $username
     * @throws ResponseException
     */
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
            throw new ResponseException(409, 'profile.ANOTHER_USER_ALREADY_REGISTERED_WITH_THIS_USERNAME');
        }
    }

    /**
     * @param $email
     * @param $new_password
     * @param $username
     * @param $firstname
     * @param $lastname
     * @param $level
     * @param $phone
     * @param $mobile
     * @param $address
     * @param $city
     * @param $country
     * @param $birthday
     * @param int $authorised
     * @return Users
     * @throws ResponseException
     */
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

    /**
     * @param $user
     * @return mixed
     * @throws Exception
     */
    private function findUserLastAccess($user)
    {
        $conditions = 'username = :username:';
        $parameters = array(
            'username' => $user['username'],
        );
        $last_access = UserAccess::find(
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
        return false;
    }

    /**
     * @param $user
     * @param $firstname
     * @param $lastname
     * @param $birthday
     * @param $email
     * @param $level
     * @param $phone
     * @param $mobile
     * @param $address
     * @param $city
     * @param $country
     * @param int $authorised
     * @return Users
     * @throws ResponseException
     */
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

    /**
     * @param $new_password
     * @param $user
     * @throws ResponseException
     */
    private function setNewPassword($new_password, $user)
    {
        $user->password = password_hash($this->request->getPut('new_password'), PASSWORD_BCRYPT);
        $this->tryToSaveData($user, 'common.COULD_NOT_BE_UPDATED');
    }

    /**
     * @param $headers
     * @return bool
     * @throws ResponseException
     */
    private function checkIfHeadersExist($headers)
    {
        if(!(!isset($headers['Authorization']) || empty($headers['Authorization'])))
            return true;

        throw new ResponseException(403, 'common.HEADER_AUTHORIZATION_NOT_SENT');
    }

    /**
     * @param $credentials
     * @return Model
     * @throws ResponseException
     */
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
        if ($user) return $user;

        throw new ResponseException(404, 'login.USER_IS_NOT_REGISTERED');
    }

    /**
     * @param $credentials
     * @return mixed
     */
    private function getUserPassword($credentials)
    {
        return $credentials['password'];
    }

    /**
     * @param $user
     * @return bool
     * @throws ResponseException
     * @throws Exception
     */
    private function checkIfUserIsNotBlocked($user)
    {
        $block_expires = strtotime($user->block_expires);
        $now = strtotime($this->getNowDateTime());

        if($block_expires > $now)
            throw new ResponseException(403, 'login.USER_BLOCKED');
        else
            return true;
    }

    /**
     * @param $user
     * @return bool|void
     */
    private function checkIfUserIsAuthorized($user)
    {
        return ($user->authorised == 0) ? $this->buildErrorResponse(403, 'login.USER_UNAUTHORIZED') : true;
    }

    /**
     * @param $user
     * @return int
     * @throws ResponseException
     */
    private function addOneLoginAttempt($user)
    {
        $user->login_attempts = $user->login_attempts + 1;
        $this->tryToSaveData($user);
        return $user->login_attempts;
    }

    /**
     * @param $minutes
     * @param $user
     * @throws ResponseException
     * @throws Exception
     */
    private function addXMinutesBlockToUser($minutes, $user)
    {
        $user->block_expires = $this->getNowDateTimePlusMinutes($minutes);
        if ($this->tryToSaveData($user)) {
            $this->buildErrorResponse(400, 'login.TOO_MANY_FAILED_LOGIN_ATTEMPTS');
        }
    }

    /**
     * @param $password
     * @param $user
     * @throws ResponseException
     */
    private function checkPassword($password, $user)
    {
        if (!password_verify($password, $user->password)) {
            $login_attempts = $this->addOneLoginAttempt($user);
            if($login_attempts <= 4)
                throw new ResponseException(400, 'login.WRONG_USER_PASSWORD');
            else
                $this->addXMinutesBlockToUser(120, $user);
        }
    }

    /**
     * @param $password
     * @param $user
     * @throws ResponseException
     */
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

    /**
     * @param $user
     * @return array
     */
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

    /**
     * @param $user
     * @return array
     * @throws Exception
     */
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

    /**
     * @param $user
     * @throws ResponseException
     */
    private function resetLoginAttempts($user)
    {
        $user->login_attempts = 0;
        $this->tryToSaveData($user);
    }

    /**
     * @param $user
     * @throws ResponseException
     * @throws Exception
     */
    private function registerNewUserAccess($user)
    {
        $headers = $this->request->getHeaders();
        $newAccess = new UserAccess();
        $newAccess->username = $user->username;
        $newAccess->ip = (isset($headers['Http-Client-Ip']) || !empty($headers['Http-Client-Ip'])) ? $headers['Http-Client-Ip'] : $this->request->getClientAddress();
        $newAccess->domain = (isset($headers['Http-Client-Domain']) || !empty($headers['Http-Client-Domain'])) ? $headers['Http-Client-Domain'] : gethostbyaddr($this->request->getClientAddress());
        $newAccess->country = (isset($headers['Http-Client-Country']) || !empty($headers['Http-Client-Country'])) ? $headers['Http-Client-Country'] : (isset($_SERVER['HTTP_CF_IPCOUNTRY'])) ? $_SERVER['HTTP_CF_IPCOUNTRY'] : 'XX';
        $newAccess->browser = $this->request->getUserAgent();
        $newAccess->date = $this->getNowDateTime();
        $this->tryToSaveData($newAccess);
    }


    public function login()
    {
        try {
            $this->initializePost();

            $this->checkIfHeadersExist($this->request->getHeaders());

            $user = $this->findUser($this->request->getBasicAuth());
            $password = $this->getUserPassword($this->request->getBasicAuth());
            $this->checkIfUserIsNotBlocked($user);
            $this->checkIfUserIsAuthorized($user);
            $this->checkPassword($password, $user);
            // ALL OK, proceed to login
            $this->checkIfPasswordNeedsRehash($password, $user);
            $user_data = $this->buildUserData($user);
            $token = $this->encodeToken($this->buildTokenData($user));
            $data = [
                'token' => $token,
                'user' => $user_data
            ];
            $this->resetLoginAttempts($user);
            $this->registerNewUserAccess($user);

            $this->buildSuccessResponse(200, 'common.SUCCESSFUL_REQUEST', $data);
        }
        catch (ResponseException $e) {
            $this->buildErrorResponse($e->getCode(), $e->getMessage(), $e->getData());
        } catch (Exception $e) {
            $this->buildErrorResponse(500, $e->getMessage());
        }
    }

    /**
     * Register a new user
     */
    public function create() {
        try {
            $this->initializePost();
            $this->checkForEmptyData([$this->request->getPost('username'), $this->request->getPost('firstname'), $this->request->getPost('new_password'), $this->request->getPost('email')]);
            $this->checkForbiddenUsername($this->request->getPost('username'));
            $this->checkIfUsernameAlreadyExists($this->request->getPost('username'));
            $user = $this->createUser($this->request->getPost('email'), $this->request->getPost('new_password'), $this->request->getPost('username'), $this->request->getPost('firstname'), $this->request->getPost('lastname'), $this->request->getPost('level'), $this->request->getPost('phone'), $this->request->getPost('mobile'), $this->request->getPost('address'), $this->request->getPost('city'), $this->request->getPost('country'), $this->request->getPost('birthday'));
            $user = $user->toArray();
            $user = $this->unsetPropertyFromArray($user, ['password', 'block_expires', 'login_attempts']);
            $this->registerLog();
            $this->buildSuccessResponse(201, 'common.CREATED_SUCCESSFULLY', $user);
        }
        catch (ResponseException $e) {
            $this->buildErrorResponse($e->getCode(), $e->getMessage(), $e->getData());
        } catch (Exception $e) {
            $this->buildErrorResponse(500, $e->getMessage());
        }
    }

    public function fetch() {
        try {
            $this->initializeGet();
            $options = $this->buildOptions('firstname asc, lastname asc', $this->request->get('sort'), $this->request->get('order'), $this->request->get('limit'), $this->request->get('offset'));
            $filters = $this->buildFilters($this->request->get('filter'));
            $cities = $this->findElements('Users', $filters['conditions'], $filters['parameters'], 'id, firstname, lastname, level, email, phone, mobile, address, country, city, birthday, authorised', $options['order_by'], $options['offset'], $options['limit']);
            $total = $this->calculateTotalElements('Users', $filters['conditions'], $filters['parameters']);
            $data = $this->buildListingObject($cities, $options['rows'], $total);
            $this->buildSuccessResponse(200, 'common.SUCCESSFUL_REQUEST', $data);
        }
        catch (ResponseException $e) {
            $this->buildErrorResponse($e->getCode(), $e->getMessage(), $e->getData());
        } catch (Exception $e) {
            $this->buildErrorResponse(500, $e->getMessage());
        }
    }

    public function get($id) {
        try {
            $this->initializeGet();
            $user = $this->findElementById('Users', $id);
            $user = $user->toArray();
            $user = $this->unsetPropertyFromArray($user, ['password', 'block_expires', 'login_attempts']);
            $user = $this->findUserLastAccess($user);
            $this->buildSuccessResponse(200, 'common.SUCCESSFUL_REQUEST', $user);
        }
        catch (ResponseException $e) {
            $this->buildErrorResponse($e->getCode(), $e->getMessage(), $e->getData());
        } catch (Exception $e) {
            $this->buildErrorResponse(500, $e->getMessage());
        }
    }

    public function update($id) {
        try {
            $this->initializePatch();
            $this->checkForEmptyData([$this->request->getPut('firstname'), $this->request->getPut('authorised')]);
            $user = $this->updateUser($this->findElementById('Users', $id), $this->request->getPut('firstname'), $this->request->getPut('lastname'), $this->request->getPut('birthday'), $this->request->getPut('email'), $this->request->getPut('level'), $this->request->getPut('phone'), $this->request->getPut('mobile'), $this->request->getPut('address'), $this->request->getPut('city'), $this->request->getPut('country'), $this->request->getPut('authorised'));
            $user = $user->toArray();
            $user = $this->unsetPropertyFromArray($user, ['password', 'block_expires', 'login_attempts']);
            $this->registerLog();
            $this->buildSuccessResponse(200, 'common.UPDATED_SUCCESSFULLY', $user);
        }
        catch (ResponseException $e) {
            $this->buildErrorResponse($e->getCode(), $e->getMessage(), $e->getData());
        } catch (Exception $e) {
            $this->buildErrorResponse(500, $e->getMessage());
        }
    }

    public function changePassword($id) {
        try {
            $this->initializePatch();
            $this->checkForEmptyData([$this->request->getPut('new_password')]);
            $user = $this->findElementById('Users', $id);
            $this->setNewPassword($this->request->getPut('new_password'), $user);
            $this->registerLog();
            $this->buildSuccessResponse(200, 'change-password.PASSWORD_SUCCESSFULLY_UPDATED');
        }
        catch (ResponseException $e) {
            $this->buildErrorResponse($e->getCode(), $e->getMessage(), $e->getData());
        } catch (Exception $e) {
            $this->buildErrorResponse(500, $e->getMessage());
        }
    }

    public function delete($id) {
        try {
            $this->initializeDelete();
            $this->tryToDeleteData($this->findElementById('Users', $id));
            $this->registerLog();
            $this->buildSuccessResponse(200, 'common.DELETED_SUCCESSFULLY');
        }
        catch (ResponseException $e) {
            $this->buildErrorResponse($e->getCode(), $e->getMessage(), $e->getData());
        } catch (Exception $e) {
            $this->buildErrorResponse(500, $e->getMessage());
        }
    }
}