<?php

namespace Northrook\UI\Component;

use Latte\Compiler\Nodes\AuxiliaryNode;
use Northrook\HTML\Element;
use Northrook\UI\Compiler\{AbstractComponent, NodeCompiler};
use Northrook\UI\Component\Button\Type;
use Northrook\UI\RenderRuntime;

class Button extends AbstractComponent
{
    protected readonly Element $button;

    public function __construct(
        protected readonly Type $type = Type::Button,
        array                   $attributes = [],
        private array           $content = [],
    ) {
        $this->button = new Element( 'button', $attributes );
        $this->button->attributes->add( 'type', 'button' );
    }

    protected function build() : string
    {
        $content = [];

        foreach ( $this->content as $index => $html ) {
            if ( \str_starts_with( $index, 'icon' ) ) {
                $content['icon'] = $html;

                continue;
            }

            if ( isset( $content['content'] ) ) {
                $content['content'] .= " {$html}";
            }
            else {
                $content['content'] = $html;
            }
        }

        if ( isset( $content['content'] ) && ! \str_starts_with( $content['content'], '<span' ) ) {
            $content['content'] = "<span>{$content['content']}</span>";
        }

        $this->button->content( $content );

        return (string) $this->button;
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
    ) : string {
        return (string) new Button(
            Type::from( $type ),
            $attributes,
            Button::parseContentArray( $content ),
        );
    }
}
