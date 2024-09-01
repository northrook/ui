<?php

namespace Northrook\UI\Element;

use Northrook\UI\Compiler\Element;
use Northrook\HTML\Format;
use Northrook\Logger\Log;
use Northrook\UI\Latte\RuntimeRenderInterface;
use function Northrook\stringStartsWith;
use function Northrook\stringStripTags;
use function Northrook\toString;


/**
 * @method static Heading H1( string|array $content, ?string $subheading = null, bool $hGroup = false, ...$attribute )
 * @method static Heading H2( string|array $content, ?string $subheading = null, bool $hGroup = false, ...$attribute )
 */
final class Heading extends Element implements RuntimeRenderInterface
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

        $this->content( [ "heading" => "<$tag>" . Format::textContent( $this->heading ) . "</$tag>" ] );

        if ( $this->subheading ?? false ) {
            $tag = $this->hGroup ? 'p' : 'small';
            $this->content(
                [
                    'subheading' => "<$tag class=\"subheading\">" . Format::textContent(
                            $this->subheading,
                        ) . "</$tag>",
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

        if ( \str_starts_with( $heading, '<' ) || \str_ends_with( $heading, '>' ) ) {
            Log::error( 'The provided heading content should not be wrapped in any element.' );
        }

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

    public static function runtimeRender(
        array $attributes = [], ...$arguments
    ) : RuntimeRenderInterface
    {
        // TODO: Implement runtimeRender() method.
    }
}