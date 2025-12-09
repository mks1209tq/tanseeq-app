<?php

namespace Modules\Navigation\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class NavigationItem
{
    public function __construct(
        public string $label,
        public ?string $icon = null,
        public int $order = 100,
        public ?string $group = null,
        public ?string $activityCode = '03',
        public array $requiredFields = [],
        public bool $showInNavigation = true,
    ) {}
}

