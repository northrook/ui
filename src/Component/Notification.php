<?php

namespace Northrook\UI\Component;

use Latte\Compiler\Nodes\AuxiliaryNode;
use Northrook\HTML\Element;
use Northrook\HTML\Element\Attributes;
use Northrook\HTML\Format;
use Northrook\Time;
use Northrook\UI\Compiler\AbstractComponent;
use Northrook\UI\Compiler\NodeCompiler;
use Northrook\UI\IconPack;
use Northrook\UI\RenderRuntime;
use function Northrook\filterHtmlText;
use function Northrook\normalizeKey;


class Notification extends AbstractComponent
{
    private array $instances = [];

    public readonly string             $type;
    public readonly string             $message;
    public readonly ?string            $description;
    public readonly Element\Attributes $attributes;
    public ?int                        $timeout = null;

    protected readonly Element $component;

    public function __construct(
        string              $type = 'notice',
        ?string             $message = null,
        ?string             $description = null,
        null | int | string $timeout = null,
        array               $attributes = [],
    )
    {
        $this->type        = filterHtmlText( $type );
        $this->message     = filterHtmlText( $message ?? \ucfirst( $this->type ) );
        $this->description = $description ? filterHtmlText( $description ) : null;
        $this->setTimeout( $timeout );
        $this->component  = new Element( 'toast', $attributes );
        $this->attributes = $this->component->attributes;
        $this->component->class( 'notification', $type, prepend : true );

        $this->instances[] = new Time();
    }

    public function setTimeout( null | int | string $timeout ) : self
    {
        if ( !\is_numeric( $timeout ) ) {
            $timeout = ( \strtotime( $timeout, 0 ) ) * 100;
        }
        $this->timeout = $timeout;
        return $this;
    }

    private function closeButton( string $label = 'Close' ) : string
    {
        $attributes = [
            'class'      => 'close',
            'aria-label' => $label,
        ];

        return Element::button(
            content    : '<i class="close"></i>',
            attributes : $attributes,
        );
    }

    protected function build() : string
    {
        $type        = normalizeKey( $this->type );
        $icon        = IconPack::get( $this->type, 'notice' );
        $message     = Format::inline( $this->message );
        $description = Format::newline( $this->description );

        $description = $description ? Element::details(
            summary    : 'Description',
            content    : $description,
            attributes : [ 'class' => 'description' ],
        ) : null;

        $content = <<<HTML
            <button class="close" aria-label="Close" type="button">
                <i class="close"></i>
            </button>
            <output role="status">
              <i class="status">
                {$icon}
                <span class="status-type">
                  {$type}
                </span>
                <time datetime="{$this->timestamp( DATE_W3C )}">
                  {$this->timestampWhen()}
                </time>
              </i>
              <span class="message">
                {$message}
              </span>
            </output>
            {$description}
        HTML;

        $this->component->attributes(
            'timeout',
            ( $this->timeout < 3500 )
                ? 3500 : $this->timeout,
        );

        return ( string ) $this->component->content( $content );
    }

    /**
     * Retrieve the {@see Timestamp} object.
     *
     * @return Time
     * @internal
     */
    private function timestamp() : Time
    {
        return $this->instances[ \array_key_last( $this->instances ) ];
    }

    private function timestampWhen() : string
    {
        $now       = time();
        $unix      = $this->timestamp()->unixTimestamp;
        $timestamp = $this->timestamp()->format( Time::FORMAT_HUMAN, true );

        // If this occurred less than 5 seconds ago, count it as now
        if ( ( $now - $unix ) < 5 ) {
            return '<span class="datetime-when">Now</span><span class="datetime-timestamp">' . $timestamp . '</span>';
        }
        // If this occurred less than 12 hours ago, it is 'today'
        if ( ( $now - $unix ) < 43200 ) {
            return '<span class="datetime-when">Today</span><span class="datetime-timestamp">' . $timestamp . '</span>';
        }
        // Otherwise print the whole day
        return $timestamp;
    }

    public static function nodeCompiler( NodeCompiler $node ) : AuxiliaryNode
    {
        return RenderRuntime::auxiliaryNode(
            renderName : Notification::class,
            arguments  : $node->properties(
                             [ 'type' => 'notice' ], 'message', 'description', 'timeout',
                         ),
        );
    }

    public static function runtimeRender(
        string              $type = 'notice',
        ?string             $message = null,
        ?string             $description = null,
        null | int | string $timeout = null,
        array               $attributes = [],
    ) : string
    {
        return (string) new Notification( ... get_defined_vars() );
    }

    static public function getAssets() : array
    {
        return [
            __DIR__ . '/Notification/notification.css',
            __DIR__ . '/Notification/notification.js',
        ];
    }

}