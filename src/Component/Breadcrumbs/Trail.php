<?php

namespace Northrook\UI\Component\Breadcrumbs;

final class Trail implements \Countable
{
    private array $breadcrumbs = [];

    public function add(
        string         $title,
        ?string        $href = null,
        array | string $class = [],
        ?string        $icon = null,
    ) : Trail {
        $this->breadcrumbs[] = new Item( $title, $href, (array) $class, $icon );
        return $this;
    }

    public function getBreadcrumbs() : array {
        return $this->breadcrumbs;
    }

    public function count() : int {
        return \count( $this->breadcrumbs );
    }
}