<?php

namespace App\Services\Education;

/**
 * Result of evaluating a single atomic condition.
 * `note` carries an explanation when the condition could not be evaluated.
 */
final class ConditionOutcome
{
    public function __construct(
        public readonly bool $passed,
        public readonly ?string $note = null,
    ) {}

    public static function unevaluable(string $note): self
    {
        return new self(passed: false, note: $note);
    }
}
