<?php

namespace Northrook\UI\Component;

use Latte\Compiler\Node;
use Northrook\HTML\Element;
use Northrook\UI\Compiler\AbstractComponent;
use Northrook\UI\Compiler\NodeCompiler;
use Northrook\UI\RenderRuntime;


class Menu extends AbstractComponent
{

    private readonly ?Element $list;

    public function __construct(
        private array            $items = [],
        private readonly array   $attributes = [],
        private readonly ?string $parent = null,
    )
    {
        $this->list = new Element( 'ol', [ 'class' => 'navigation' ] );
    }

    protected function build() : string
    {
        $menu = new Element( 'ul', [ 'class' => 'navigation' ], '[Menu here.]' );
        if ( $this->parent ) {
            return (string) new Element( $this->parent, $this->attributes, $menu );
        }
        $menu->attributes->set( $this->attributes );
        return (string) $menu;
    }

    public static function nodeCompiler( NodeCompiler $node ) : Node
    {
        $arguments = $node->arguments();
        // $items     = \array_shift( $arguments );
        // dump( $node,$arguments );

        return RenderRuntime::auxiliaryNode(
            renderName : Menu::class,
            arguments  : [
                             $node->arguments(),
                             $node->attributes(),
                             $node->tag( 'nav' ),
                         ],
        );
    }

    public static function runtimeRender(
        array   $items = [],
        array   $attributes = [],
        ?string $parent = null,
    ) : string
    {
        return (string) new Menu( $items, $attributes, $parent );
    }
}