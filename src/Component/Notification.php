<?php

namespace Northrook\UI\Component;

use Latte\Compiler\Node;
use Latte\Compiler\Nodes\AuxiliaryNode;
use Latte\Runtime\Html;
use Latte\Runtime\HtmlStringable;
use Northrook\Logger\Log;
use Northrook\Time;
use Northrook\Trait\PropertyAccessor;
use Northrook\UI\Compiler\Component;
use Northrook\UI\Compiler\NodeCompiler;
use Northrook\UI\IconPack;
use Northrook\UI\Latte\RenderRuntime;
use function Northrook\hashKey;
use function Northrook\normalizeKey;


/**
 *
 * @property-read string  $type          One of 'info', 'success', 'warning', 'danger', or 'notice'
 * @property-read string  $icon          Built-in SVG icons for each type
 * @property-read string  $message       The main message to show the user
 * @property-read ?string $description   [optional] Provide more details.
 * @property-read ?int    $timeout       How long before the message should time out, in milliseconds
 * @property-read Time    $timestamp     The most recent timestamp object
 *
 * @property-read string  $key           Unique key to identify this object internally
 * @property-read array   $instances     // All the times this exact Notification has been created since it was last rendered
 * @property-read int     $unixTimestamp // The most recent timestamps' unix int
 * @property-read ?string $when
 *
 * @author Martin Nielsen <mn@northrook.com>
 */
final class Notification extends Component
{
    use PropertyAccessor;


    protected const ?string  TYPE = 'notification';

    private array $instances  = [];
    private array $parameters = [
        'type'        => null,
        'message'     => null,
        'description' => null,
        'timeout'     => null,
    ];

    final public function __construct(
        array               $attributes = [],
        string              $type = 'notice',
        ?string             $message = null,
        ?string             $description = null,
        null | int | string $timeout = null,
    )
    {
        parent::__construct( $attributes );

        $this->attributes->add( 'class', "notification $type" );

        $this->parameters[ 'type' ]        = normalizeKey( $type );
        $this->parameters[ 'message' ]     =
            $message ? \trim( $message ) : throw new \InvalidArgumentException( 'A message is required.' );
        $this->parameters[ 'description' ] = $description ? trim( $description ) : null;
        if ( $timeout ) {
            // TODO : Expand this in core\functions
            if ( !\is_numeric( $timeout ) ) {
                $timeout = ( \strtotime( $timeout, 0 ) ) * 100;
            }
            $this->attributes->set( 'timeout', ( $timeout < 3500 ) ? 3500 : $timeout );
        }

        $this->instances[] = new Time();
    }

    public function __get( string $property ) : null | string | int | array | HtmlStringable
    {
        return match ( $property ) {
            'key'           => hashKey( $this->parameters ),
            'type'          => $this->parameters[ 'type' ],
            'icon'          => IconPack::get( $this->type, 'notice' ),
            'timeout'       => $this->parameters[ 'timeout' ],
            'message'       => $this->parameters[ 'message' ],
            'description'   => $this->parameters[ 'description' ],
            'instances'     => $this->instances,
            'timestamp'     => $this->getTimestamp(),
            'unixTimestamp' => $this->getTimestamp()->unixTimestamp,
            'when'          => new Html( $this->timestampWhen() ),
        };
    }

    protected function render() : string
    {
        return $this->latte( __DIR__ . '/Notification/notification.latte' );
    }

    public function setTimeout( int $timeout ) : self
    {
        $this->attributes->set( 'timeout', $timeout );
        return $this;
    }

    static public function getAssets() : array
    {
        return [
            __DIR__ . '/Notification/notification.css',
            __DIR__ . '/Notification/notification.js',
        ];
    }

    /**
     * Format the most recent timestamp object
     *
     * The {@see \Northrook\Time} object provides commonly used formats as constants.
     *
     * @link https://www.php.net/manual/en/datetime.format.php#refsect1-datetime.format-parameters Formatting Documentation
     *
     * @param string  $format
     *
     * @return string
     */
    public function timestamp( string $format = Time::FORMAT_HUMAN ) : string
    {
        return $this->getTimestamp()->format( $format );
    }

    /**
     * Retrieve the {@see Timestamp} object.
     *
     * @return Time
     * @internal
     */
    private function getTimestamp() : Time
    {
        return $this->instances[ \array_key_last( $this->instances ) ];
    }

    private function timestampWhen() : string
    {
        $now       = time();
        $unix      = $this->getTimestamp()->unixTimestamp;
        $timestamp = $this->getTimestamp()->format( Time::FORMAT_HUMAN, true );

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

    /**
     * How many times has this been triggered since the last render?
     *
     * @return int
     */
    public function count() : int
    {
        return count( $this->instances );
    }

    public static function nodeCompiler( Node $node ) : AuxiliaryNode
    {
        $node = new NodeCompiler( $node );
        [ $attributes, $variables ] = $node->resolveComponentArguments();
        dump( $attributes, $variables );
        return RenderRuntime::auxiliaryNode(
            Notification::class,
            [
                $attributes,
                $variables,
            ],
        );
    }

    public static function runtimeRender( array $attributes = [], array $variables = [] ) : string
    {
        $arguments = [
            'type'        => null,
            'message'     => null,
            'description' => null,
            'timeout'     => null,

        ];
        foreach ( $attributes as $variable => $value ) {
            if ( \array_key_exists( $variable, $arguments ) ) {
                $arguments[ $variable ] = $value;
                unset( $attributes[ $variable ] );
            }
        }
        // unset( $variable, $value );
        Log::critical( print_r( $attributes, true ) );
        Log::critical( print_r( $arguments, true ) );

        return (string) new Notification( $attributes, ... $arguments );
    }
}
// public static function runtimeRender(
//     array   $attributes = [],
//     string  $type = 'notice',
//     ?string $message = null,
//     ?string $description = null,
//     ?int    $timeout = null,
// ) : Notification
// {
//     foreach ( $attributes as $variable => $value ) {
//         if ( \array_key_exists( $variable, \get_defined_vars() ) ) {
//             $$variable = $value;
//             unset( $attributes[ $variable ] );
//         }
//     }
//     unset( $variable, $value );
//
//     return new Notification( $attributes, $type, $message, $description, $timeout );
// }