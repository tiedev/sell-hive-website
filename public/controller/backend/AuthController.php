<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface as Logger;
use Respect\Validation\Validator as v;
use Noodlehaus\Config;

class AuthController
{
    /**
     * @OA\Get(
     *     path="/backend/auth",
     *     summary="session for user exists?",
     *     @OA\Response(
     *         response="200",
     *         description="answer",
     *         @OA\JsonContent(
     *             @OA\Property(property="authenticated", type="boolean"),
     *             @OA\Property(property="admin", type="boolean")
     *        )
     *     )
     * )
     */
    public function isAuthenticated(Request $request, Response $response, Logger $logger)
    {
        $logger->debug('=== AuthController:isAuthenticated(...) ===');

        $out = array();

        $out['authenticated'] = isset($_SESSION['user']);
        $out['admin'] = isset($_SESSION['user']) && $_SESSION['user'] == -1;

        $logger->debug('output', $out);

        return $response->withJson($out, 200, JSON_PRETTY_PRINT);
    }

    /**
     * @OA\Post(
     *     path="/backend/auth",
     *     summary="create session when mail address and password are correct",
     *     @OA\Parameter(name="data",in="query",
     *         @OA\JsonContent(
     *             @OA\Property(property="mail", type="string"),
     *             @OA\Property(property="password", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="login succesfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="login", type="boolean"),
     *             @OA\Property(property="admin", type="boolean")
     *         )
     *     ),
     *     @OA\Response(response="400", description="login failed")
     * )
     */
    public function login(Request $request, Response $response, Logger $logger, Config $config)
    {
        $logger->debug('=== AuthController:login(...) ===');

        $out = array();
        $out['login'] = true;
        $out['admin'] = false;

        $in = $request->getParsedBody();

        $logger->debug('input', isset($in) ? $in : array());

        if (v::not(v::keySet(v::key('mail'), v::key('password')))->validate($in)) {
            $logger->debug('missing input "mail" and/or "password"');
            return $response->withStatus(400);
        }

        if (v::equals('admin')->validate($in['mail'])) {
            $logger->debug('is admin login');
            $out['admin'] = $config->get('admin.active');
        } elseif (v::email()->length(1, 254)->validate($in['mail'])) {
            $logger->debug('is user login');
            $out['mail'] = 'valid';
        } else {
            $logger->debug('invalid mail');
            $out['login'] = false;
        }

        if (!v::alnum()->length(1, 64)->validate($in['password'])) {
            $logger->debug('invalid password');
            $out['login'] = false;
        }

        if ($out['login'] && $out['admin']) {
            if (v::equals($config->get('admin.password'))->validate($in['password'])) {
                $_SESSION['user'] = -1;
                $logger->debug('admin login success');
            } else {
                $out['login'] = false;
                $logger->debug('admin login failed');
            }
        } elseif ($out['login']) {
            $seller = SellerQuery::create()->filterByMail($in['mail'])->filterByPassword($in['password'])->findOne();
            if ($seller == null) {
                $out['login'] = false;
                $logger->debug('user login failed');
            } else {
                $_SESSION['user'] = $seller->getId();
                $logger->debug('user login success');
            }
        } else {
            $logger->debug('login invalid');
        }

        $logger->debug('output', $out);

        return $response->withJson($out, 200, JSON_PRETTY_PRINT);
    }

    /**
     * @OA\Delete(
     *     path="/backend/auth",
     *     summary="destroy session from active user",
     *     @OA\Response(response="200", description="logged out successfully or logout not required")
     * )
     */
    public function logout(Request $request, Response $response, Logger $logger)
    {
        $logger->debug('=== AuthController:logout(...) ===');

        if (isset($_SESSION['user'])) {
            session_destroy();
            $logger->debug('logout success');
            return $response->withStatus(200);
        } else {
            $logger->debug('logout not required');
            return $response->withStatus(200);
        }
    }

    /**
     * @OA\Post(
     *     path="/backend/auth/remind",
     *     summary="generate new password for given mail address (and correct captcha)",
     *     @OA\Parameter(name="data",in="query",
     *         @OA\JsonContent(
     *             @OA\Property(property="mail", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="login succesfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="mailed", type="boolean")
     *         )
     *     )
     * )
     */
    public function remind(Request $request, Response $response, Logger $logger, Config $config, MailService $mail)
    {
        $logger->debug('=== AuthController:remind(...) ===');

        /* TODO : recaptcha prüfen
        $recaptcha = new \ReCaptcha\ReCaptcha($secret);
        $resp = $recaptcha->verify($gRecaptchaResponse, $remoteIp);
        if ($resp->isSuccess()) {
            // verified!
        } else {
            $errors = $resp->getErrorCodes();
        }
        */

        $out = array();
        $out['mailed'] = false;

        $in = $request->getParsedBody();

        $logger->debug('input', isset($in) ? $in : array());

        if (v::email()->length(1, 254)->validate($in['mail'])) {
            $seller = SellerQuery::create()->filterByMail($in['mail'])->findOne();

            if ($seller == null) {
                $logger->debug('seller not found');
            } else {
                $seller->genPassword();
                $seller->save();

                $out['mailed'] = $mail->sendPasswordReminderToSeller($seller);
            }
        }

        $logger->debug('output', $out);

        return $response->withJson($out, 200, JSON_PRETTY_PRINT);
    }
}
