<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Dhii\Package\Version\Constraint;

use Syde\Vendor\Worldline\Dhii\Package\Version\Constraint\Exception\ConstraintFailedExceptionInterface;
use Syde\Vendor\Worldline\Dhii\Package\Version\VersionInterface;
use Syde\Vendor\Worldline\Dhii\Validation\ValidatorInterface;
use Exception;
/**
 * Represents a version constraint.
 */
interface VersionConstraintInterface extends ValidatorInterface
{
    /**
     * Validates a package version.
     *
     * @param VersionInterface|mixed $version The version to validate.
     *
     * @throws ConstraintFailedExceptionInterface If version does not match this constraint.
     * @throws Exception If problem validating.
     */
    public function validate($version): void;
}
