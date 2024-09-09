<?php

namespace Northrook\UI\Element;

// code:block, <code lang="php">, <pre lang="javascript">
use Latte\Compiler\Node;
use Latte\Compiler\NodeHelpers;
use Latte\Compiler\Nodes\AreaNode;
use Latte\Compiler\Nodes\AuxiliaryNode;
use Latte\Compiler\Nodes\Html\AttributeNode;
use Latte\Compiler\Nodes\Html\ElementNode;
use Latte\Compiler\Nodes\TextNode;
use Latte\Compiler\PrintContext;
use Northrook\Minify;
use Northrook\UI\Compiler\Element;
use Northrook\UI\Compiler\Latte\RuntimeRenderInterface;
use Northrook\UI\Compiler\NodeCompiler;
use Northrook\UI\RenderRuntime;
use Symfony\Component\Console\Command\DumpCompletionCommand;
use Tempest\Highlight\Highlighter;
use const Northrook\EMPTY_STRING;


class Code extends Element implements RuntimeRenderInterface
{
    private readonly Highlighter $highlighter;

    public function __construct(
        private string  $string,
        private ?string $language = null,
        private string  $type = 'inline',
        array           $attributes = [],
    )
    {
        dump( get_defined_vars() );
        $this
            ->tag( 'code' )
            ->assignAttributes( $attributes )
        ;
    }

    protected function onBuild() : void
    {
        $this->highlighter = new Highlighter();

        $content = $this->language
            ? $this->highlighter->parse( $this->string, $this->language )
            : $this->string;

        dump( $this->language, $content );

        $this->content( $content );
    }

    public static function nodeCompiler( ElementNode $node ) : Node
    {
        return RenderRuntime::auxiliaryNode(
            Code::class,
            Code::resolveArguments( $node ),
        );
    }

    private static function resolveArguments( $node ) : array
    {
        $exploded = \explode( ':', $node->name );

        \array_shift( $exploded );
        $type     = null;
        $language = null;

        $hasType = \array_search( 'inline', $exploded )
                   ?? \array_search( 'block', $exploded );

        if ( $hasType ) {
            $type = $exploded[ $hasType ];
            unset( $exploded[ $hasType ] );
        }

        if ( !empty( $exploded ) ) {
            $language = \implode( ', ', $exploded );
            unset( $exploded );
        }

        $arguments = [
            NodeHelpers::toText( $node->content ),
            $language,
            $type,
            [],
        ];

        // dump( $arguments );

        return $arguments;
    }

    public static function runtimeRender(
        ?string $string = null,
        ?string $language = null,
        string  $type = 'inline',
        array   $attributes = [],
    ) : string
    {
        if ( !$string ) {
            return EMPTY_STRING;
        }

        return (string) new Code( $string, $language, $type, $attributes );
    }
}