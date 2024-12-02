<?php

namespace App\Templates\Types;

enum Severity
{
    case SUCCESS;
    case WARNING;
    case DANGER;
    case INFO;

    public function isSuccess(): bool
    {
        return $this === Severity::SUCCESS;
    }

    public function isWarning(): bool
    {
        return $this === Severity::WARNING;
    }

    public function isDanger(): bool
    {
        return $this === Severity::DANGER;
    }

    public function isInfo(): bool
    {
        return $this === Severity::INFO;
    }
}