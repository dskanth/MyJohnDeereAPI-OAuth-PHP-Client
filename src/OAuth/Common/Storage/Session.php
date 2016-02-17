<?php

namespace OAuth\Common\Storage;

use OAuth\Common\Token\TokenInterface;
use OAuth\Common\Storage\Exception\TokenNotFoundException;
use OAuth\Common\Storage\Exception\AuthorizationStateNotFoundException;

/**
 * Stores a token in a PHP session.
 */
class Session implements TokenStorageInterface
{
    /**
     * @var bool
     */
    protected $startSession;

    /**
     * @var string
     */
    protected $sessionVariableName;

    /**
     * @var string
     */
    protected $stateVariableName;

    /**
     * @param bool $startSession Whether or not to start the session upon construction.
     * @param string $sessionVariableName the variable name to use within the _SESSION superglobal
     * @param string $stateVariableName
     */
    public function __construct(
        $startSession = true,
        $sessionVariableName = 'lusitanian-oauth-token',
        $stateVariableName = 'lusitanian-oauth-state'
    ) {
        if ($startSession && !$this->sessionHasStarted()) {
            session_start();
        }

        $this->startSession = $startSession;
        $this->sessionVariableName = $sessionVariableName;
        $this->stateVariableName = $stateVariableName;
        if (!isset($_SESSION[$sessionVariableName])) {
            $_SESSION[$sessionVariableName] = array();
        }
        if (!isset($_SESSION[$stateVariableName])) {
            $_SESSION[$stateVariableName] = array();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function retrieveAccessToken($service)
    {
		//echo 'Retrieve!'; exit;
		
		if ($this->hasAccessToken($service)) {
			return unserialize($_SESSION[$this->sessionVariableName][$service]);
        }

        throw new TokenNotFoundException('Token not found in session, are you sure you stored it?');
    }

    /**
     * {@inheritDoc}
     */
    public function storeAccessToken($service, TokenInterface $token)
    {
		//print_r($token); //exit;
		
        $serializedToken = serialize($token);
		
        if (isset($_SESSION[$this->sessionVariableName])
            && is_array($_SESSION[$this->sessionVariableName])
        ) {
            $_SESSION[$this->sessionVariableName][$service] = $serializedToken;
        } else {
            $_SESSION[$this->sessionVariableName] = array(
                $service => $serializedToken,
            );
        }
		
		//print_r($this->sessionVariableName); echo '<br><br>'; print_r($service); echo '<br><br>'; print_r($_SESSION[$this->sessionVariableName]); echo '<br><br>'; exit;
        // allow chaining
        return $this;
    }
	
	// Custom function by shasi, to store the oauth token
	public function store_access_token($service, $token)
    {
		//print_r($token); exit;
		
        if (isset($_SESSION[$this->sessionVariableName])
            && is_array($_SESSION[$this->sessionVariableName])
        ) {
            $_SESSION[$this->sessionVariableName][$service] = $token;
        } else {
            $_SESSION[$this->sessionVariableName] = array(
                $service => $token,
            );
        }
		
		//print_r($this->sessionVariableName); echo '<br><br>'; print_r($service); echo '<br><br>'; print_r($_SESSION[$this->sessionVariableName]); echo '<br><br>'; exit;
        // allow chaining
        return $this;
    }
	
	// Custom function by shasi
	public function retrieve_access_token($service)
    {
		//echo 'Retrieve!'; exit;
        if ($this->hasAccessToken($service)) {
			return $_SESSION[$this->sessionVariableName][$service];
        }

        throw new TokenNotFoundException('OOPS, Token not found in session, are you sure you stored it?');
    }

    /**
     * {@inheritDoc}
     */
    public function hasAccessToken($service)
    {
		//print_r($this->sessionVariableName); echo '<br><br>'; 
		//echo $service; echo '<br><br>'; 
		//print_r($_SESSION[$this->sessionVariableName]); echo '<br><br>';
		
		return isset($_SESSION[$this->sessionVariableName], $_SESSION[$this->sessionVariableName][$service]);
    }

    /**
     * {@inheritDoc}
     */
    public function clearToken($service)
    {
        if (array_key_exists($service, $_SESSION[$this->sessionVariableName])) {
            unset($_SESSION[$this->sessionVariableName][$service]);
        }

        // allow chaining
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function clearAllTokens()
    {
        unset($_SESSION[$this->sessionVariableName]);

        // allow chaining
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function storeAuthorizationState($service, $state)
    {
        if (isset($_SESSION[$this->stateVariableName])
            && is_array($_SESSION[$this->stateVariableName])
        ) {
            $_SESSION[$this->stateVariableName][$service] = $state;
        } else {
            $_SESSION[$this->stateVariableName] = array(
                $service => $state,
            );
        }

        // allow chaining
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function hasAuthorizationState($service)
    {
        return isset($_SESSION[$this->stateVariableName], $_SESSION[$this->stateVariableName][$service]);
    }

    /**
     * {@inheritDoc}
     */
    public function retrieveAuthorizationState($service)
    {
        if ($this->hasAuthorizationState($service)) {
            return $_SESSION[$this->stateVariableName][$service];
        }

        throw new AuthorizationStateNotFoundException('State not found in session, are you sure you stored it?');
    }

    /**
     * {@inheritDoc}
     */
    public function clearAuthorizationState($service)
    {
        if (array_key_exists($service, $_SESSION[$this->stateVariableName])) {
            unset($_SESSION[$this->stateVariableName][$service]);
        }

        // allow chaining
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function clearAllAuthorizationStates()
    {
        unset($_SESSION[$this->stateVariableName]);

        // allow chaining
        return $this;
    }

    public function __destruct()
    {
        if ($this->startSession) {
            session_write_close();
        }
    }

    /**
     * Determine if the session has started.
     * @url http://stackoverflow.com/a/18542272/1470961
     * @return bool
     */
    protected function sessionHasStarted()
    {
        // For more modern PHP versions we use a more reliable method.
        if (version_compare(PHP_VERSION, '5.4.0') >= 0) {
            return session_status() != PHP_SESSION_NONE;
        }

        // Below PHP 5.4 we should test for the current session ID.
        return session_id() !== '';
    }
}
