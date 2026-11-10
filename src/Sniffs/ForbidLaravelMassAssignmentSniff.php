<?php

declare(strict_types = 1);

namespace Milewski\ECS\Sniffs;

use Milewski\ECS\TokenAnalyzer\LaravelProjectDetector;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\Property;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionProperty;
use SplFileInfo;

final class ForbidLaravelMassAssignmentSniff implements Sniff
{
    private const string MODEL = 'Illuminate\\Database\\Eloquent\\Model';

    private const string BUILDER = 'Illuminate\\Database\\Eloquent\\Builder';

    private const string RELATION = 'Illuminate\\Database\\Eloquent\\Relations\\Relation';

    private const string COLLECTION = 'Illuminate\\Database\\Eloquent\\Collection';

    private const string GUIDANCE = 'Assign each model attribute explicitly, then call save().';

    private const array MASS_ASSIGNMENT_METHODS = [
        'fill',
        'forcefill',
        'create',
        'createquietly',
        'forcecreate',
        'forcecreatequietly',
        'createorfirst',
        'firstorcreate',
        'firstornew',
        'update',
        'updatequietly',
        'updateorfail',
        'updateorcreate',
        'createmany',
        'createmanyquietly',
        'forcecreatemany',
        'forcecreatemanyquietly',
        'make',
        'makemany',
        'newinstance',
        'setrawattributes',
        'unguard',
        'unguarded',
        'fillable',
        'mergefillable',
        'guard',
        'mergeguarded',
        'withattributes',
    ];

    /**
     * @var array<string, ClassLike>
     */
    private array $classes = [];

    public function register(): array
    {
        return [ T_OPEN_TAG ];
    }

    public function process(File $phpcsFile, int $stackPtr): ?int
    {
        if (new LaravelProjectDetector()->supports(new SplFileInfo($phpcsFile->getFilename())) === false) {
            return $phpcsFile->numTokens;
        }

        $source = $phpcsFile->getTokensAsString(0, $phpcsFile->numTokens);
        $statements = new ParserFactory()->createForNewestSupportedVersion()->parse($source) ?? [];
        $statements = new NodeTraverser(new NameResolver())->traverse($statements);
        $this->classes = [];

        foreach (new NodeFinder()->findInstanceOf($statements, ClassLike::class) as $class) {

            if (isset($class->namespacedName)) {
                $this->classes[ strtolower($class->namespacedName->toString()) ] = $class;
            }

        }

        $variables = [];

        foreach ($statements as $statement) {
            $this->inspect($phpcsFile, $statement, null, $variables);
        }

        return $phpcsFile->numTokens;
    }

    /**
     * @param array<string, string|null> $variables
     */
    private function inspect(File $file, Node $node, ?ClassLike $class, array &$variables): void
    {
        if ($node instanceof ClassLike) {

            $class = $node;
            $variables = [];

        }

        if ($node instanceof FunctionLike) {

            if ($node instanceof Node\Stmt\ClassMethod || $node instanceof Node\Stmt\Function_) {
                $variables = [];
            }

            foreach ($node->getParams() as $parameter) {

                if (is_string($parameter->var->name)) {
                    $variables[ $parameter->var->name ] = $this->typeName($parameter->type, $class);
                }

            }

        }

        if ($node instanceof Assign) {

            if ($node->var instanceof Variable) {

                if (is_string($node->var->name)) {
                    $variables[ $node->var->name ] = $this->expressionType($node->expr, $class, $variables);
                }

            }

        }

        if ($node instanceof Node\Stmt\Foreach_) {

            if ($node->valueVar instanceof Variable) {

                if (is_string($node->valueVar->name)) {

                    $type = $this->expressionType($node->expr, $class, $variables);
                    $variables[ $node->valueVar->name ] = $type === self::COLLECTION ? self::MODEL : null;

                }

            }

        }

        if ($node instanceof Property) {

            if ($this->isEloquent($this->className($class))) {

                foreach ($node->props as $property) {
                    $this->checkConfiguration($file, $property, $property->name->toString(), $property->default);
                }

            }

        }

        if ($node instanceof Node\Attribute) {

            $attribute = strtolower($node->name->toString());

            if ($attribute === 'illuminate\\database\\eloquent\\attributes\\unguarded') {
                $this->report($file, $node, 'Unguarded models are forbidden.');
            }

            if ($attribute === 'illuminate\\database\\eloquent\\attributes\\fillable') {
                $this->checkConfiguration($file, $node, 'fillable', $this->firstArgument($node->args));
            }

            if ($attribute === 'illuminate\\database\\eloquent\\attributes\\guarded') {
                $this->checkConfiguration($file, $node, 'guarded', $this->firstArgument($node->args));
            }

        }

        if ($node instanceof Assign || $node instanceof Expr\AssignOp) {

            $target = $node->var;
            $value = $node instanceof Assign ? $node->expr : null;

            while ($target instanceof Expr\ArrayDimFetch) {

                $target = $target->var;
                $value = null;

            }

            if ($target instanceof PropertyFetch) {

                if ($target->name instanceof Identifier) {

                    if ($this->isEloquent($this->expressionType($target->var, $class, $variables))) {
                        $this->checkConfiguration($file, $target, $target->name->toString(), $value);
                    }

                }

            }

            if ($target instanceof StaticPropertyFetch) {

                if ($target->class instanceof Name) {

                    if ($this->isEloquent($this->typeName($target->class, $class))) {

                        if ($target->name instanceof Node\VarLikeIdentifier) {
                            $this->checkConfiguration($file, $target, $target->name->toString(), $value);
                        }

                    }

                }

            }

        }

        if ($node instanceof New_) {

            if ($this->eloquentType($this->expressionType($node, $class, $variables)) === self::MODEL) {

                if ($node->args !== []) {
                    $this->report($file, $node, 'Passing attributes to a model constructor is forbidden.');
                }

            }

        }

        if ($node instanceof StaticCall || $node instanceof MethodCall || $node instanceof NullsafeMethodCall) {

            if ($node->name instanceof Identifier) {

                $receiver = $node instanceof StaticCall ? $node->class : $node->var;
                $type = $receiver instanceof Name
                    ? $this->typeName($receiver, $class)
                    : $this->expressionType($receiver, $class, $variables);

                if ($this->isMassAssignment(strtolower($node->name->toString()), $type)) {

                    $this->report(
                        file: $file,
                        node: $node->name,
                        message: sprintf('Eloquent %s() mass assignment is forbidden.', $node->name),
                    );

                }

            }

        }

        foreach ($node->getSubNodeNames() as $name) {

            $children = $node->{$name};

            if ($children instanceof Node) {
                $children = [ $children ];
            }

            if (is_array($children) === false) {
                continue;
            }

            foreach ($children as $child) {

                if ($child instanceof Node === false) {
                    continue;
                }

                if ($child instanceof FunctionLike || $child instanceof ClassLike) {

                    $scope = $variables;

                    $this->inspect($file, $child, $class, $scope);

                    continue;

                }

                $this->inspect($file, $child, $class, $variables);

            }

        }
    }

    private function isMassAssignment(string $method, ?string $type): bool
    {
        if ($this->isEloquent($type) === false) {
            return false;
        }

        if (in_array($method, self::MASS_ASSIGNMENT_METHODS, true) === false) {
            return false;
        }

        if ($method === 'update') {
            return $this->eloquentType($type) === self::MODEL;
        }

        return true;
    }

    private function checkConfiguration(File $file, Node $node, string $name, ?Expr $value): void
    {
        if ($name === 'guarded') {

            if ($this->guardsEveryAttribute($value)) {
                return;
            }

            $this->report(
                file: $file,
                node: $node,
                message: 'Models must guard every attribute with $guarded = [\'*\']; empty or partial guards are forbidden.',
            );

        }

        if ($name === 'fillable') {

            if ($value instanceof Array_) {

                if ($value->items === []) {
                    return;
                }

            }

            $this->report($file, $node, 'Declaring mass assignable attributes with $fillable is forbidden.');

        }

        if ($name === 'unguarded') {

            if ($value instanceof ConstFetch) {

                if (strtolower($value->name->toString()) === 'false') {
                    return;
                }

            }

            $this->report($file, $node, 'Enabling $unguarded is forbidden.');

        }
    }

    private function guardsEveryAttribute(?Expr $value): bool
    {
        if ($value instanceof Array_ === false) {
            return false;
        }

        if (count($value->items) !== 1) {
            return false;
        }

        $item = $value->items[ 0 ];

        if ($item?->key !== null) {
            return false;
        }

        if ($item?->unpack === true) {
            return false;
        }

        if ($item?->value instanceof String_ === false) {
            return false;
        }

        return $item->value->value === '*';
    }

    /**
     * @param array<string, string|null> $variables
     */
    private function expressionType(Node $node, ?ClassLike $class, array $variables): ?string
    {
        if ($node instanceof Variable) {

            if ($node->name === 'this') {
                return $this->className($class);
            }

            return is_string($node->name) ? ($variables[ $node->name ] ?? null) : null;

        }

        if ($node instanceof New_) {

            if ($node->class instanceof Name) {
                return $this->typeName($node->class, $class);
            }

            return $node->class instanceof Class_ ? $this->className($node->class) : null;

        }

        if ($node instanceof PropertyFetch) {

            if ($node->name instanceof Identifier) {

                $owner = $this->expressionType($node->var, $class, $variables);
                $declaration = $this->classes[ strtolower($owner ?? '') ] ?? null;

                if ($declaration !== null) {

                    foreach ($declaration->getMethod('__construct')?->params ?? [] as $parameter) {

                        if ($parameter->flags !== 0) {

                            if ($parameter->var->name === $node->name->toString()) {
                                return $this->typeName($parameter->type, $declaration);
                            }

                        }

                    }

                    return $this->typeName($declaration->getProperty($node->name->toString())?->type, $declaration);

                }

                if ($owner !== null) {

                    if (class_exists($owner)) {

                        if (property_exists($owner, $node->name->toString())) {

                            $type = new ReflectionProperty($owner, $node->name->toString())->getType();

                            return $type instanceof ReflectionNamedType ? $type->getName() : null;

                        }

                    }

                }

            }

        }

        if ($node instanceof MethodCall || $node instanceof NullsafeMethodCall || $node instanceof StaticCall) {

            if ($node->name instanceof Identifier) {

                $receiver = $node instanceof StaticCall ? $node->class : $node->var;
                $owner = $receiver instanceof Name ? $this->typeName(
                    type: $receiver,
                    class: $class,
                ) : $this->expressionType($receiver, $class, $variables);

                $method = strtolower($node->name->toString());
                $declaration = $this->classes[ strtolower($owner ?? '') ] ?? null;
                $returnType = $declaration?->getMethod($method)?->returnType;

                if ($returnType !== null) {
                    return $this->typeName($returnType, $declaration);
                }

                if ($owner !== null) {

                    if (class_exists($owner)) {

                        if (method_exists($owner, $method)) {

                            $type = new ReflectionMethod($owner, $method)->getReturnType();

                            if ($type instanceof ReflectionNamedType) {
                                return in_array($type->getName(), [ 'static', 'self' ], true) ? $owner : $type->getName();
                            }

                        }

                    }

                }

                if ($this->isEloquent($owner)) {

                    if (in_array($method, [ 'get', 'all' ], true)) {
                        return self::COLLECTION;
                    }

                    if (in_array($method, [ 'pluck', 'factory', 'count', 'exists', 'tosql', 'tobase', 'getquery' ], true)) {
                        return null;
                    }

                    if (in_array($method, [ 'first', 'firstorfail', 'firstwhere', 'firstor', 'find', 'findorfail', 'findornew', 'findor', 'sole', 'getmodel' ], true)) {
                        return self::MODEL;
                    }

                    if (in_array($method, [ 'hasone', 'hasmany', 'belongsto', 'belongstomany', 'morphone', 'morphmany', 'morphto', 'morphtomany' ], true)) {
                        return self::RELATION;
                    }

                    if (in_array($method, [ 'query', 'newquery', 'newmodelquery', 'newquerywithoutscopes', 'newquerywithoutrelationships', 'newqueryforrestoration' ], true)) {
                        return self::BUILDER;
                    }

                    if (str_starts_with($method, 'where')) {
                        return self::BUILDER;
                    }

                    if (str_starts_with($method, 'orwhere')) {
                        return self::BUILDER;
                    }

                    if ($node instanceof StaticCall) {
                        return self::BUILDER;
                    }

                    return $owner;

                }

                if ($owner === self::COLLECTION) {

                    if (in_array($method, [ 'first', 'last', 'sole', 'find', 'findorfail' ], true)) {
                        return self::MODEL;
                    }

                }

            }

        }

        return null;
    }

    private function typeName(?Node $type, ?ClassLike $class): ?string
    {
        if ($type instanceof Node\NullableType) {
            return $this->typeName($type->type, $class);
        }

        if ($type instanceof Node\UnionType || $type instanceof Node\IntersectionType) {

            foreach ($type->types as $member) {

                $name = $this->typeName($member, $class);

                if ($this->isEloquent($name)) {
                    return $name;
                }

            }

            return null;

        }

        if ($type instanceof Name) {

            if (in_array(strtolower($type->toString()), [ 'self', 'static' ], true)) {
                return $this->className($class);
            }

            if (strtolower($type->toString()) === 'parent') {
                return $class instanceof Class_ ? $class->extends?->toString() : null;
            }

            return $type->toString();

        }

        return null;
    }

    private function className(?ClassLike $class): ?string
    {
        if (isset($class->namespacedName)) {
            return $class->namespacedName->toString();
        }

        return $class instanceof Class_ ? $class->extends?->toString() : null;
    }

    private function isEloquent(?string $name): bool
    {
        return $this->eloquentType($name) !== null;
    }

    /**
     * @param list<string> $visited
     */
    private function eloquentType(?string $name, array $visited = []): ?string
    {
        if ($name === null) {
            return null;
        }

        $key = strtolower($name);

        if (in_array($key, $visited, true)) {
            return null;
        }

        if (in_array($key, array_map(strtolower(...), [ self::MODEL, 'Illuminate\\Foundation\\Auth\\User', 'Illuminate\\Database\\Eloquent\\Relations\\Pivot', 'Illuminate\\Database\\Eloquent\\Relations\\MorphPivot' ]), true)) {
            return self::MODEL;
        }

        if (in_array($key, array_map(strtolower(...), [ self::BUILDER, 'Illuminate\\Contracts\\Database\\Eloquent\\Builder' ]), true)) {
            return self::BUILDER;
        }

        if (str_starts_with($key, 'illuminate\\database\\eloquent\\relations\\')) {
            return self::RELATION;
        }

        $declaration = $this->classes[ $key ] ?? null;

        if ($declaration instanceof Class_) {
            return $this->eloquentType($declaration->extends?->toString(), [ ...$visited, $key ]);
        }

        foreach ([ self::MODEL, self::BUILDER, self::RELATION ] as $parent) {

            if (is_a($name, $parent, true)) {
                return $parent;
            }

        }

        return null;
    }

    /**
     * @param list<Arg> $arguments
     */
    private function firstArgument(array $arguments): ?Expr
    {
        return $arguments[ 0 ]->value ?? null;
    }

    private function report(File $file, Node $node, string $message): void
    {
        $pointer = 0;
        $offset = 0;

        foreach ($file->getTokens() as $index => $token) {

            if ($offset > $node->getStartFilePos()) {
                break;
            }

            $pointer = $index;
            $offset += strlen($token[ 'content' ]);

        }

        $file->addError($message . ' ' . self::GUIDANCE, $pointer, 'Found');
    }
}
