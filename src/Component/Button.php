<?php

namespace Northrook\UI\Component;

use Latte\Compiler\Nodes\AuxiliaryNode;
use Northrook\HTML\Element;
use Northrook\UI\Compiler\AbstractComponent;
use Northrook\UI\Compiler\NodeCompiler;
use Northrook\UI\Component\Button\Type;
use Northrook\UI\RenderRuntime;


class Button extends AbstractComponent
{
    protected readonly Element $button;

    public function __construct(
        protected readonly Type $type = Type::Button,
        array                   $attributes = [],
        array                   $content = [],
    )
    {
        $this->button = new Element( 'button', $attributes, $content );
        $this->button->attributes
            ->set( 'class', 'button', true )
            ->add( 'type', 'button' )
        ;
    }

    protected function build() : string
    {
        return ( string ) $this->button;
    }

    public static function nodeCompiler( NodeCompiler $node ) : AuxiliaryNode
    {
        return RenderRuntime::auxiliaryNode(
            renderName : Button::class,
            arguments  : [
                             'button',
                             $node->attributes(),
                             $node->parseContent(),
                         ],
        );
    }

    public static function runtimeRender(
        string $type = 'button',
        array  $attributes = [],
        array  $content = [],
    ) : string
    {
        return (string) new Button(
            Type::from( $type ),
            $attributes,
            Button::parseContentArray($content),
        );
    }

}