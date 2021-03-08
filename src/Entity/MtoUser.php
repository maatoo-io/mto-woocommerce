<?php


namespace Maatoo\WooCommerce\Entity;


class MtoUser
{
    private ?string $username;
    private ?string $password;
    private ?string $token;

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
    public function getToken(): ?string
    {
        return $this->token;
    }

    /**
     * @return string|null
     */
    public function getUsername(): ?string
    {
        return $this->username;
    }
}