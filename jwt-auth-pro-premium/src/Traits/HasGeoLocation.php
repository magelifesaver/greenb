<?php

declare(strict_types=1);

namespace Tmeister\JWTAuthPro\Traits;

use Exception;
use GeoIp2\Database\Reader;
use GeoIp2\Exception\AddressNotFoundException;

trait HasGeoLocation
{
    private ?Reader $geoReader = null;

    public function getCountryByIP(string $ip): string
    {
        try {
            if ($this->isPrivateIp($ip)) {
                return apply_filters('jwt_auth_private_ip_country_code', '--'); // Local/Private IP
            }

            if (!$this->geoReader) {
                $this->geoReader = new Reader(
                    JWT_AUTH_PRO_PATH . 'src/Database/Countries/GeoLite2-Country.mmdb'
                );
            }

            $record = $this->geoReader->country($ip);
            return $record->country->isoCode ?? apply_filters('jwt_auth_unknown_country_code', 'XX'); // XX for unknown country
        } catch (AddressNotFoundException) {
            return apply_filters('jwt_auth_unknown_country_code', 'XX'); // Unknown country
        } catch (Exception) {
            return apply_filters('jwt_auth_error_country_code', 'ZZ'); // Error reading database
        }
    }

    private function isPrivateIp(string $ip): bool
    {
        // Check for localhost and private networks
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) === false;
    }
}
