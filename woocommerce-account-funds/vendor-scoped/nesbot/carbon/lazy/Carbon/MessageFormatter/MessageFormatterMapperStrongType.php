<?php

/**
 * This file is part of the Carbon package.
 *
 * (c) Brian Nesbitt <brian@nesbot.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Kestrel\Account_Funds\Scoped\Carbon\MessageFormatter;

use Kestrel\Account_Funds\Scoped\Symfony\Component\Translation\Formatter\MessageFormatterInterface;
if (!class_exists(LazyMessageFormatter::class, \false)) {
    abstract class LazyMessageFormatter implements MessageFormatterInterface
    {
        public function format(string $message, string $locale, array $parameters = []): string
        {
            return $this->formatter->format($message, $this->transformLocale($locale), $parameters);
        }
    }
}
