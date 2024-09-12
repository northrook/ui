<?php

namespace Northrook\UI\Component\Breadcrumbs;

use Northrook\Exception\Trigger;
use Northrook\Get;
use Northrook\HTML\Element;
use function Northrook\filterHtmlText;
use function Northrook\filterUrl;


/**
 * @internal
 * @author  Martin Nielsen <mn@northrook.com>
 * @used-by Breadcrumbs, Trail
 */
final readonly class Item
{
    public string  $title;
    public ?string $href;
    public ?string $type;
    public ?string $classes;

    public function __construct(
        string         $title,
        ?string        $href = null,
        array          $classes = [],
        public ?string $icon = null,
        public ?int    $position = null,
        public bool    $current = false,

    )
    {
        $this->title   = filterHtmlText( $title );
        $this->href    = $this->resolveUrl( $href );
        $this->type    = $this->href ? "WebPage" : null;
        $this->classes = implode( ' ', $classes );
    }

    /**
     */
    private function resolveUrl( ?string $url ) : ?string
    {
        if ( !$url ) {
            return null;
        }

        // trigger_deprecation();

        if ( \str_starts_with( $url, '/' ) ) {
            Trigger::valueError(
                'Breadcrumb Items requires an absolute URL, but the relative URL {url} was provided.',
                [ 'url' => $url ],
            );
        }

        return filterUrl( $url );
    }
}