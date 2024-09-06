<?php

namespace Northrook\UI\Component\Breadcrumbs;

use function Northrook\filterHtmlText;
use function Northrook\filterUrl;

final readonly class Item
{
    public string  $title;
    public ?string $href;
    public ?string $type;
    public ?string $classes;

    public function __construct(
        string         $title,
        ?string        $href = null,
        array   $classes = [],
        public ?string $icon = null,
        public ?int    $position = null,
        public bool    $current = false,

    ) {
        $this->title = filterHtmlText( $title );
        $this->href  = $href ? filterUrl( $href ) : null;
        $this->type  = $this->href ? "WebPage" : null;
        $this->classes = implode( ' ', $classes );
    }
}