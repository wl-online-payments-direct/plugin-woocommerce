<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Uri;

use RuntimeException;
use UnexpectedValueException;
trait RegexTrait
{
    /**
     * Matches a pattern in a string.
     *
     * @see preg_match()
     *
     *
     * @param string $pattern
     * @param string $subject
     * @param array $matches
     * @param int-mask<0, 256, 512, 768> $flags
     * @param int $offset
     *
     * @return bool True if a match is found; false otherwise.
     * @throws RuntimeException If problem matching.
     */
    protected function pregMatch(string $pattern, string $subject, array &$matches = [], int $flags = 0, int $offset = 0) : bool
    {
        $result = \preg_match($pattern, $subject, $matches, $flags, $offset);
        if ($result === \false) {
            if (\preg_last_error() === \PREG_NO_ERROR) {
                throw new UnexpectedValueException('A RegEx error has occurred, but no info is available');
            }
            throw new RuntimeException(\preg_last_error_msg());
        }
        return (bool) $result;
    }
    /**
     * Matches a pattern in a string.
     *
     * @see preg_match()
     *
     * @param string $pattern
     * @param string $subject
     * @param array<int, string> $matches
     * @param int $flags
     * @param int $offset
     *
     * @return int The number of full pattern matches.
     * @throws RuntimeException If problem matching.
     */
    protected function pregMatchAll(string $pattern, string $subject, array &$matches = [], int $flags = 0, int $offset = 0) : int
    {
        $result = \preg_match_all($pattern, $subject, $matches, $flags, $offset);
        if ($result === \false) {
            if (\preg_last_error() === \PREG_NO_ERROR) {
                throw new UnexpectedValueException('A RegEx error has occurred, but no info is available');
            }
            throw new RuntimeException(\preg_last_error_msg());
        }
        return $result;
    }
    /**
     * Replaces a pattern in a string.
     *
     * @see preg_replace()
     *
     * @param string $pattern The pattern to use for replacement.
     * @param string $replacement The replacement.
     * @param string $subject The subject to replace in.
     * @param int $limit The max number of replacements to make.
     * @param int $count This will reflect the number of replacements made.
     *
     * @return string The string after replacement.
     * @throws RuntimeException If problem replacing.
     */
    protected function pregReplace(string $pattern, string $replacement, string $subject, int $limit = -1, int &$count = 0) : string
    {
        $result = \preg_replace($pattern, $replacement, $subject, $limit, $count);
        if ($result === null) {
            if (\preg_last_error() === \PREG_NO_ERROR) {
                throw new UnexpectedValueException('A RegEx error has occurred, but no info is available');
            }
            throw new RuntimeException(\preg_last_error_msg());
        }
        return $result;
    }
    /**
     * Replaces a pattern in a string.
     *
     * @see preg_replace_callback()
     *
     * @param string $pattern The pattern to use for replacement.
     * @param callable(array):string $callback The replacement callback.
     * @param string $subject The subject to replace in.
     * @param int $limit The max number of replacements to make.
     * @param int $count This will reflect the number of replacements made.
     *
     * @return string The string after replacement.
     * @throws RuntimeException If problem replacing.
     */
    protected function pregReplaceCallback(string $pattern, callable $callback, string $subject, int $limit = -1, int &$count = 0) : string
    {
        $result = \preg_replace_callback($pattern, $callback, $subject, $limit, $count);
        if ($result === null) {
            if (\preg_last_error() === \PREG_NO_ERROR) {
                throw new UnexpectedValueException('A RegEx error has occurred, but no info is available');
            }
            throw new RuntimeException(\preg_last_error_msg());
        }
        return $result;
    }
}
