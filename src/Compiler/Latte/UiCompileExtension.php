<?php

declare( strict_types = 1 );

namespace Northrook\UI\Compiler\Latte;

use Latte;
use Latte\Compiler\Node;
use Latte\Compiler\Nodes\AuxiliaryNode;
use Latte\Compiler\Nodes\Html\ElementNode;
use Latte\Compiler\Nodes\Php\ExpressionNode;
use Latte\Compiler\Nodes\TemplateNode;
use Latte\Compiler\NodeTraverser;
use Northrook\HTML\Element\Tag;
use Northrook\UI\Compiler\NodeCompilerMethods;
use Northrook\UI\Component\Breadcrumbs;
use Northrook\UI\Component\Notification;
use Northrook\UI\Element\Button;
use Northrook\UI\Element\Code;
use Northrook\UI\Element\Heading;
use Northrook\UI\Element\Image;
use Northrook\UI\IconPack;
use Northrook\UI\Node\Anchor;
use Northrook\UI\RenderRuntime;
use Symfony\Contracts\Cache\CacheInterface;


final class UiCompileExtension extends Latte\Extension
{
    use NodeCompilerMethods;


    public readonly RenderRuntime $runtime;
    public readonly IconPack      $iconPack;

    public function __construct(
        ?CacheInterface $cacheAdapter = null,
        ?IconPack       $iconPack = null,
        array           $runtimeRenderCallback = [],
    )
    {
        $this->runtime  = new RenderRuntime( $cacheAdapter, $runtimeRenderCallback );
        $this->iconPack = $iconPack ?? new IconPack();
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

        $parsed = match ( true ) {
            $this::isHeading( $node )           => Heading::nodeCompiler( $node ),
            $this::isImage( $node )             => Image::nodeCompiler( $node ),
            $this::isElement( $node, 'a' )      => Anchor::nodeCompiler( $node ),
            $this::isElement( $node, 'button' ) => Button::nodeCompiler( $node ),
            $this::isElement( $node, 'code' )   => Code::nodeCompiler( $node ),
            default                             => null
        };

        return $parsed ?? match ( $node->name ) {
            'ui:breadcrumbs'              => Breadcrumbs::nodeCompiler( $node ),
            'ui:notification', 'ui:toast' => Notification::nodeCompiler( $node ),
            default                       => $node
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