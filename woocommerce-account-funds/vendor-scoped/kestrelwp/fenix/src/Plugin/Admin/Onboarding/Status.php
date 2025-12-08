<?php

namespace Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Admin\Onboarding;

use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Traits\Is_Enum;
/**
 * Onboarding statuses.
 *
 * @since 1.0.0
 */
final class Status
{
    use Is_Enum;
    /** @var string onboarding is not offered */
    public const UNAVAILABLE = 'unavailable';
    /** @var string onboarding has not started yet */
    public const NOT_STARTED = 'not_started';
    /** @var string onboarding is in progress */
    public const IN_PROGRESS = 'in_progress';
    /** @var string onboarding has been completed */
    public const COMPLETED = 'completed';
    /** @var string onboarding has been skipped or dismissed halfway through */
    public const DISMISSED = 'dismissed';
}
