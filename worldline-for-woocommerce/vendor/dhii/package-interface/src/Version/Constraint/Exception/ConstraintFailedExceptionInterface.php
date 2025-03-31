<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Dhii\Package\Version\Constraint\Exception;

use Syde\Vendor\Worldline\Dhii\Validation\Exception\ValidationFailedExceptionInterface;
/**
 * Represents a case when a version does not match a constraint.
 */
interface ConstraintFailedExceptionInterface extends ValidationFailedExceptionInterface
{
}
