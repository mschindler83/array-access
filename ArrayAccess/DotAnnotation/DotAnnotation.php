<?php
declare(strict_types=1);

namespace Mschindler83\ArrayAccess\DotAnnotation;

interface DotAnnotation
{
    public function path(): array;

    public function value();
}
