<?php


namespace Maatoo\WooCommerce\Entity;


class MtoUser
{
    private ?string $username;
    private ?string $password;
    private ?string $url;

    public function __construct()
    {
        $options = get_option('mto');
        if (empty($options)) {
            return null;
        }

        $this->username = $options['username'] ?? null;
        $this->password = $options['password'] ?? null;
        $this->url = rtrim($options['url']) ?? null;
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

    public static function toMtoUser($username, $password, $url)
    {
        $user = new MtoUser();
        return $user->setUsername($username)
                    ->setPassword($password)
                    ->setUrl($url);
    }
}