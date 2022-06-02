<?php


namespace Maatoo\WooCommerce\Entity;


class MtoUser
{
    private ?string $username = null;
    private ?string $password = null;
    private ?string $url = null;
    private bool $isBirthdayEnabled = false;
    private bool $isMarketingEnabled = false;
    private bool $isMarketingCheckedEnabled = true;
    private ?string $marketingCta = null;
    private string $marketingPosition = MTO_DEDAULT_MARKETING_CTA_POSITION;

    public function __construct()
    {
        $options = get_option('mto');
        if (empty($options)) {
            return null;
        }

        $this->username = $options['username'] ?? null;
        $this->password = $options['password'] ?? null;
        $this->url = rtrim($options['url']) ?? null;
        $this->isBirthdayEnabled = (bool)($options['birthday'] ?? false);
        $this->isMarketingEnabled = (bool)($options['marketing'] ?? false);
        $this->isMarketingCheckedEnabled = (bool)($options['marketing_checked'] ?? true);
        $this->marketingCta = $options['marketing_cta'] ?? null;
        $this->marketingPosition = $options['marketing_position'] ?? MTO_DEDAULT_MARKETING_CTA_POSITION;
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
     * @return bool
     */
    public function isMarketingEnabled()
    {
        return $this->isMarketingEnabled;
    }

    /**
     * @return bool
     */
    public function isMarketingCheckedEnabled()
    {
        return $this->isMarketingCheckedEnabled;
    }

    /**
     * @return string|null
     */
    public function getUsername(): ?string
    {
        return $this->username;
    }

    /**
     * @return string|null
     */
    public function getMarketingCta(): ?string
    {
        if ($this->marketingCta === "") {
            return false;
        } else {
            return $this->marketingCta;
        }
    }

    /**
     * @return string
     */
    public function getMarketingPosition(): string
    {
        return $this->marketingPosition;
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

    public function setIsMarketingEnabled($isMarketingEnabled)
    {
        $this->isMarketingEnabled = $isMarketingEnabled;
        return $this;
    }

    public function setIsMarketingCheckedEnabled($isMarketingCheckedEnabled)
    {
        $this->isMarketingCheckedEnabled = $isMarketingCheckedEnabled;
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

    /**
     * Set Marketing cta.
     *
     * @param $marketingCta
     *
     * @return $this
     */
    public function setMarketingCta($marketingCta): MtoUser
    {
        $this->marketingCta = $marketingCta;
        return $this;
    }

    /**
     * Set marketing position.
     *
     * @param $marketingPosition
     *
     * @return $this
     */
    public function setMarketingPosition($marketingPosition): MtoUser
    {
        if ($marketingPosition !== '') {
            $this->marketingPosition = $marketingPosition;
        }
        return $this;
    }

    public static function toMtoUser($username, $password, $url, $isBirthdayEnabled = false, $isMarketingEnabled = false, $isMarketingCheckedEnabled = true, $marketingCta = null, $marketingPosition = '')
    {
        $user = new MtoUser();
        return $user->setUsername($username)
          ->setPassword($password)
          ->setUrl($url)
          ->setIsBirthdayEnabled($isBirthdayEnabled)
          ->setIsMarketingEnabled($isMarketingEnabled)
          ->setIsMarketingCheckedEnabled($isMarketingCheckedEnabled)
          ->setMarketingCta($marketingCta)
          ->setMarketingPosition($marketingPosition);
    }
}