<?php

declare(strict_types = 1);

namespace Milewski\ECS\TokenAnalyzer;

use PhpParser\Error;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Stmt\Return_;
use PhpParser\ParserFactory;
use ReflectionMethod;

final class ReturnedClosureAnalyzer
{
    /**
     * @var array<string, ArrowFunction|Closure|null>
     */
    private array $closures = [];

    public function find(ReflectionMethod $method): Closure|ArrowFunction|null
    {
        $file = $method->getFileName();

        if ($file === false) {
            return null;
        }

        if (is_file($file) === false) {
            return null;
        }

        $key = sprintf('%s:%s:%s', $file, $method->getStartLine(), $method->getName());

        if (array_key_exists($key, $this->closures)) {
            return $this->closures[ $key ];
        }

        $this->closures[ $key ] = null;
        $lines = file($file);

        if ($lines === false) {
            return null;
        }

        $source = implode('', array_slice($lines, $method->getStartLine() - 1, $method->getEndLine() - $method->getStartLine() + 1));

        try {

            $statements = new ParserFactory()
                ->createForNewestSupportedVersion()
                ->parse("<?php\nclass MacroFactory {\n" . $source . "\n}");

        } catch (Error) {

            return null;

        }

        $declaration = $statements[ 0 ]->getMethod($method->getName());

        if ($declaration === null) {
            return null;
        }

        if (count($declaration->stmts ?? []) !== 1) {
            return null;
        }

        $return = $declaration->stmts[ 0 ];

        if ($return instanceof Return_ === false) {
            return null;
        }

        if ($return->expr instanceof Closure) {
            return $this->closures[ $key ] = $return->expr;
        }

        if ($return->expr instanceof ArrowFunction) {
            return $this->closures[ $key ] = $return->expr;
        }

        return null;
    }
}
