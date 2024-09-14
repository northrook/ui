<?php

namespace Northrook\UI\Component;

use Latte\Compiler\Nodes\AuxiliaryNode;
use Northrook\HTML\Element;
use Northrook\UI\Compiler\AbstractComponent;
use Northrook\UI\Compiler\NodeCompiler;
use Northrook\UI\Component\Button\Type;
use Northrook\UI\IconPack;
use Northrook\UI\RenderRuntime;
use const Northrook\EMPTY_STRING;


class Icon extends AbstractComponent
{
    private readonly ?Element $icon;

    public function __construct(
        protected readonly string $component,
        protected readonly string $get,
        protected readonly array  $attributes = [],
    )
    {
        $this->icon = IconPack::get( $this->get, 'notice', true );
    }

    protected function build() : string
    {
        $this->icon->attributes->merge( $this->attributes );

        if ( $this->component !== 'icon' ) {
            $tag = \strstr( $this->component, ':', true );
            return new Element( $tag, $this->attributes, $this->icon );
        }

        return $this->icon->toString();
    }

    public static function nodeCompiler( NodeCompiler $node ) : AuxiliaryNode
    {
        $attributes = $node->attributes();
        $get        = $node->arguments()[ 'get' ] ?? $attributes[ 'get' ] ?? null;

        unset( $attributes[ 'get' ] );

        return RenderRuntime::auxiliaryNode(
            renderName : Icon::class,
            arguments  : [
                             $node->tag( 'i', 'span' ),
                             $get,
                             $attributes,
                         ],
        );
    }

    public static function runtimeRender(
        ?string $component = 'icon',
        string  $get = EMPTY_STRING,
        array   $attributes = [],
    ) : string
    {
        return (string) new Icon( $component ?? 'icon', $get, $attributes );
    }

}