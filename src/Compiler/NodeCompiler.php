<?php

declare( strict_types = 1 );

namespace Northrook\UI\Compiler;

use Latte\Compiler\Node;
use Latte\Compiler\NodeHelpers;
use Latte\Compiler\Nodes\AreaNode;
use Latte\Compiler\Nodes\AuxiliaryNode;
use Latte\Compiler\Nodes\FragmentNode;
use Latte\Compiler\Nodes\Html\AttributeNode;
use Latte\Compiler\Nodes\Html\ElementNode;
use Latte\Compiler\Nodes\TextNode;
use Latte\Compiler\PrintContext;
use Latte\ContentType;
use Northrook\HTML\Element;
use Northrook\HTML\Element\Attributes;
use Northrook\Logger\Log;
use Northrook\UI\Compiler\Node\ChildNode;
use Northrook\UI\Compiler\Node\PrintedNode;
use Latte\Essential\Nodes\PrintNode;
use const Northrook\EMPTY_STRING;


/**
 * @property-read string   $tag
 * @property-read string   $name
 * @property-read AreaNode $content
 */
class NodeCompiler
{
    use NodeCompilerMethods;


    protected const array PROPERTY_ALIAS = [
        'tag' => 'name',
    ];

    public bool $hasExpression = false;

    public function __construct( protected Node $node, private ?NodeCompiler $parent = null ) {}

    public function __get( string $property )
    {
        if ( \array_key_exists( $property, $this::PROPERTY_ALIAS ) ) {
            $property = $this::PROPERTY_ALIAS[ $property ];
        }

        if ( !\property_exists( $this->node, $property ) ) {
            return null;
        }
        return match ( $property ) {
            'name'  => $this->node->name,
            default => null
        };
    }

    // :: CHECKS :::

    public function isTextNode() : bool
    {
        return $this->node instanceof TextNode;
    }

    public function isElementNode( string ...$withTag ) : bool
    {
        if ( !$this->node instanceof ElementNode ) {
            return false;
        }
        if ( empty( $withTag ) ) {
            return false;
        }
        return \in_array( $this->node->name, $withTag, true );
    }

    public function isFragmentNode() : bool
    {
        return $this->node instanceof FragmentNode;
    }

    public function isEmptyText( ?Node $node = null ) : bool
    {
        $node ??= $this->node;

        if ( $node instanceof TextNode && !\trim( $node->content ) ) {
            return true;
        }

        return false;
    }

    // :: END CHECKS

    // :: STRING :::

    private function print( Node $node, ?PrintContext $context = null ) : string
    {
        try {
            return $node->print( $context ?? new PrintContext() );
        }
        catch ( \Exception $exception ) {
            Log::exception( $exception );
        }
        return EMPTY_STRING;
    }

    public function printNode( ?Node $node = null, ?PrintContext $context = null ) : PrintedNode
    {
        // dump( $node );
        $printed = new PrintedNode( $node ?? $this->node, $context );
        if ( $printed->isExpression && isset( $this->parent ) ) {
            $this->parent->hasExpression = true;
        }
        return $printed;
    }

    // :: END STRING

    // :: NODE :::

    /**
     * @param ?ElementNode  $from
     *
     * @return NodeCompiler[]
     */
    public function getContent( ?ElementNode $from = null ) : iterable
    {
        $array = [];
        foreach ( \iterator_to_array( ( $from ?? $this->node )->content ) as $key => $node ) {
            if ( $this->isEmptyText( $node ) ) {
                continue;
            }

            $array[ $key ] = $this->childNode( $node );
            // $array[ $key ] = new ChildNode();
        }
        return $array;
    }

    // :: END NODE

    public static function getComponentArguments( Node $node) : array
    {
        return ( new NodeCompiler( $node))->resolveComponentArguments();
    }

    final public function resolveComponentArguments( ?ElementNode $node = null ) : array
    {
        $node ??= $this->node;

        if ( !$node instanceof ElementNode ) {
            Log::error(
                '{method} can only parse {nodeType}.',
                [ 'method' => __METHOD__, 'nodeType' => $node::class ],
            );
            return [];
        }

        $attributes = [];
        $variables  = [];

        foreach ( $node->attributes->children as $index => $attribute ) {
            if ( !$attribute instanceof AttributeNode ) {
                continue;
            }

            if ( $attribute->name instanceof TextNode ) {
                $name                = NodeHelpers::toText( $attribute->name );
                $value               = NodeHelpers::toText( $attribute->value );
                $attributes[ $name ] = $value;
                continue;
            }

            if ( $attribute->name instanceof PrintNode ) {
                $attribute         = $this->printNode( $attribute->name );
                $key               = \trim( $attribute->variable, "$" );
                $variables[ $key ] = $attribute->expression;
            }
        }

        return [
            $attributes,
            $variables,
        ];
    }

    /**
     * Extract {@see ElementNode::$attributes} to `array`.
     *
     * - Each `[key=>value]` is passed through {@see NodeHelpers::toText()}.
     *
     * @param ?ElementNode  $from
     *
     * @return array
     */
    public function attributes( ?ElementNode $from = null ) : array
    {
        $attributes = [];
        foreach ( static::getAttributeNodes( $from ?? $this->node ) as $attribute ) {
            $name                = NodeHelpers::toText( $attribute->name );
            $value               = NodeHelpers::toText( $attribute->value );
            $attributes[ $name ] = $value;
        }
        return $attributes;
    }

    /**
     * @param ElementNode  $node
     * @param bool         $clean
     *
     * @return AttributeNode[]
     */
    final protected static function getAttributeNodes( ElementNode $node, bool $clean = false ) : array
    {
        $attributes = [];
        foreach ( $node->attributes->children as $index => $attribute ) {
            if ( $clean && !$attribute instanceof AttributeNode ) {
                unset( $node->attributes->children[ $index ] );
                continue;
            }

            if ( $attribute instanceof AttributeNode ) {
                $attributes[] = $attribute;
            }
        }
        return $clean ? $node->attributes->children : $attributes;
    }

    public function returnNode() : Node
    {
        return $this->node;
    }

    public function returnAuxiliaryNode() : AuxiliaryNode
    {
        return new AuxiliaryNode( fn() => $this->node->name );
    }

    public function childNode( Node $node ) : ChildNode
    {
        return new ChildNode( $node, $this );
    }

    public static function export() : NodeExporter
    {
        return new NodeExporter();
    }

    public static function elementNode(
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

    public static function attributeNode(
        string                $name,
        null | string | array $value = null,
        string                $quote = '"',
    ) : AttributeNode
    {
        $value = match ( true ) {
            $name === 'class'    => Element::classes( $value ),
            $name === 'style'    => Element::styles( $value ),
            \is_string( $value ) => $value,
            default              => null
        };

        return new AttributeNode( static::textNode( $name ), static::textNode( $value ), $quote );
    }

    public static function textNode( ?string $string = null ) : ?TextNode
    {
        return $string !== null ? new TextNode( (string) $string ) : $string;
    }

    /**
     * @param FragmentNode|AreaNode[]  $attributes
     *
     * @return AreaNode[]
     */
    public static function sortAttributes( FragmentNode | array $attributes ) : array
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