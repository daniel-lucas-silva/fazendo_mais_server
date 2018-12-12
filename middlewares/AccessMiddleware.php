<?php

use Phalcon\Mvc\Micro\MiddlewareInterface;

class AccessMiddleware extends ControllerBase implements MiddlewareInterface
{
    public function call(Phalcon\Mvc\Micro $app)
    {
        include APP_PATH . '/config/acl.php';
        $arrHandler = $app->getActiveHandler();

        $array = (array) $arrHandler[0];
        $nameController = implode("", $array);
        $controller = str_replace('Controller', '', $nameController);
        // get function
        $function = $arrHandler[1];

        if ($controller === 'Index') {
            $allowed = 1;
            return $allowed;
        }

        if (array_key_exists($controller, $arrResources['Guest']) && in_array($function, $arrResources['Guest'][$controller])) {
            $allowed = 1;
            return $allowed;
        }

        $mytoken = $this->getToken();

        if (empty($mytoken) || $mytoken == '') {
            $this->buildErrorResponse(400, "Token não recebido.");
        } else {

            try {

                $token_decoded = $this->decodeToken($mytoken);

                $allowed_access = $acl->isAllowed($token_decoded->user_level, $controller, $arrHandler[1]);
                if (!$allowed_access) {
                    $this->buildErrorResponse(403, "Usuário nao tem permissão à esta função.");
                } else {
                    return $allowed_access;
                }

            } catch (Exception $e) {
                $this->buildErrorResponse(401, "BAD_TOKEN_GET_A_NEW_ONE");
            }
        }
    }
}
