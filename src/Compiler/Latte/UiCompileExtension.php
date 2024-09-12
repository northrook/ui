<?php

declare( strict_types = 1 );

namespace Northrook\UI\Compiler\Latte;

use Latte;
use Latte\Compiler\Node;
use Latte\Compiler\Nodes\Html\ElementNode;
use Latte\Compiler\Nodes\Php\ExpressionNode;
use Latte\Compiler\Nodes\TemplateNode;
use Latte\Compiler\NodeTraverser;
use Northrook\UI\Compiler\NodeCompiler;
use Northrook\UI\Compiler\NodeCompilerMethods;
use Northrook\UI\Component\Breadcrumbs;
use Northrook\UI\Component\Button;
use Northrook\UI\Component\Code;
use Northrook\UI\Component\Heading;
use Northrook\UI\Component\Icon;
use Northrook\UI\Component\Menu;
use Northrook\UI\Component\Notification;
use Northrook\UI\RenderRuntime;
use Symfony\Contracts\Cache\CacheInterface;


final class UiCompileExtension extends Latte\Extension
{
    use NodeCompilerMethods;


    public readonly RenderRuntime $runtime;

    public function __construct(
        ?CacheInterface $cacheAdapter = null,
        array           $runtimeRenderCallback = [],
    )
    {
        $this->runtime = new RenderRuntime( $cacheAdapter, $runtimeRenderCallback );
    }

    public function getPasses() : array
    {
        return [
            $this::class => fn( TemplateNode $templateNode ) => (
            new NodeTraverser() )->traverse( $templateNode, [ $this, 'parseTemplate', ] ),
        ];
    }

    public function parseTemplate( Node $node ) : int | Node
    {
        if ( $node instanceof ExpressionNode ) {
            return NodeTraverser::DontTraverseChildren;
        }

        if ( !$node instanceof ElementNode ) {
            return $node;
        }

        $component = new NodeCompiler( $node );

        $parsed = match ( true ) {
            $this::isElement( $node, 'code' )   => Code::nodeCompiler( $component ),
            $this::isHeading( $node )           => Heading::nodeCompiler( $component ),
            // $this::isImage( $node )             => Image::nodeCompiler( $node ),
            // $this::isElement( $node, 'a' )      => Anchor::nodeCompiler( $node ),
            // $this::isElement( $node, 'code' )   => Code::nodeCompiler( $node ),
            $component->is( 'button' ) => Button::nodeCompiler( $component ),
            $component->is( 'icon' )   => Icon::nodeCompiler( $component ),
            $component->is( 'menu' )     => Menu::nodeCompiler( $component ),
            $component->is( 'breadcrumbs' )     => Breadcrumbs::nodeCompiler( $component ),
            $component->is( 'ui:notification' ) => Notification::nodeCompiler( $component ),
            default                             => null
        };

        return $parsed ?? match ( $node->name ) {
            // 'ui:breadcrumbs' => Breadcrumbs::nodeCompiler( $component ),
            // 'ui:notification', 'ui:toast' => Notification::nodeCompiler( $node ),
            default => $node
        };
    }

    public
    function getProviders() : array
    {
        return [
            'render' => $this->runtime,
        ];
    }
}