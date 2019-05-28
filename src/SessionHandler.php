<?php
/**
 * Created by PhpStorm.
 * User: matthias
 * Date: 08.05.19
 * Time: 15:40
 */

namespace Phore\Session;


use Phore\Flash\Flash;

class SessionHandler
{

    const SESS_STR_LEN = 128;

    /**
     * @var Flash
     */
    private $flash;


    private $options = [
        "session_hard_ttl" => 86400 * 30,
        "session_soft_ttl" => 86400 * 5,
        "session_exp_ttl" => 3600
    ];


    private $debugLog = [];

    private $sessionCookieName;

    /**
     * Session constructor.
     *
     * <example>
     *
     * </example>
     *
     * @param $sessionHandler   Flash|string
     */
    public function __construct($sessionHandler, array $allowClasses=[], $sessionCookieName="PSID")
    {
        if ($sessionHandler instanceof Flash) {
            $this->flash = $sessionHandler;
        } else if (is_string($sessionHandler)) {
            $this->flash = new Flash($sessionHandler);
        } else {
            throw new \InvalidArgumentException("Invalid argument in parameter 1.");
        }
        $this->sessionCookieName = $sessionCookieName;
        $this->flash->allowClass($allowClasses);
    }

    /**
     * Whitelist Classes for serialisation
     *
     * @param $classes
     * @return SessionHandler
     */
    public function allowClass($classes) : self
    {
        $this->flash->allowClass($classes);
        return $this;
    }


    public function getFlash() : Flash
    {
        return $this->flash;
    }


    private function log ($msg) {
        $this->debugLog[] = $msg;
    }


    protected function _getSessionIdFromRequest() : ?string
    {
        $sessId = null;
        if (isset ($_COOKIE[$this->sessionCookieName])) {
            $sessId = $_COOKIE[$this->sessionCookieName];
            $this->log("Found cookie in" . $this->sessionCookieName);

        }
        if ($sessId === null) {
            $this->log("null -> sessionId === null");
            return null;
        }

        if (strlen($sessId) !== self::SESS_STR_LEN || ! ctype_alnum($sessId)) {
            $this->log("Strlen or ctype_alnum() mismatch.");
            return null;
        }
        return $sessId;
    }


    private function _loadSession(string $sessionStoreKey) : ?Session
    {
        $data = $this->flash->withKey($sessionStoreKey)->get();
        if ($data === null) {
            $this->log("cannot find sessionid in flash");
            return null;
        }
        $this->log("sessiondata ($sessionStoreKey):"  . print_r($data, true));

        $createTs = (int)phore_pluck("create_ts", $data, 0);
        $lastSeen = (int)phore_pluck("last_seen_ts", $data, 0);

        if ($createTs < time() - $this->options["session_hard_ttl"]) {
            $this->flash->withKey($sessionStoreKey)->del();
            $this->log("hard ttl timeout");
            return null;
        }

        if ($lastSeen < time() - $this->options["session_soft_ttl"]) {
            $this->flash->withKey($sessionStoreKey)->del();
            $this->log("soft_ttl timeout");
            return null;
        }

        $session = new Session($this, $sessionStoreKey, $data);
        return $session;
    }

    private function _createSession() : Session
    {
        $this->log("_createSession() called");
        $newSessionId = phore_random_str(self::SESS_STR_LEN);
        setcookie($this->sessionCookieName, $newSessionId, 0, "/", "", false, true);

        $sessionStoreKey = $this->_getStoreKey($newSessionId);

        $this->flash
            ->withKey($sessionStoreKey)
            ->withTTL($this->options["session_hard_ttl"])
            ->set([
                "create_ts" => time(),
                "last_seen_ts" => time()
            ]);

        $session = $this->_loadSession($sessionStoreKey);
        if ($session === null)
            throw new \InvalidArgumentException("Internal error: Can't persist new session.");
        return $session;
    }


    /**
     * Called from Session::update() to store the changed data.
     *
     * @param string $sessionStoreKey
     * @param array $sessionData
     * @internal
     */
    public function __updateSession(string $sessionStoreKey, array $sessionData)
    {
        $this->flash
            ->withKey($sessionStoreKey)
            ->update($sessionData);
    }


    protected function _getStoreKey (string $sessioId) : string
    {
        return phore_hash($sessioId, true);
    }


    public function loadSession() : Session
    {
        $sessionId = $this->_getSessionIdFromRequest();
        if ($sessionId === null) {
            return $this->_createSession();
        }
        $sessionStoreKey = $this->_getStoreKey($sessionId);
        $sess = $this->_loadSession($sessionStoreKey);
        if ($sess === null)
            $sess = $this->_createSession();
        return $sess;
    }


    public function destroy(Session $session) : Session
    {
        $this->flash->withKey($session->getSessionStoreKey())->del();
        return $this->_createSession();
    }


    public function loadSessionByStoreKey(string $storeKey) : ?Session
    {
        return $this->_loadSession($storeKey);
    }
}
