<?php

namespace Northrook\UI\Compiler;

use Latte\Compiler\Node;
use Northrook\HTML;
use Northrook\HTML\Element\AttributeMethods;
use Northrook\UI\Compiler\Latte\RuntimeRenderInterface;
use Northrook\UI\Component\Icon;
use Northrook\UI\RenderRuntime;
use function Northrook\classBasename;
use function Northrook\hashKey;
use function Northrook\stringStartsWith;
use const Northrook\WHITESPACE;


abstract class AbstractComponent implements RuntimeRenderInterface
{

    private static function elementKey( string | int $element, string $valueType ) : string | int | null
    {
        if ( \is_int( $element ) ) {
            return $element;
        }

        $index = \strrpos( $element, ':' );

        // Treat parsed string variables as simple strings
        if ( $valueType === 'string' && \str_starts_with( $element, '$' ) ) {
            return (int) \substr( $element, $index++ );
        }

        return $element;

        // Trim off the index, return as-is
        // return \substr( $element, 0, $index );
    }

    private static function appendTextString( string $value, array &$content ) : void
    {
        $lastIndex = \array_key_last( $content );
        $index     = \count( $content );

        if ( \is_int( $lastIndex ) ) {
            if ( $index > 0 ) {
                $index--;
            }
        }

        if ( isset( $content[ $index ] ) ) {
            $content[ $index ] .= " $value";
        }
        else {
            $content[ $index ] = $value;
        }
    }

    // private static function appendHtmlElement( string $element, array $value, array &$content ) : void
    // {
    //     $content[ $element ] = static::recursiveElement(
    //         tag        : $element,
    //         attributes : \array_shift( $value ),
    //         content    : $value,
    //     );
    // }

    private static function recursiveElement( array $array, null | string | int $key = null ) : string | array
    {
        // If $key is string, this iteration is an element
        if ( \is_string( $key ) ) {
            $tag        = \strrchr( $key, ':', true );
            $attributes = $array[ 'attributes' ];
            $array      = $array[ 'content' ];

            if ( \str_ends_with( $tag, 'icon') && $get = $attributes[ 'get' ] ?? null ) {
                unset( $attributes[ 'get' ] );
                return (string) new Icon( $tag, $get, $attributes );
            }
        }

        $content = [];

        foreach ( $array as $elementKey => $value ) {
            $elementKey = self::elementKey( $elementKey, \gettype( $value ) );
            $elementTag = \strrpos( $elementKey, ':' );

            if ( \is_array( $value ) ) {
                $content[ $elementKey ] = self::recursiveElement( $value, $elementKey );
            }
            else {
                static::appendTextString( $value, $content );
            }

            // Unnamed elements will be truncated as single simple text strings
            // if ( \is_int( $elementKey ) ) {
            // }
        }

        // $tag = \strstr( $tag, ':', true );
        //
        if ( \is_string( $key ) ) {
            $result = new HTML\Element( $tag, $attributes, $content );
        }
        else {
            return $content;
        }
        return (string) $result;
    }

    final protected static function parseContentArray( array $array ) : array
    {
        return static::recursiveElement( $array );
    }

    final protected function templateName() : string
    {
        return \strtolower( classBasename( $this::class ) );
    }

    final protected function uniqueTemplateId() : string
    {
        return hashKey( [ $this, \spl_object_id( $this ) ] );
    }

    /**
     * Called when the Component is stringified.
     *
     * @return string
     */
    abstract protected function build() : string;

    final protected function onPrint() : void {}

    final public function __toString() : string
    {
        RenderRuntime::registerInvocation( $this::class );
        return $this->build();
    }
}