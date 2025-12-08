<?php

namespace Kestrel\Account_Funds\Scoped;

/**
 * This file is part of the Carbon package.
 *
 * (c) Brian Nesbitt <brian@nesbot.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use Kestrel\Account_Funds\Scoped\Symfony\Component\Translation\PluralizationRules;
// @codeCoverageIgnoreStart
if (\class_exists(PluralizationRules::class)) {
    PluralizationRules::set(static function ($number) {
        return PluralizationRules::get($number, 'ca');
    }, 'ca_ES_Valencia');
}
// @codeCoverageIgnoreEnd
return \array_replace_recursive(require __DIR__ . '/ca.php', []);
