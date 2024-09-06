<?php

declare( strict_types = 1 );

namespace Northrook\UI\Compiler;

use Latte\CompileException;
use Latte\Compiler\Node;
use Latte\Compiler\Nodes\FragmentNode;
use Latte\Compiler\Nodes\Html\ElementNode;
use Latte\Compiler\Nodes\TextNode;
use Latte\Compiler\PrintContext;
use Latte\Essential\Nodes\PrintNode;
use Northrook\HTML\Element\Tag;
use Northrook\Logger\Log;


trait NodeCompilerMethods
{
    /**
     * Check if the provided string is considered a {@see Tag::HEADING}.
     *
     * - Case-insensitive
     *
     * @param string | Node  $element
     *
     * @return bool
     */
    protected static function isHeading( string | Node $element ) : bool
    {
        if ( $element instanceof Node ) {
            if ( $element instanceof ElementNode ) {
                $element = $element->name;
            }
            else {
                return false;
            }
        }

        return \in_array( \strtolower( $element ), Tag::HEADING, true );
    }

    /**
     * Check if the provided string is considered a {@see Tag::HEADING}.
     *
     * - Case-insensitive
     *
     * @param Node    $node
     * @param string  $name
     *
     * @return bool
     */
    protected static function isComponent( Node $node, string $name ) : bool
    {
        if ( !$node instanceof ElementNode ) {
            return false;
        }

        return $node->name === $name;
    }

    protected static function stringifyCodeContent( ElementNode | TextNode | PrintNode $node ) : ?string
    {
        if ( $node instanceof TextNode ) {
            return $node->content;
        }
        if ( $node instanceof PrintNode ) {
            try {
                return $node->print( new PrintContext() );
            }
            catch ( CompileException $exception ) {
                Log::exception( $exception );
            }
        }

        if ( $node->content instanceof FragmentNode ) {
            $string = '';
            foreach ( $node->content->children as $childNode ) {
                if ( $childNode instanceof TextNode && !\trim( $childNode->content ) ) {
                    continue;
                }

                if ( $childNode instanceof TextNode ) {
                    $string .= $childNode->content;
                    continue;
                }
                if ( $childNode instanceof ElementNode ) {
                    $string .= self::stringifyCodeContent( $childNode );
                }
                if ( $childNode instanceof PrintNode ) {
                    $string .= self::stringifyCodeContent( $childNode );
                }
            }
            return $string;
        }

        return null;
    }

}