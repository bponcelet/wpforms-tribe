<?php

/**
 * This file is part of the league/oauth2-client library
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @copyright Copyright (c) Alex Bilbie <hello@alexbilbie.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @link http://thephpleague.com/oauth2-client/ Documentation
 * @link https://packagist.org/packages/league/oauth2-client Packagist
 * @link https://github.com/thephpleague/oauth2-client GitHub
 */
namespace WPFormsTribe\Vendor\League\OAuth2\Client\Provider;

/**
 * Classes implementing `ResourceOwnerInterface` may be used to represent
 * the resource owner authenticated with a service provider.
 */
interface ResourceOwnerInterface
{
    /**
     * Returns the identifier of the authorized resource owner.
     *
     * @return mixed
     */
    public function getId();
    /**
     * Return all of the owner details available as an array.
     *
     * @return array
     */
    public function toArray();
}
