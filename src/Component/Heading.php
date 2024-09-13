<?php /** @noinspection DuplicatedCode */

namespace Northrook\UI\Component;

use JetBrains\PhpStorm\ExpectedValues;
use Latte\Compiler\NodeHelpers;
use Latte\Compiler\Nodes\AuxiliaryNode;
use Latte\Compiler\Nodes\Html\ElementNode;
use Northrook\HTML\Element;
use Northrook\HTML\Format;
use Northrook\HTML\HtmlNode;
use Northrook\UI\Compiler\AbstractComponent;
use Northrook\UI\Compiler\NodeCompiler;
use Northrook\UI\RenderRuntime;
use function Northrook\stringStartsWith;
use function Northrook\stringStripTags;
use function Northrook\toString;
use const Northrook\EMPTY_STRING;
use const Northrook\WHITESPACE;


/**
 * @method static Heading H1( string|array $content, ?string $subheading = null, bool $hGroup = false, ...$attribute )
 * @method static Heading H2( string|array $content, ?string $subheading = null, bool $hGroup = false, ...$attribute )
 */
final class Heading extends AbstractComponent
{
    private string  $heading;
    private ?string $subheading;

    public function __construct(
        #[ExpectedValues( [ 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' ] )]
        private string $level,
        string | array $heading,
        ?string        $subheading = null,
        public bool    $subheadingBefore = false,
        public bool    $hGroup = false,
        private array  $attributes = [],
    )
    {
        $this->parseHeadingContent( $heading, $subheading );
    }

    private function parseHeadingContent( string | array $heading, ?string $subheading ) : void
    {
        if ( \is_array( $heading ) ) {
            foreach ( $heading as $key => $value ) {
                if ( stringStartsWith( $key, [ 'small', 'p' ] ) ) {
                    $this->subheading( $value, \array_key_first( $heading ) === $key );
                    unset( $heading[ $key ] );
                }
            }
        }

        $heading = toString( $heading, WHITESPACE );

        $heading = HtmlNode::unwrap( $heading, 'span' );

        $this->heading    = $heading;
        $this->subheading ??= $subheading;
    }

    protected function build() : string
    {
        $element = new Element(
            $this->hGroup ? 'hgroup' : $this->level,
            $this->attributes + [ 'id' => $this->getHeadingText() ],
        );

        $this->heading = $this->hGroup ? "<$this->level>$this->heading</$this->level>" : "<span>$this->heading</span>";

        $element
            ->content( $this->heading )
            ->content( $this->subheading, $this->subheadingBefore )
        ;

        // dump( (string) $element );

        return (string) $element;
    }

    public static function __callStatic( string $level, array $arguments )
    {
        $level = \strtolower( $level );

        if ( \in_array( $level, [ 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' ] ) ) {
            $attributes = [];
            $heading    = \array_shift( $arguments );
            $subheading = null;
            $hGroup     = false;

            foreach ( $arguments as $name => $attribute ) {
                if ( $name === 'subheading' ) {
                    $subheading = $attribute;
                    unset( $arguments[ $name ] );
                }
                if ( $name === 'hGroup' ) {
                    $hGroup = $attribute;
                    unset( $arguments[ $name ] );
                }
            }

            return new Heading(
                $level, $heading, $subheading, hGroup : $hGroup, attributes : $attributes,
            );
        }
        throw new \LogicException( "Undefined Heading level called: '$level' . " );
    }

    public function getHeadingText() : string
    {
        if ( $this->hGroup ) {
            return stringStripTags( $this->heading );
        }

        $content = $this->subheadingBefore
            ? [ $this->subheading, $this->heading ]
            : [ $this->heading, $this->subheading ];

        return stringStripTags( toString( $content, ' ' ) );
    }

    public function subheading(
        ?string $string,
        bool    $before = false,
        ?bool   $hGroup = null,
    ) : Heading
    {
        $this->subheading       = \trim( $string );
        $this->subheadingBefore = $before;
        if ( $hGroup !== null ) {
            $this->hGroup = $hGroup;
        }
        return $this;
    }

    public static function nodeCompiler( NodeCompiler $node ) : AuxiliaryNode
    {
        foreach ( $node->iterateChildNodes() as $key => $childNode ) {
            if ( $childNode instanceof ElementNode && in_array( $childNode->name, [ 'small', 'p' ] ) ) {
                $classes = $childNode->getAttribute( 'class' );

                $childNode->attributes->append(
                    $node::attributeNode(
                        'class', [
                        'subheading',
                        $classes,
                    ],
                    ),
                );

                continue;
            }
        }

        return RenderRuntime::auxiliaryNode(
            Heading::class,
            [
                $node->tag,
                $node->parseContent(),
                $node->attributes(),
            ],
        );
    }

    public static function runtimeRender( string $level = 'h1', array $content = [], array $attributes = [] ) : string
    {
        return (string) new Heading( $level, Heading::parseContentArray( $content ), attributes : $attributes );
    }
}