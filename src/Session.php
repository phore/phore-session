<?php
/**
 * Created by PhpStorm.
 * User: matthias
 * Date: 06.05.19
 * Time: 13:13
 */

namespace Phore\Session;


use Phore\Flash\Flash;

class Session
{
    /**
     * @var SessionHandler
     */
    private $sessionHandler;

    /**
     * The key, this session is stored in flash
     *
     * The real sessionId is never stored so owned data won't
     * lead to session highjacking.
     *
     * @var string
     */
    private $sessionStoreKey;
    private $sessionData = [];

    public function __construct(SessionHandler $sessionHandler, string $sessionStoreKey, array $sessionData)
    {
        $this->sessionHandler = $sessionHandler;
        $this->sessionStoreKey = $sessionStoreKey;
        $this->sessionData = $sessionData;
    }

    public function getSessionStoreKey() : string
    {
        return $this->sessionStoreKey;
    }

    public function setInfoMessage(string $message)
    {
        $this->sessionData["_flash_message"] = $message;
    }

    public function getInfoMessage() : ?string
    {
        if (isset($this->sessionData["_flash_message"])) {
            $msg = $this->sessionData["_flash_message"];
            $this->sessionData["_flash_message"] = null;
            return $msg;
        }
        return null;
    }

    public function set(string $key, $value)
    {
        if ( ! $this->sessionHandler->getFlash()->isAllowed($value))
            throw new \InvalidArgumentException("Object of class '". get_class($value) . " are not allowed. Add to allowed classes by calling allowClass().");
        $this->sessionData[$key] = $value;
    }

    public function get(string $key, $default=null)
    {
        if ( ! isset ($this->sessionData[$key])) {
            if ($default instanceof \Exception)
                throw $default;
            return $default;
        }
        return $this->sessionData[$key];
    }


    public function setSignInUserId(string $id = null)
    {
        $this->sessionData["_signin_user_id"] = $id;
    }

    public function getSignInUserId() : ?string
    {
        if ( ! isset ($this->sessionData["_signin_user_id"]) || $this->sessionData["_signin_user_id"] == "")
            return null;
        return $this->sessionData["_signin_user_id"];
    }

    public function setOauthToken(string $token)
    {
        $this->sessionData["_oauth_token"] = $token;
    }

    public function getOauthToken() : ?string
    {
        if ( ! isset ($this->sessionData["_oauth_token"]))
            return null;
        return $this->sessionData["_oauth_token"];
    }

    public function destroy()
    {
        $this->sessionHandler->destroy($this);
    }


    public function update()
    {
        $this->sessionHandler->__updateSession($this->sessionStoreKey, $this->sessionData);
    }

    public function __destruct()
    {
        $this->update();
    }
}
