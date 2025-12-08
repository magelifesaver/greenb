<?php

declare (strict_types=1);
namespace Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Http;

defined('ABSPATH') or exit;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Http\Error\Client_Error;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Http\Exceptions\Redirection_Exception;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Http\Url\Exceptions\Invalid_URL_Exception;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Http\Url\Query_Parameters;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Traits\Has_Accessors;
/**
 * HTTP redirect.
 *
 * @since 1.0.0
 *
 * @method string get_url()
 * @method Query_Parameters|null get_query_parameters()
 * @method int get_status()
 * @method bool get_ssl()
 * @method string get_redirected_by()
 * @method $this set_url( string $url )
 * @method $this set_query_parameters( Query_Parameters|null $query_parameters )
 * @method $this set_status( int $status )
 * @method $this set_ssl( bool $ssl )
 * @method $this set_redirected_by( string $redirected_by )
 */
final class Redirect
{
    use Has_Accessors;
    /** @var string base URL to redirect to */
    protected string $url = '';
    /** @var Query_Parameters|null optional query parameters */
    protected ?Query_Parameters $query_parameters = null;
    /** @var int HTTP status code */
    protected int $status = 302;
    /** @var bool whether to use SSL */
    protected bool $ssl = \true;
    /** @var string */
    protected string $redirected_by = 'WordPress';
    /**
     * Constructor.
     *
     * @since 1.0.0
     *
     * @param array<string, mixed> $args
     */
    protected function __construct(array $args = [])
    {
        $this->set_properties($args);
    }
    /**
     * Redirects to the given URL.
     *
     * @since 1.0.0
     *
     * @param string|Url $url
     * @param array<string, mixed> $args
     *
     * @phpstan-param array{
     *    ssl?: bool,
     *    status?: int,
     *    redirected_by?: string,
     *    query_parameters?: Query_Parameters|null,
     * } $args
     *
     * @return void
     * @throws Redirection_Exception
     */
    public static function to($url, array $args = []): void
    {
        $redirect = new self($args);
        if ($url instanceof Url) {
            $url = $url->to_string();
        } elseif (!is_string($url)) {
            // @phpstan-ignore-line type safety
            throw new Redirection_Exception(Client_Error::BAD_REQUEST, 'Invalid URL provided for redirection.');
            // phpcs:ignore
        }
        $redirect->set_url($url);
        $redirect->redirect();
        // @phpstan-ignore-next-line
        if (!empty($args['return'])) {
            return;
        } else {
            exit;
        }
    }
    /**
     * Determines whether a safe redirect should be performed.
     *
     * @since 1.0.0
     *
     * @return bool
     */
    public function use_ssl(): bool
    {
        return $this->get_ssl();
    }
    /**
     * Determines if the URL has query parameters set and are not empty.
     *
     * @since 1.0.0
     *
     * @return bool
     */
    protected function has_query_parameters(): bool
    {
        return null !== $this->query_parameters && $this->query_parameters->count() > 0;
    }
    /**
     * Builds the redirect URL.
     *
     * @return string
     * @throws Invalid_URL_Exception
     */
    protected function build_redirect_url(): string
    {
        $url = Url::from_string($this->get_url());
        if ($this->has_query_parameters()) {
            /** @var Query_Parameters $query */
            $query = $this->get_query_parameters();
            $url->add_query_parameters($query->to_array());
        }
        return $url->to_string();
    }
    /**
     * Redirects to the configured URL.
     *
     * @see \wp_safe_redirect()
     * @see \wp_redirect()
     *
     * @since 1.0.0
     *
     * @return void
     * @throws Redirection_Exception
     */
    public function redirect(): void
    {
        $function = $this->use_ssl() ? '\wp_safe_redirect' : '\wp_redirect';
        try {
            $redirect_url = $this->build_redirect_url();
        } catch (Invalid_URL_Exception $exception) {
            throw new Redirection_Exception(Client_Error::BAD_REQUEST, esc_html($exception->getMessage()), $exception);
            // phpcs:ignore
        }
        $success = $function($redirect_url, $this->get_status(), $this->get_redirected_by());
        if (!$success) {
            throw new Redirection_Exception(Client_Error::MISDIRECTED_REQUEST, esc_html(sprintf('Could not redirect to %s', esc_url($this->get_url()))));
            // phpcs:ignore
        }
    }
}
