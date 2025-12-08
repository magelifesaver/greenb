<?php

declare (strict_types=1);
namespace Kestrel\Account_Funds\Scoped\Kestrel\Fenix\WordPress;

defined('ABSPATH') or exit;
use Exception;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Helpers\Strings;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Http\Url;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\WordPress;
/**
 * Repository for site-related utilities.
 *
 * @since 1.7.1
 */
final class Site
{
    /**
     * Returns the current site title.
     *
     * @since 1.7.1
     *
     * @return string
     */
    public static function title(): string
    {
        return trim((string) get_bloginfo('name'));
    }
    /**
     * Returns the current site description.
     *
     * @since 1.7.1
     *
     * @return string
     */
    public static function description(): string
    {
        return trim((string) get_bloginfo('description'));
    }
    /**
     * Returns the URL to the homepage for the current site.
     *
     * @since 1.7.1
     *
     * @param string $path optional path to append to the URL
     * @return string
     */
    public static function url(string $path = ''): string
    {
        return home_url($path);
    }
    /**
     * Determines if the current site is in a staging or development environment.
     *
     * This checks the host against known staging subdomains and TLDs.
     *
     * @since 1.7.1
     *
     * @return bool
     */
    public static function is_staging(): bool
    {
        if (WordPress::is_staging()) {
            return \true;
        }
        try {
            $url = Url::from_string(self::url());
            $host = $url->get_host();
            if (!$host || trim($host) === '') {
                return \false;
            }
        } catch (Exception $exception) {
            return \false;
        }
        $host = Strings::string($host)->lowercase();
        // list of known dev/staging subdomains and wildcard domains
        $staging_subdomains = ['*.aubrie-app.fndr-infra.de', '*.closte.com', '*.cloudwaysapps.com', '*.ddev.site', '*.flywheelsites.com', '*.flywheelstaging.com', '*.instawp.xyz', '*.kinsta.cloud', '*.myftpupload.com', '*.pantheonsite.io', '*.sg-host.com', '*.sozowebdesign.co.uk', '*.staging.', '*.templweb.com', '*.test.', '*.wordifysites.com', '*.wpcomstaging.com', '*.wpdns.site', '*.wpengine.com', '*.wpstage.net', 'dev.', 'dev.nfs.health', 'stage.', 'staging.', 'staging-*.'];
        // list of TLDs that usually indicate local or dev environments
        $dev_tlds = ['.dev', '.local', '.test'];
        foreach ($dev_tlds as $dev_tld) {
            if ($host->ends_with($dev_tld)) {
                return \true;
            }
        }
        foreach ($staging_subdomains as $staging_pattern) {
            $staging_pattern = Strings::string($staging_pattern);
            if ($staging_pattern->starts_with('*')) {
                $needle = $staging_pattern->trim_start('*.');
                if ($host->ends_with($needle)) {
                    return \true;
                }
            } elseif ($staging_pattern->ends_with(['.', '-.'])) {
                if ($host->starts_with($staging_pattern->trim_end('.'))) {
                    return \true;
                }
            } elseif ($host->equals($staging_pattern)) {
                return \true;
            }
        }
        return \false;
    }
    /**
     * Determines if the current site is in production.
     *
     * @since 1.7.1
     *
     * @return bool
     */
    public static function is_production(): bool
    {
        return !self::is_staging();
    }
}
