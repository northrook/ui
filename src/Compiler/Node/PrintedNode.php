<?php

namespace Northrook\UI\Compiler\Node;

use Latte\Compiler\Node;
use Latte\Compiler\PrintContext;
use Latte\Essential\Nodes\PrintNode;
use Northrook\Logger\Log;
use function Northrook\pregExtract;
use const Northrook\EMPTY_STRING;


final class PrintedNode implements \Stringable
{

    public readonly string $value;

    public readonly string $type;

    public readonly bool    $isExpression;
    public readonly ?string $variable;
    public readonly ?string $expression;

    public function __construct(
        private readonly Node $node,
        private ?PrintContext $context = null,
    )
    {
        $this->context ??= new PrintContext();
        match ( true ) {
            $node instanceof PrintNode => $this->parsePrintNode(),
            default                    => $this->parseNode(),
        };
        $this->isExpression ??= false;
        $this->variable     ??= null;
    }

    private function parseNode() : void
    {
        $this->value = \str_ireplace( [ "echo '", "';" ], EMPTY_STRING, $this->print() );
    }

    private function parsePrintNode() : void
    {
        $this->value        = \trim( \preg_replace( '#echo (.+) /\*.+?;#', "$1 ", $this->print() ) );
        $this->variable     = pregExtract( '#\$(\w+)(?=\s|:|\?|$)#', $this->value );
        $this->expression     = pregExtract( '#\$(.+?)(?=\)|$)#', $this->value );
        $this->isExpression = true;
    }

    public function __toString() : string
    {
        return $this->value;
    }

    private function print( ?Node $node = null, ?PrintContext $context = null ) : string
    {
        try {
            $node    ??= $this->node;
            $context ??= $this->context;
            return $node->print( $context );
        }
        catch ( \Exception $exception ) {
            Log::exception( $exception );
        }
        return EMPTY_STRING;
    }
}