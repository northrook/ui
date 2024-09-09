<?php /** @noinspection DuplicatedCode */

namespace Northrook\UI\Element;

use Latte\Compiler\Node;
use Latte\Compiler\Nodes\AuxiliaryNode;
use Latte\Compiler\Nodes\Html\ElementNode;
use Northrook\HTML\Format;
use Northrook\HTML\HtmlNode;
use Northrook\UI\Compiler\Element;
use Northrook\UI\Compiler\NodeCompiler;
use Northrook\UI\RenderRuntime;
use function Northrook\stringStartsWith;
use function Northrook\stringStripTags;
use function Northrook\toString;
use const Northrook\EMPTY_STRING;


/**
 * @method static Heading H1( string|array $content, ?string $subheading = null, bool $hGroup = false, ...$attribute )
 * @method static Heading H2( string|array $content, ?string $subheading = null, bool $hGroup = false, ...$attribute )
 */
final class Heading extends Element
{
    private string  $heading;
    private ?string $subheading;

    public function __construct(
        string         $tag,
        string | array $heading,
        ?string        $subheading = null,
        public bool    $subheadingBefore = false,
        public bool    $hGroup = false,
        array          $attributes = [],
    )
    {
        $this->parseHeadingContent( $heading, $subheading );

        $this
            ->tag( $tag )
            ->assignAttributes( $attributes )
        ;
    }

    protected function onBuild() : void
    {
        $tag = 'span';

        if ( $this->hGroup ) {
            $tag = $this->tag->name;
            $this->tag->set( 'hgroup' );
        }

        $this->content( [ "heading" => "<$tag>" . Format::inline( $this->heading ) . "</$tag>" ] );

        if ( $this->subheading ?? false ) {
            $tag = $this->hGroup ? 'p' : 'small';
            $this->content(
                [
                    'subheading' => "<$tag class=\"subheading\">"
                                    . Format::inline( $this->subheading )
                                    . "</$tag>",
                ],
                $this->subheadingBefore,
            );
        }

        $this->attributes->add( 'id', $this->getHeadingText() );
    }

    private function parseHeadingContent( string | array $heading, ?string $subheading ) : void
    {
        if ( \is_array( $heading ) ) {
            foreach ( $heading as $key => $value ) {
                $value = HtmlNode::unwrap( $value, 'span' );

                if ( stringStartsWith( $key, [ 'small', 'p' ] ) ) {
                    $this->subheading(
                        $value,
                        \str_ends_with( $key, ':before' ),
                        \str_starts_with( $key, 'p' ),
                    );
                    unset( $heading[ $key ] );
                }
                if ( !\is_scalar( $value ) || !$value ) {
                    unset( $heading[ $key ] );
                }
            }
        }

        $heading = \trim( toString( $heading ) );

        $heading = HtmlNode::unwrap( $heading, 'span' );

        $this->heading    = $heading;
        $this->subheading ??= $subheading;
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

    public static function __callStatic(
        string $level, array $arguments,
    )
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

    protected static function parseNodeContent( NodeCompiler $node ) : array
    {
        $childNodes = $node->getContent();
        $content    = [ 'heading' => EMPTY_STRING ];

        foreach ( $childNodes as $index => $childNode ) {
            if ( $childNode->isElementNode( 'small', 'p' ) ) {
                if (
                    !(
                        \array_key_first( $childNodes ) === $index
                        ||
                        \array_key_last( $childNodes ) === $index )
                ) {
                    throw new \LogicException( 'No' );
                }

                $key = !$content[ 'heading' ]
                    ? "{$childNode->tag}:before"
                    : "{$childNode->tag}";

                $content[ $key ] = $childNode->printNode( $childNode->returnNode()->content )->value;
                continue;
            }

            $value = $childNode->printNode();

            $content[ 'heading' ] .= match ( true ) {
                !$content[ 'heading' ] => $value->isExpression ? "$value . '" : $value,
                default                => $value->isExpression ? "' . $value . '" : $value
            };
        }
        $content[ 'heading' ] = "'" . \trim( $content[ 'heading' ] ) . "'";
        return $content;
    }

    public static function nodeCompiler( ElementNode $node ) : AuxiliaryNode
    {
        $node = new NodeCompiler( $node );

        return RenderRuntime::auxiliaryNode(
            Heading::class,
            [
                $node->tag,
                Heading::parseNodeContent( $node ),
                $node->attributes(),
            ],
        );
    }

    public static function runtimeRender( string $level = 'h1', array $content = [], array $attributes = [] ) : string
    {
        return (string) new Heading( $level, $content, attributes : $attributes );
    }
}