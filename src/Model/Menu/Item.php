<?php

namespace Northrook\UI\Model\Menu;

use Northrook\HTML\Element;
use Northrook\HTML\Format;
use Northrook\Trait\PropertyAccessor;
use Northrook\UI\Compiler\Template;
use Northrook\UI\IconPack;
use Northrook\UI\Model\Menu;
use function Northrook\escapeHtmlText;
use function Northrook\filterUrl;
use function Northrook\normalizeKey;
use function Northrook\normalizeUrl;
use function Northrook\toString;
use const Northrook\EMPTY_STRING;


/**
 * @property-read bool    $canRender
 * @property-read bool    $hasChildren
 * @property-read ?string $href
 * @property-read ?Item[] $item // Loop over each child item
 */
final class Item implements \Stringable
{
    use PropertyAccessor;


    private array $items = [];

    public readonly string  $id;
    public readonly string  $title;
    public readonly ?string $icon;
    private ?string         $description;
    private ?string         $link;
    private bool            $isLink;
    private ?string         $submenuId = null;

    /**
     * @param string          $title
     * @param ?string         $href
     * @param ?string         $icon
     * @param ?string         $description
     * @param null|string     $id
     * @param bool            $canRender
     * @param null|Item|Menu  $parent
     * @param null|Template   $template
     */
    public function __construct(
        string                     $title,
        ?string                    $href = null,
        ?string                    $icon = null,
        ?string                    $description = null,
        ?string                    $id = null,
        ?bool                      $isLink = null,
        private bool               $canRender = true,
        private null | Item | Menu $parent = null,
        private null | Template    $template = null,
        private readonly array     $attributes = [],
    )
    {
        $this->title       = escapeHtmlText( $title );
        $this->id          = normalizeKey( $id ?? $this->title );
        $this->link        = $href ? filterUrl( $href ) : null;
        $this->isLink      = $isLink ?? $href;
        $this->icon        = $icon ? IconPack::get( $icon ) : null;
        $this->description = Format::newline( escapeHtmlText( $description ) );
    }

    public function __get( string $property )
    {
        return match ( $property ) {
            'item'        => $this->items,
            'href'        => $this->link,
            'canRender'   => $this->canRender,
            'hasChildren' => !empty( $this->items ),
            default       => throw new \InvalidArgumentException( 'Unknown property: ' . $property ),
        };
    }

    public function submenu( Item ...$item ) : Item
    {
        foreach ( $item as $add ) {
            $add->parent( $this );
            $this->items[ $add->id ] = $add;
        }
        return $this;
    }

    public function render( ?string $parentHref = null ) : string
    {
        $item = Element::li(
            <<<HTML
            <div class="title">
              {$this->itemLabel( $parentHref )}
            </div>
            {$this->actions()}
            {$this->description()}
            {$this->nestedItems()}
        HTML,
            $this->attributes,
        );

        if ( $this->hasChildren ) {
            $item->class( 'has-children' );
        }

        $item
            ->id( normalizeKey( [ $this->parent->id, $this->id ] ) )
            ->class( 'menu-item', prepend : true )
        ;

        return $item;
    }

    private function description() : ?string
    {
        if ( !$this->description ) {
            return null;
        }
        return <<<HTML
            <div class="description">{$this->description}</div>
        HTML;
    }

    private function actions() : ?string
    {
        $actions = [];

        if ( $this->hasChildren ) {
            $this->submenuId = "$this->id-submenu";
            $actions[]       = Element::button(
                <<<HTML
                <svg class="icon toggle on direction:down" viewBox="0 0 16 16" fill="none" stroke="currentColor">
                  <path class="chevron" stroke-linecap="round" stroke-linejoin="round" d=""></path>
                </svg>
                HTML

                , [
                'aria-controls' => $this->submenuId,
                'aria-expanded' => 'false',
            ],
            );
        }

        if ( !$actions ) {
            return null;
        }

        $actions = toString( $actions );
        return <<<HTML
            <div class="group">{$actions}</div>
        HTML;
    }

    private function link( ?string $parentHref ) : ?string
    {
        if ( $parentHref === null ) {
            return $this->link;
        }

        $isAbsolute = false;

        if ( \str_starts_with( $this->link, './' ) ) {
            return normalizeUrl( \trim( $this->link, './' ) );
        }

        return $this->link = normalizeUrl(
            "{$parentHref}/{$this->link}",
        );
    }

    private function itemLabel( ?string $parentHref = null ) : string
    {
        // $title = ( $this->icon || $this->href ) ? "<span>{$this->title}</span>" : $this->title;
        $icon = $this->icon ? "<i>{$this->icon}</i>" : null;
        if ( $this->link ) {
            $href = $this->link( $parentHref );
            return <<<HTML
                {$icon}<a href="{$href}">{$this->title}</a>
                HTML;
        }
        return <<<HTML
                {$icon}<span>{$this->title}</span>
                HTML;
    }

    private function nestedItems() : ?string
    {
        if ( empty( $this->items ) ) {
            return null;
        }

        $content = [];

        foreach ( $this->items as $item ) {
            $content[] = $item->render( $this->href );
        }

        return Element::ol(
            $content, [
            'id'    => $this->submenuId,
            'class' => 'submenu',
        ],
        );
    }

    public function parent( null | Item | Menu $set = null ) : null | Item | Menu
    {
        if ( $set ) {
            $this->parent ??= $set;
        }
        return $this->parent;
    }

    public function __toString()
    {
        return '[recursive generator]';
    }

}