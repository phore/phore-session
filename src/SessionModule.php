<?php
/**
 * Created by PhpStorm.
 * User: matthias
 * Date: 29.05.19
 * Time: 10:23
 */

namespace Phore\Session;


use Phore\Flash\Flash;
use Phore\MicroApp\App;
use Phore\MicroApp\AppModule;

class SessionModule implements AppModule
{

    private $allowClasses;

    public function __construct(array $allowClasses = [])
    {
        $this->allowClasses = $allowClasses;
    }

    /**
     * Called just after adding this to a app by calling
     * `$app->addModule(new SomeModule());`
     *
     * Here is the right place to add Routes, etc.
     *
     * @param App $app
     *
     * @return mixed
     */
    public function register(App $app)
    {

        if ( ! $app->isResolvable("flash"))
            throw new \InvalidArgumentException("Session Handler requires FlashModule registered");

        $app->define("sessionHandler", function (Flash $flash) {
            $handler = new SessionHandler($flash->withPrefix("PSES"), $this->allowClasses);
            return $handler;
        });

        $app->define("session", function (SessionHandler $sessionHandler) {
            return $sessionHandler->loadSession();
        });
    }
}
