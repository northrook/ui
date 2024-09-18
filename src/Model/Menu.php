<?php

namespace Northrook\UI\Model;

use JetBrains\PhpStorm\Language;
use Northrook\HTML\Element;
use Northrook\UI\Model\Menu\Item;
use function Northrook\normalizeKey;
use function Northrook\normalizeUrl;
use const Northrook\EMPTY_STRING;


/**
 * This is effectively building the `ol > lo` item stack.
 *
 * Which means it should be expected to exist within a `nav` element when used as a menu,
 * and stand-alone when used as dropdown or possibly breadcrumbs.
 *
 * ```
 * <ol> // each Item will wrap in this
 *     <li> // each Item will be this
 *         <div class="item"> // icon, label, buttons, etc
 *             <svg>{icon}</svg>
 *             <span>{label}</span>
 *             <div class="group"> // dropdown toggle, expand all, remove, grab, etc
 *                 {actions}
 *             </div>
 *         </div>
 *         <ol> // submenu
 *             <li>
 *                 <div class="item"></div>
 *                 <ol></ol> // recursive..
 *             </li>
 *         </ol>
 *     </li>
 * </ol>
 * ```
 *
 *
 */
class Menu implements \Stringable
{

    public array $items = [];

    public readonly string  $name;
    public readonly string  $root;
    public readonly ?string $id;

    public function __construct(
            string                  $name,
            string                  $root,
            public readonly ?string $current = null,
            null | string | false   $id = null,
            private array           $attributes = [],
    )
    {
        $this->name = normalizeKey( $name );
        $this->root = normalizeUrl( $root );
        $this->id   = $id !== false ? normalizeKey( $id ?? $this->name ) : null;

        if ( $this->id ) {
            $this->attributes[ 'id' ] = $this->id;
        }
    }

    final public function items( Item ...$menu ) : static
    {
        foreach ( $menu as $item ) {
            $item->parent( $this );
            $this->items[ $item->id ] = $item;
        }
        return $this;
    }

    public static function html(
            #[Language( 'HTML' )]
            string $html,
    ) : void {}

    public static function link(
            string         $title,
            string         $href,
            string | false $icon = false,
            ?string        $description = null,
            ?string        $id = null,
            bool           $render = true,
            array          $attributes = [],
    ) : Item
    {
        return new Item(
                title       : $title,
                href        : $href,
                icon        : $icon,
                description : $description,
                id          : $id,
                canRender   : $render,
                attributes  : $attributes,
        );
    }

    public static function item(
            string         $title,
            ?string        $href = null,
            string | false $icon = false,
            ?string        $description = null,
            ?string        $id = null,
            bool           $render = true,
            array          $attributes = [],
    ) : Item
    {
        return new Item(
                title       : $title,
                href        : $href,
                icon        : $icon,
                description : $description,
                id          : $id,
                isLink      : false,
                canRender   : $render,
                attributes  : $attributes,
        );
    }

    final public function render( array $attributes = [], string $tag = 'ol' ) : string
    {
        $render = [];

        foreach ( $this->items as $item ) {
            $render[] = $item->render( $this->root );
        }

        $element = new Element( $tag, $this->attributes, $render );
        $element->attributes->merge( $attributes );

        return $element->toString( PHP_EOL );
    }

    public function __toString()
    {
        return $this->render();
    }
}