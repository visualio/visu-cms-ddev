<?php

namespace App\Templates\Types;

enum Size
{
    case XS;
    case SM;
    case MD;
    case LG;
    case XL;

    public function isXS(): bool
    {
        return $this === Size::XS;
    }

    public function isSM(): bool
    {
        return $this === Size::SM;
    }

    public function isMD(): bool
    {
        return $this === Size::MD;
    }

    public function isLG(): bool
    {
        return $this === Size::LG;
    }

    public function isXL(): bool
    {
        return $this === Size::XL;
    }
}