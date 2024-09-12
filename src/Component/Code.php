<?php

declare( strict_types = 1 );

namespace Northrook\UI\Component;

use Latte\Compiler\Node;
use Northrook\HTML\Element;
use Northrook\Logger\Log;
use Northrook\UI\Compiler\AbstractComponent;
use Northrook\UI\Compiler\NodeCompiler;
use Northrook\UI\RenderRuntime;
use Tempest\Highlight\Highlighter;
use function Northrook\replaceEach;
use const Northrook\EMPTY_STRING;
use const Northrook\WHITESPACE;


// TODO : Copy-to-clipboard integration - toggle via attr copyToClipboard="true/false|line"
//        Block will enable this by default, inline will not.
//        Allow copy whole block, and line-by-line

class Code extends AbstractComponent
{
    protected const string
        INLINE = 'inline',
        BLOCK = 'block';

    protected readonly Element $component;

    public function __construct(
        private string           $string,
        private readonly ?string $language = null,
        private ?string          $type = null,
        private readonly bool    $tidyCode = true,
        array                    $attributes = [],
    )
    {
        if ( ( $this->type ??= Code::INLINE ) === Code::INLINE ) {
            $this->component = new Element( 'code', $attributes );
            $this->component->class( 'inline', prepend : true );
            $this->string = \preg_replace( '#\s+#', WHITESPACE, $this->string );
        }
        else {
            $this->component = new Element( 'pre', $attributes );
            $this->component->class( 'block', prepend : true );
        }
    }

    protected function build() : string
    {
        if ( $this->tidyCode ) {
            $this->string = replaceEach(
                [ ' ), );' => ' ) );', ],
                $this->string,
            );
        }

        if ( $this->language ) {
            $content = "{$this->highlight( $this->string )}";
            $lines   = \substr_count( $content, PHP_EOL );
            $this->component->attributes( 'language', $this->language );

            if ( $lines ) {
                $this->component->attributes( 'line-count', (string) $lines );
            }
        }
        else {
            $content = $this->string;
        }

        return ( string ) $this->component->content( $content );
    }

    final protected function highlight( string $code, ?int $gutter = null ) : string
    {
        if ( !$this->language || !$code ) {
            return EMPTY_STRING;
        }

        if ( $this->type === Code::INLINE && $gutter ) {
            Log::warning( 'Inline code snippets cannot have a gutter' );
            $gutter = null;
        }

        $highlighter = new Highlighter();
        if ( $gutter ) {
            return $highlighter->withGutter( $gutter )->parse( $code, $this->language );
        }
        return $highlighter->parse( $code, $this->language );
    }

    final public static function nodeCompiler( NodeCompiler $node ) : Node
    {
        $attributes = $node->attributes();
        $exploded   = \explode( ':', $node->name );

        \array_shift( $exploded );
        $type     = null;
        $language = null;
        $tidyCode = \array_key_exists( 'tidyCode', $attributes );
        unset( $attributes[ 'tidyCode' ] );

        $hasType = \array_search( 'inline', $exploded )
            ?: \array_search( 'block', $exploded );

        if ( \is_int( $hasType ) ) {
            $type = $exploded[ $hasType ];
            unset( $exploded[ $hasType ] );
        }

        if ( !empty( $exploded ) ) {
            $language = \implode( ', ', $exploded );
            unset( $exploded );
        }

        return RenderRuntime::auxiliaryNode(
            renderName : Code::class,
            arguments  : [
                             $node->htmlContent(),
                             $language,
                             $type,
                             $tidyCode,
                             $attributes,
                         ],
        );
    }

    public static function runtimeRender(
        ?string $string = null,
        ?string $language = null,
        ?string $type = null,
        bool    $tidyCode = true,
        array   $attributes = [],
    ) : string
    {
        if ( !$string ) {
            return EMPTY_STRING;
        }

        return (string) new Code( $string, $language, $type, $tidyCode, $attributes );
    }
}