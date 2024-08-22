<?php

namespace Northrook\UI\Compiler;

// :: The base Component, will be extended by a RuntimeComponent

use Latte\Runtime\Html;
use Latte\Runtime\HtmlStringable;
use Northrook\HTML\Element\Attributes;
use Northrook\Latte;
use function Northrook\classBasename;
use function Northrook\hashKey;

abstract class Component implements HtmlStringable, ComponentInterface
{
    /**
     * @var ?string Manually set the Component Type - will be derived from ClassName otherwise
     */
    protected const ?string TYPE = null;
    protected readonly string $templateType;
    protected readonly string $templatePath;

    public readonly Attributes $attributes;

    /**
     * @param array  $attributes
     */
    public function __construct(
        array $attributes = [],
    ) {
        $this->attributes   = new Attributes( $attributes );
        $this->templateType = \strtolower( $this::TYPE ?? classBasename( $this::class ) );
    }

    /**
     * @return string
     */
    abstract protected function render() : string;

    /**
     * Returns an array of all CSS and JS assets.
     *
     * @return string[]
     */
    abstract static public function getAssets() : array;

    final public function attr( mixed ...$inject ) : ?HtmlStringable {
        return new Html( \implode( ' ', $this->attributes->merge( $inject )->toArray() ) );
    }

    function __toString() : string {
        return $this->render();
    }

    final protected function latte( string $template, array $attributes = [] ) : string {
        return Latte::render(
            template       : $template,
            parameters     : [ $this->templateType => $this ] + $attributes,
            postProcessing : false,
        );
    }

    final protected function uniqueTemplateId() : string {
        return hashKey( [ $this, \spl_object_id( $this ) ] );
    }
}