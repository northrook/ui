<?php

namespace Northrook\UI\Compiler;

// :: The base Component, will be extended by a RuntimeComponent

use Latte\Runtime\Html;
use Latte\Runtime\HtmlStringable;
use Northrook\HTML\Element\Attributes;
use Northrook\Latte;
use Northrook\UI\Compiler\Latte\RuntimeRenderInterface;
use Northrook\UI\RenderRuntime;
use function Northrook\classBasename;
use function Northrook\hashKey;


/**
 *
 * @author Martin Nielsen <mn@northrook.com>
 */
abstract class Component implements RuntimeRenderInterface
{
    use NodeCompilerMethods;


    /**
     * @var ?string Manually set the Component Type - will be derived from ClassName otherwise
     */
    protected const ?string TYPE = null;
    public readonly Attributes $attributes;
    protected readonly string  $templateType;

    /**
     * @param array  $attributes
     */
    public function __construct(
        array $attributes = [],
    )
    {
        $this->attributes   = new Attributes( $attributes );
        $this->templateType = \strtolower( $this::TYPE ?? classBasename( $this::class ) );
    }

    /**
     * @return string
     */
    abstract protected function render() : string;

    final public function attr( mixed ...$inject ) : ?HtmlStringable
    {
        return new Html( \implode( ' ', $this->attributes->merge( $inject )->toArray() ) );
    }

    final public function __toString() : string
    {
        RenderRuntime::registerInvocation( $this::class );
        return $this->render();
    }

    final protected function latte( string $template, array $attributes = [] ) : string
    {
        return Latte::render(
            template       : $template,
            parameters     : [ $this->templateType => $this ] + $attributes,
            postProcessing : false,
        );
    }

    final protected function uniqueTemplateId() : string
    {
        return hashKey( [ $this, \spl_object_id( $this ) ] );
    }

    /**
     * Returns an array of all CSS and JS assets.
     *
     * @return string[]
     */
    abstract static public function getAssets() : array;
}