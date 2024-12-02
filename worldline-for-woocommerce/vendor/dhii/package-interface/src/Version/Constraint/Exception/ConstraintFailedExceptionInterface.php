<?php

declare (strict_types=1);
namespace Syde\Vendor\Dhii\Package\Version\Constraint\Exception;

use Syde\Vendor\Dhii\Validation\Exception\ValidationFailedExceptionInterface;
/**
 * Represents a case when a version does not match a constraint.
 */
interface ConstraintFailedExceptionInterface extends ValidationFailedExceptionInterface
{
}
