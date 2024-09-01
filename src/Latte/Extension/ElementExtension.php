<?php

namespace Northrook\UI\Latte\Extension;

use Latte\Compiler\Node;
use Latte\Compiler\NodeHelpers;
use Latte\Compiler\Nodes\AuxiliaryNode;
use Latte\Compiler\Nodes\FragmentNode;
use Latte\Compiler\Nodes\Html\ElementNode;
use Latte\Compiler\Nodes\Php\ExpressionNode;
use Latte\Compiler\Nodes\TextNode;
use Latte\Compiler\NodeTraverser;
use Latte\Compiler\PrintContext;
use Northrook\Minify;
use Northrook\UI\Compiler\LatteExtension;
use Northrook\UI\Element\Heading;
use function Northrook\replaceEach;


final class ElementExtension extends LatteExtension
{

    public function traverseNodes() : array
    {
        return [
            [ $this, 'headingSystem' ],
        ];
    }

    public function headingSystem( Node $node ) : int | Node
    {
        if ( $node instanceof ExpressionNode ) {
            return NodeTraverser::DontTraverseChildren;
        }

        if ( !$node instanceof ElementNode ) {
            return $node;
        }

        if ( $this->isHeading( $node->name ) ) {
            $level      = $node->name;
            $attributes = $this->nodeAttributes( $node );

            $totalChildren = \count( \iterator_to_array( $node->content ) ) - 1;

            $structure = [];
            $heading   = [];

            /** @var ElementNode[] $childrenArray */
            $childrenArray = \iterator_to_array( $node->content );
            $lastKey       = \array_key_last( $childrenArray );

            foreach ( $childrenArray as $index => $childNode ) {
                // if ( $childNode instanceof TextNode && !\trim( $childNode->content ) ) {
                //     continue;
                // }

                if ( $this->isElement( $childNode, 'small' ) ) {
                    $heading[ 'small' ] = $childNode->content;
                    continue;
                }
                if ( $childNode instanceof ElementNode ) {
                    $heading[] = $childNode->content;
                }
                else {
                    $heading[] = $childNode;
                }

                // if ( $totalChildren === $index && !empty( $structure ) ) {
                //     $heading[] = $this->elementNode( 'span', $node, $structure );
                //     $structure = [];
                // }
                //
                // if ( $childNode instanceof TextNode && !\trim( NodeHelpers::toText( $childNode ) ) ) {
                //     continue;
                // }
                // $structure[ $index ] = $childNode;
            }

            dump( $heading );

            // $node->content = new FragmentNode( \array_values( $heading ) ?? [] );
            if ( $heading ) {
                return new AuxiliaryNode(
                    function( PrintContext $context ) use ( $level, $heading, $attributes ) {
                        $hasVariables = false;

                        foreach ( $heading as $index => $childNode ) {
                            $rawLatteContent = $childNode->print( $context );

                            if (
                                \str_contains( $rawLatteContent, 'LR\Filters' )
                                ||
                                \str_contains( $rawLatteContent, 'echo $' )
                            ) {
                                $hasVariables = true;
                            }

                            if ( \str_contains( $rawLatteContent, 'echo $' ) ) {
                                // dump( $rawLatteContent );
                                $rawLatteContent = \preg_replace(
                                    '#echo (\$\w+?) .*?;#',
                                    "' .  $1  . '",
                                    $rawLatteContent,
                                );
                            }

                            $rawLatteContent = replaceEach(
                                  [
                                      "echo '" => '',
                                      "';"     => '',
                                  ]
                                , $rawLatteContent,
                            );

                            $rawLatteContent = \preg_replace(
                                [ '#(^<\w+.*?>)\s+#', '#\s+(</\w+?>)$#', ], '$1', $rawLatteContent,
                            );

                            // dump( $rawLatteContent );
                            $heading[ $index ] = Minify::HTML( $rawLatteContent );
                        }

                        if ( $hasVariables ) {
                            $echo = ' echo ' . Heading::class . '::' . \strtoupper( $level ) . "( [";
                            foreach ( $heading as $index => $childNode ) {
                                $index = \is_string( $index ) ? "'$index'" : $index;
                                $echo  .= " $index => '$childNode',";
                            }
                            $echo .= '], [';
                            foreach ( $attributes as $index => $childNode ) {
                                $index = \is_string( $index ) ? "'$index'" : $index;
                                $echo  .= " $index => '$childNode',";
                            }
                            $echo .= '] );';

                            // dump( $echo );
                            return $echo;
                        }

                        dump( $heading, $hasVariables );
                        $element = new Heading( $level, $heading, attributes : $attributes );
                        dump( $element );

                        // return "echo 'heading'";
                        //
                        // dump( $level, $attributes, $heading );
                        return "echo '" . $element->toString() . "';";
                    },
                );
            }

            // dump($heading);
            // dump( $structure );
            // dump( $heading );

            // foreach ( $node->content as $index => $child ) {
            //
            //     if ( $this->isElement( $child, 'small' ) ) {
            //         $heading[ 'small' ] = $child;
            //         continue;
            //     }
            //
            //     if ( $this->isElement( $child, 'span' ) ) {
            //         $heading[ "span.$index" ] = $child;
            //         continue;
            //     }
            //
            //
            //     // dump( $totalChildren === $index );
            //     if ( $child instanceof TextNode && ! \trim( NodeHelpers::toText( $child ) ) ) {
            //         continue;
            //     }
            //     $structure[ $index ] = $child;
            // }
            // dump(
            //     $structure,
            //     $heading, $totalChildren
            // // $node->content = new FragmentNode()
            // );

            // dump( $node );

        }
        // if ( $node->name === 'small' && $this->isHeading( $node->parent->name ) ) {
        //     $node->attributes->append(
        //         $this->attributeNode(
        //             'class', [ 'subheading', $node->getAttribute( 'class' ), ],
        //         ),
        //     );
        // }
        //
        // if ( $node->attributes ) {
        //     $node->attributes->children = $this->sortAttributes( $node->attributes );
        // }

        return $node;
    }

}