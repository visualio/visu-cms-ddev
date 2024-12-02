<?php

namespace App\Templates\Types;

enum Variant
{
    case PRIMARY;
    case SECONDARY;
    case TERTIARY;

    public function isPrimary(): bool
    {
        return $this === Variant::PRIMARY;
    }

    public function isSecondary(): bool
    {
        return $this === Variant::SECONDARY;
    }

    public function isTertiary(): bool
    {
        return $this === Variant::TERTIARY;
    }
}