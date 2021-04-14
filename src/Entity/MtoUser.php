<?php


namespace Maatoo\WooCommerce\Entity;


class MtoUser
{
    private ?string $username = null;
    private ?string $password = null;
    private ?string $url = null;
    private bool $isBirthdayEnabled = false;

    public function __construct()
    {
        $options = get_option('mto');
        if (empty($options)) {
            return null;
        }

        $this->username = $options['username'] ?? null;
        $this->password = $options['password'] ?? null;
        $this->url = rtrim($options['url']) ?? null;
        $this->isBirthdayEnabled = (bool)$options['birthday'] ?? false;
    }

    /**
     * @return string|null
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * @return string|null
     */
    public function getUrl(): ?string
    {
        return $this->url;
    }

    /**
     * @return bool
     */
    public function isBirthdayEnabled()
    {
        return $this->isBirthdayEnabled;
    }

    /**
     * @return string|null
     */
    public function getUsername(): ?string
    {
        return $this->username;
    }

    /**
     * Set Password.
     *
     * @param $password
     *
     * @return $this
     */
    public function setPassword($password): MtoUser
    {
        $this->password = $password;
        return $this;
    }

    public function setIsBirthdayEnabled($isBirthdayEnabled)
    {
        $this->isBirthdayEnabled = $isBirthdayEnabled;
        return $this;
    }

    /**
     * Set Username.
     *
     * @param $username
     *
     * @return $this
     */
    public function setUsername($username): MtoUser
    {
        $this->username = $username;
        return $this;
    }

    /**
     * Set Url.
     *
     * @param $url
     *
     * @return $this
     */
    public function setUrl($url): MtoUser
    {
        $this->url = rtrim($url);
        return $this;
    }

    public static function toMtoUser($username, $password, $url, $isBirthdayEnabled = false)
    {
        $user = new MtoUser();
        return $user->setUsername($username)
          ->setPassword($password)
          ->setUrl($url)
          ->setIsBirthdayEnabled($isBirthdayEnabled);
    }
}