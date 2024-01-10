<?php /** @noinspection PhpUnused */

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface as Logger;
use Respect\Validation\Validator as v;
use Noodlehaus\Config;

class AuthController
{
    private Logger $logger;
    private Config $config;
    private MailService $mail;

    public function __construct(Logger $logger, Config $config, MailService $mail)
    {
        $this->logger = $logger;
        $this->config = $config;
        $this->mail = $mail;
    }

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
    public function isAuthenticated(Request $request, Response $response): Response
    {
        $this->logger->debug('=== AuthController:isAuthenticated(...) ===');

        $out = array();

        $out['authenticated'] = isset($_SESSION['user']);
        $out['admin'] = isset($_SESSION['user']) && $_SESSION['user'] == -1;

        $this->logger->debug('output', $out);

        $response->getBody()->write(json_encode($out, JSON_PRETTY_PRINT));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
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
    public function login(Request $request, Response $response)
    {
        $this->logger->debug('=== AuthController:login(...) ===');

        $out = array();
        $out['login'] = true;
        $out['admin'] = false;

        $in = $request->getParsedBody();

        $this->logger->debug('input', $in ?? array());

        if (v::not(v::keySet(v::key('mail'), v::key('password')))->validate($in)) {
            $this->logger->debug('missing input "mail" and/or "password"');
            return $response->withStatus(400);
        }

        if (v::equals('admin')->validate($in['mail'])) {
            $this->logger->debug('is admin login');
            $out['admin'] = $this->config->get('admin.active');
        } elseif (v::email()->length(1, 254)->validate($in['mail'])) {
            $this->logger->debug('is user login');
            $out['mail'] = 'valid';
        } else {
            $this->logger->debug('invalid mail');
            $out['login'] = false;
        }

        if (!v::alnum()->length(1, 64)->validate($in['password'])) {
            $this->logger->debug('invalid password');
            $out['login'] = false;
        }

        if ($out['login'] && $out['admin']) {
            if (v::equals($this->config->get('admin.password'))->validate($in['password'])) {
                $_SESSION['user'] = -1;
                $this->logger->debug('admin login success');
            } else {
                $out['login'] = false;
                $this->logger->debug('admin login failed');
            }
        } elseif ($out['login']) {
            $seller = SellerQuery::create()->filterByMail($in['mail'])->filterByPassword($in['password'])->findOne();
            if ($seller == null) {
                $out['login'] = false;
                $this->logger->debug('user login failed');
            } else {
                $_SESSION['user'] = $seller->getId();
                $this->logger->debug('user login success');
            }
        } else {
            $this->logger->debug('login invalid');
        }

        $this->logger->debug('output', $out);

        $response->getBody()->write(json_encode($out, JSON_PRETTY_PRINT));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }

    /**
     * @OA\Delete(
     *     path="/backend/auth",
     *     summary="destroy session from active user",
     *     @OA\Response(response="200", description="logged out successfully or logout not required")
     * )
     */
    public function logout(Request $request, Response $response)
    {
        $this->logger->debug('=== AuthController:logout(...) ===');

        if (isset($_SESSION['user'])) {
            session_destroy();
            $this->logger->debug('logout success');
            return $response->withStatus(200);
        } else {
            $this->logger->debug('logout not required');
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
    public function remind(Request $request, Response $response)
    {
        $this->logger->debug('=== AuthController:remind(...) ===');

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

        $this->logger->debug('input', isset($in) ? $in : array());

        if (v::email()->length(1, 254)->validate($in['mail'])) {
            $seller = SellerQuery::create()->filterByMail($in['mail'])->findOne();

            if ($seller == null) {
                $this->logger->debug('seller not found');
            } else {
                $seller->genPassword();
                $seller->save();

                $out['mailed'] = $this->mail->sendPasswordReminderToSeller($seller);
            }
        }

        $this->logger->debug('output', $out);

        $response->getBody()->write(json_encode($out, JSON_PRETTY_PRINT));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }
}
