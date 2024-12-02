<?php

namespace App\Templates\Types;

enum Orientation
{
    case TOP;
    case BOTTOM;
    case LEFT;
    case RIGHT;

    public function isTop(): bool
    {
        return $this === Orientation::TOP;
    }

    public function isBottom(): bool
    {
        return $this === Orientation::BOTTOM;
    }

    public function isLeft(): bool
    {
        return $this === Orientation::LEFT;
    }

    public function isRight(): bool
    {
        return $this === Orientation::RIGHT;
    }
}