<?php

declare( strict_types = 1 );

namespace Northrook\UI\Compiler;

use JetBrains\PhpStorm\Deprecated;
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
use Northrook\Minify;
use Northrook\UI\Compiler\Node\ChildNode;
use Northrook\UI\Compiler\Node\PrintedNode;
use Latte\Essential\Nodes\PrintNode;
use function Northrook\arrayFilter;
use function Northrook\squish;
use const Northrook\EMPTY_STRING;
use const Northrook\WHITESPACE;


/**
 * @property-read string   $tag
 * @property-read string   $name
 * @property-read AreaNode $content
 */
class NodeCompiler
{
    use NodeCompilerMethods;


    public const string HTML_NS = "UI";

    protected const array PROPERTY_ALIAS = [
        'tag' => 'name',
    ];

    public bool $hasExpression = false;

    public function __construct(
        protected Node                 $node,
        private readonly ?NodeCompiler $parent = null,
    ) {}

    public function __get( string $property )
    {
        return match ( $property ) {
            'name', 'tag' => $this->getNodeName(),
            default       => null
        };
    }

    // :: CHECKS :::

    private function getNodeName() : string
    {
        return $this->node?->name ?? throw new \LogicException( 'Undefined property: `name`.' );
    }

    public function isTextNode() : bool
    {
        return $this->node instanceof TextNode;
    }

    final public function tag( string ...$match ) : ?string
    {
        if ( \str_starts_with( $this->getNodeName(), \strtolower( $this::HTML_NS ) ) ) {
            return null;
        }

        if ( $tags = array_intersect( \explode( ':', $this->getNodeName() ), $match ) ) {
            return \array_shift( $tags );
        };

        return null;
    }

    final public function is( string $name ) : bool
    {
        if ( !$this->node instanceof ElementNode
            // ||
            // !\str_contains( $this->node->name, ':' )
        ) {
            return false;
        }

        // dump( $this->node->name, $name );

        return \str_contains( $this->node->name, \strtolower( $name ) );
    }

    public function isElementNode( string ...$tag ) : bool
    {
        if ( !$this->node instanceof ElementNode ) {
            return false;
        }
        if ( empty( $tag ) ) {
            return false;
        }
        return \in_array( $this->node->name, $tag, true );
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
        $printed = new PrintedNode( $node ?? $this->node, $context );
        if ( $printed->isExpression && isset( $this->parent ) ) {
            $this->parent->hasExpression = true;
        }
        return $printed;
    }

    // :: END STRING

    // :: NODE :::

    // :: END NODE

    public function iterateChildNodes() : iterable
    {
        if ( $this->node instanceof ElementNode ) {
            return $this->node->content->getIterator();
        }
        return [];
    }

    public static function getComponentArguments( Node $node ) : array
    {
        return ( new NodeCompiler( $node ) )->resolveComponentArguments();
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

    public function properties( string | array ...$keys ) : array
    {
        $properties = [];
        $attributes = $this->attributes();

        foreach ( $keys as $key ) {
            $default            = \is_array( $key ) ? $key[ \array_key_first( $key ) ] : null;
            $key                = \is_array( $key ) ? \array_key_first( $key ) : $key;
            $value              = $attributes[ $key ] ?? $default;
            $properties[ $key ] = $value;
            unset( $attributes[ $key ] );
        }

        return [ ... $properties, 'attributes' => $attributes ];
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
        // TODO : Does NOT account for expressions or n:tags at this point
        $attributes = [];
        foreach ( static::getAttributeNodes( $from ?? $this->node ) as $attribute ) {
            $name                = NodeHelpers::toText( $attribute->name );
            $value               = NodeHelpers::toText( $attribute->value );
            $attributes[ $name ] = $value;
        }
        // dump( $attributes );
        return $attributes;
    }

    /**
     * Parses an {@see ElementNode} and extracts PHP variables and expressions.
     *
     * @param ?ElementNode  $from
     *
     * @return array
     */
    public function arguments( ?ElementNode $from = null ) : array
    {
        $arguments = [];
        $from      ??= $this->node;

        if ( !$from instanceof ElementNode ) {
            Log::error(
                '{method} can only parse {nodeType}.',
                [
                    'method'   => __METHOD__,
                    'nodeType' => $from::class,
                ],
            );
            return [];
        }

        foreach ( $from->attributes->children as $index => $attribute ) {
            if ( !$attribute instanceof AttributeNode ) {
                continue;
            }

            if ( $attribute->name instanceof PrintNode ) {
                $attribute         = $this->printNode( $attribute->name );
                $key               = \trim( $attribute->variable, "$" );
                $arguments[ $key ] = $attribute->expression;
            }
        }

        // dump( $arguments );

        return $arguments;
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

            if ( $attribute instanceof AttributeNode
                 && !$attribute->name instanceof PrintNode
            ) {
                $attributes[] = $attribute;
            }
        }
        return $clean ? $node->attributes->children : $attributes;
    }

    public function node() : Node
    {
        return $this->node;
    }

    #[Deprecated( replacement : '%class%->node()' )]
    public function returnNode() : Node
    {
        return $this->node;
    }

    /**
     * @param ElementNode  $from
     * @param int          $level
     *
     * @return array
     */
    public function getContent( ElementNode $from, int &$level ) : array
    {
        $content = [];
        $level++;

        foreach ( $from->content->getIterator() as $index => $node ) {
            if ( $node instanceof TextNode ) {
                if ( !$value = squish( NodeHelpers::toText( $node ) ) ) {
                    continue;
                }
                $content[ $index ] = $value;
            }

            if ( $node instanceof PrintNode ) {
                $node = $this->printNode( $node );
                $key  = $node->variable ?? "\${$index}";

                $content[ "$key:$index" ] = $node->value;
            }

            if ( $node instanceof ElementNode ) {
                $content[ "$node->name:$index" ] = [
                    'attributes' => $this->attributes( $node ),
                    'content'    => $this->getContent( $node, $level ),
                ];
                continue;
            }
            //
            // $content[ $index ] = NodeHelpers::toText( $node );
        }

        return $content;
    }

    public function parseContent( ?ElementNode $from = null ) : array
    {
        /** @var ElementNode $from */
        $from  ??= $this->node;
        $level = 0;

        $arguments = $this->getContent( $from, $level );

        // foreach ( $from->content->children as $index => $node ) {
        //     if ( $this->isEmptyText( $node ) ) {
        //         continue;
        //     }
        //     if ( $node instanceof ElementNode ) {
        //         $arguments = [ ... $arguments, ...$this->parseContent( $node ) ];
        //     }

        //     // if ( $this->)
        // }

        return $arguments;
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

    public function htmlContent() : string
    {
        if ( !\property_exists( $this->node, 'content' ) ) {
            Log::error(
                'The NodeCompiler could not parse the requested {method}, as the {nodeType} does not have a {property} property. Returned {result}.',
                [
                    'method'   => 'htmlContent',
                    'nodeType' => $this->node::class,
                    'property' => 'content',
                    'result'   => 'empty string',
                ],
            );
            return EMPTY_STRING;
        }
        return NodeHelpers::toText( $this->node->content );
    }

}