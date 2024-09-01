<?php

namespace Northrook\UI\Compiler;

use Latte\Compiler\Node;
use Latte\Compiler\NodeHelpers;
use Latte\Compiler\Nodes\AreaNode;
use Latte\Compiler\Nodes\FragmentNode;
use Latte\Compiler\Nodes\Html\AttributeNode;
use Latte\Compiler\Nodes\Html\ElementNode;
use Latte\Compiler\Nodes\TextNode;
use Latte\ContentType;
use Northrook\HTML\Element\Attributes;
use Northrook\Latte\Compiler\CompilerPassExtension;
use const Northrook\WHITESPACE;


abstract class LatteExtension extends CompilerPassExtension
{
    final protected function isElement( Node $node, ?string $name = null ) : bool
    {
        if ( !$node instanceof ElementNode ) {
            return false;
        }

        if ( $name && $node->name !== $name ) {
            return false;
        }

        return true;
    }

    final protected function isHeading( string $string ) : bool
    {
        return \in_array( $string, [ 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' ], true );
    }

    final protected function nodeRawValue( ?string $string ) : ?string
    {
        dump( $string );
        if ( $string === null ) {
            return null;
        }

        if ( \str_starts_with( $string, 'echo ' ) ) {
            $string = \substr( $string, \strlen( 'echo ' ) );
        }

        if ( \str_starts_with( $string, 'LR\Filters' ) ) {
            $string = \strstr( $string, '(' );
            $string = \strchr( $string, ')', true );
        }

        return \trim( $string, " \n\r\t\v\0;()\"'" );
    }

    final public function textNode( ?string $string = null ) : ?TextNode
    {
        return $string !== null ? new TextNode( (string) $string ) : $string;
    }

    final public function elementNode(
        string              $tag,
        ?Node               $parent,
        null | array | Node $children = null,
        string              $contentType = ContentType::Html,
    ) : ElementNode
    {
        $element = new ElementNode(
            name        : $tag,
            parent      : $parent,
            contentType : $contentType,
        );

        if ( $children ) {
            foreach ( $children as $index => $childNode ) {
                if ( $childNode instanceof TextNode ) {
                    $childNode->content = \trim( $childNode->content );
                }
            }

            $element->content = new FragmentNode( $children );
        }

        return $element;
    }

    final protected function attributeNode(
        string                $name,
        string | array | null $value = null,
        string                $separator = WHITESPACE,
    ) : AttributeNode
    {
        if ( \is_array( $value ) ) {
            $value = \implode( $separator, \array_filter( $value ) );
        }
        return new AttributeNode( $this->textNode( $name ), $this->textNode( $value ), '"' );
    }

    /**
     * @param ElementNode  $node
     *
     * @return AttributeNode[]
     */
    final protected function cleanNodeAttributes( ElementNode $node ) : array
    {
        foreach ( $node->attributes->children as $index => $attribute ) {
            if ( !$attribute instanceof AttributeNode ) {
                unset( $node->attributes->children[ $index ] );
            }
        }
        return $node->attributes->children;
    }

    final protected function nodeAttributes( ElementNode $node ) : array
    {
        $attributes = [];
        foreach ( $this->cleanNodeAttributes( $node ) as $attribute ) {
            $name                = NodeHelpers::toText( $attribute->name );
            $value               = NodeHelpers::toText( $attribute->value );
            $attributes[ $name ] = $value;
        }
        return $attributes;
    }


    //:: UTIL/
    //
    /**
     * @param FragmentNode|AreaNode[]  $attributes
     *
     * @return AreaNode[]
     */
    final static protected function sortAttributes( FragmentNode | array $attributes ) : array
    {
        $children = $attributes instanceof FragmentNode ? $attributes->children : $attributes;

        foreach ( $children as $index => $attribute ) {
            unset( $children[ $index ] );
            if ( $attribute instanceof AttributeNode ) {
                $children[ NodeHelpers::toText( $attribute->name ) ] = $attribute;
            }
        }

        $attributes = [];

        foreach ( Attributes::sort( $children ) as $index => $attribute ) {
            $attributes[] = new TextNode( ' ' );
            $attributes[] = $attribute;
        }

        return $attributes;
    }

}