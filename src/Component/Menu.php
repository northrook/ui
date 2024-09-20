<?php

namespace Northrook\UI\Component;

use Latte\Compiler\Node;
use Northrook\HTML\Element;
use Northrook\UI\Model;
use Northrook\UI\Compiler\AbstractComponent;
use Northrook\UI\Compiler\NodeCompiler;
use Northrook\UI\RenderRuntime;


/**
 * @internal
 */
class Menu extends AbstractComponent
{

    public function __construct(
            private readonly null | string | Model\Menu $menu,
            private array                               $attributes = [],
            private readonly ?string                    $tag = null,
    ) {}

    protected function build() : string
    {
        if ( !isset( $this->attributes[ 'class' ] ) ) {
            $this->attributes[ 'class' ] = 'navigation';
        }
        else {
            $this->attributes[ 'class' ] .= 'navigation ' . $this->attributes[ 'class' ];
        }
        if ( $this->tag ) {
            return ( string) new Element( $this->tag, $this->attributes, $this->menu->render() );
        }

        if ( $this->menu instanceof Model\Menu ) {
            return (string) $this->menu->render( $this->attributes );
        }

        $menu = new Element( 'ul', $this->attributes, $this->menu );
        return (string) $menu;
    }

    public static function nodeCompiler( NodeCompiler $node ) : Node
    {
        $arguments    = $node->arguments();
        $menuVariable = \array_shift( $arguments );
        // dump( $menuVariable, $arguments );

        return RenderRuntime::auxiliaryNode(
                renderName : Menu::class,
                arguments  : [
                                     $menuVariable,
                                     $node->attributes(),
                                     $node->tag( 'nav' ),
                             ],
        );
    }

    public static function runtimeRender(
            null | string | Model\Menu $items = null,
            array                      $attributes = [],
            ?string                    $parent = null,
    ) : string
    {
        return (string) new Menu( $items, $attributes, $parent );
    }

    static public function getAssets() : array
    {
        return [
                __DIR__ . '/Menu/menu.css',
        ];
    }
}