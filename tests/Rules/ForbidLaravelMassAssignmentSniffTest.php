<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Rules;

use Milewski\ECS\Tests\Support\EcsTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Symplify\EasyCodingStandard\Parallel\ValueObject\Bridge;
use Symplify\EasyCodingStandard\SniffRunner\Application\SniffFileProcessor;
use Symplify\EasyCodingStandard\ValueObject\Configuration;

final class ForbidLaravelMassAssignmentSniffTest extends EcsTestCase
{
    #[DataProvider('provideMassAssignmentCalls')]
    public function test_rejects_mass_assignment_calls(string $call, string $method): void
    {
        $errors = $this->processCode(<<<PHP
        <?php
        use Illuminate\Database\Eloquent\Model;
        use Illuminate\Database\Eloquent\Builder;
        use Illuminate\Database\Eloquent\Relations\HasMany;
        class User extends Model {}
        function persist(User \$user, Builder \$builder, HasMany \$relation): void {
            $call;
        }
        PHP);

        $this->assertCount(1, $errors);
        $this->assertSame(
            expected: sprintf('Eloquent %s() mass assignment is forbidden. Assign each model attribute explicitly, then call save().', $method),
            actual: $errors[ 0 ]->getMessage(),
        );

        $this->assertSame(7, $errors[ 0 ]->getLine());
    }

    public static function provideMassAssignmentCalls(): iterable
    {
        foreach ([
            'fill',
            'forceFill',
            'create',
            'createQuietly',
            'forceCreate',
            'forceCreateQuietly',
            'createOrFirst',
            'firstOrCreate',
            'firstOrNew',
            'update',
            'updateQuietly',
            'updateOrCreate',
            'createMany',
            'createManyQuietly',
            'forceCreateMany',
            'forceCreateManyQuietly',
            'make',
            'makeMany',
            'newInstance',
            'setRawAttributes',
            'insert',
            'insertGetId',
            'insertOrIgnore',
            'insertUsing',
            'insertOrIgnoreUsing',
            'upsert',
            'updateOrInsert',
            'unguard',
            'unguarded',
            'fillable',
            'mergeFillable',
            'guard',
            'mergeGuarded',
            'withAttributes',
        ] as $method) {
            yield $method => [ sprintf('User::%s(["name" => "Ada"])', $method), $method ];
        }

        yield 'model instance' => [ '$user->fill(["name" => "Ada"])', 'fill' ];

        yield 'nullsafe instance' => [ '$user?->update(["name" => "Ada"])', 'update' ];

        yield 'case insensitive method' => [ '$user->FILL(["name" => "Ada"])', 'FILL' ];

        yield 'typed builder' => [ '$builder->create(["name" => "Ada"])', 'create' ];

        yield 'typed relationship' => [ '$relation->createMany($rows)', 'createMany' ];

        yield 'query chain' => [ 'User::query()->where("active", true)->update(["name" => "Ada"])', 'update' ];

        yield 'assigned model' => [ '$copy = new User(); $copy->fill($attributes)', 'fill' ];

        yield 'assigned query' => [ '$query = User::query(); $query->update($attributes)', 'update' ];

        yield 'retrieved model' => [ '$found = User::find(1); $found->fill($attributes)', 'fill' ];

        yield 'arrow capture' => [ '$callback = fn (): User => $user->fill($attributes)', 'fill' ];

        yield 'closure capture' => [ '$callback = function () use ($user): void { $user->fill($attributes); }', 'fill' ];

        yield 'first class callable' => [ '$user->fill(...)', 'fill' ];
    }

    #[DataProvider('provideForbiddenConfiguration')]
    public function test_rejects_mass_assignment_configuration(string $body, string $message): void
    {
        $errors = $this->processCode(<<<PHP
        <?php
        use Illuminate\Database\Eloquent\Model;
        class User extends Model {
            $body
        }
        PHP);

        $this->assertCount(1, $errors);
        $this->assertStringContainsString($message, $errors[ 0 ]->getMessage());
        $this->assertStringEndsWith('Assign each model attribute explicitly, then call save().', $errors[ 0 ]->getMessage());
    }

    public static function provideForbiddenConfiguration(): iterable
    {
        yield 'empty guarded' => [ 'protected $guarded = [];', 'empty or partial guards are forbidden' ];

        yield 'long array guarded' => [ 'protected $guarded = array();', 'empty or partial guards are forbidden' ];

        yield 'partial guarded' => [ 'protected $guarded = ["id"];', 'empty or partial guards are forbidden' ];

        yield 'computed guarded' => [ 'protected $guarded = self::GUARDED;', 'empty or partial guards are forbidden' ];

        yield 'wildcard mixed with columns' => [ 'protected $guarded = ["*", "id"];', 'empty or partial guards are forbidden' ];

        yield 'keyed wildcard' => [ 'protected $guarded = ["column" => "*"];', 'empty or partial guards are forbidden' ];

        yield 'unpacked wildcard' => [ 'protected $guarded = [..."*"];', 'empty or partial guards are forbidden' ];

        yield 'dynamic guard entry' => [ 'protected $guarded = [self::GUARD];', 'empty or partial guards are forbidden' ];

        yield 'fillable attributes' => [ 'protected $fillable = ["name"];', '$fillable is forbidden' ];

        yield 'unguarded enabled' => [ 'protected static $unguarded = true;', 'Enabling $unguarded is forbidden' ];

        yield 'runtime guarded' => [ 'function configure(): void { $this->guarded = []; }', 'empty or partial guards are forbidden' ];

        yield 'runtime fillable' => [ 'function configure(): void { $this->fillable = ["name"]; }', '$fillable is forbidden' ];

        yield 'runtime unguarded' => [ 'function configure(): void { static::$unguarded = true; }', 'Enabling $unguarded is forbidden' ];

        yield 'fillable append' => [ 'function configure(): void { $this->fillable[] = "name"; }', '$fillable is forbidden' ];

        yield 'guarded replacement' => [ 'function configure(): void { $this->guarded[0] = "id"; }', 'empty or partial guards are forbidden' ];

        yield 'compound fillable assignment' => [ 'function configure(): void { $this->fillable += ["name"]; }', '$fillable is forbidden' ];
    }

    #[DataProvider('provideModelContexts')]
    public function test_resolves_model_contexts(string $source): void
    {
        $errors = $this->processCode("<?php\n" . $source);

        $this->assertCount(1, $errors);
        $this->assertStringContainsString('forbidden', $errors[ 0 ]->getMessage());
    }

    public static function provideModelContexts(): iterable
    {
        yield 'aliased parent' => [ 'use Illuminate\Database\Eloquent\Model as Record; class User extends Record { protected $guarded = []; }' ];

        yield 'group import' => [ 'use Illuminate\Database\Eloquent\{Model as Record}; class User extends Record { protected $guarded = []; }' ];

        yield 'fully qualified parent' => [ 'class User extends \Illuminate\Database\Eloquent\Model { protected $guarded = []; }' ];

        yield 'custom base model' => [ 'class User extends BaseRecord { protected $guarded = []; } class BaseRecord extends \Illuminate\Database\Eloquent\Model {}' ];

        yield 'authenticatable model' => [ 'class User extends \Illuminate\Foundation\Auth\User { protected $guarded = []; }' ];

        yield 'pivot model' => [ 'class Membership extends \Illuminate\Database\Eloquent\Relations\Pivot { protected $guarded = []; }' ];

        yield 'anonymous model' => [ 'new class extends \Illuminate\Database\Eloquent\Model { protected $guarded = []; };' ];

        yield 'constructor' => [ 'class User extends \Illuminate\Database\Eloquent\Model {} new User(["name" => "Ada"]);' ];

        yield 'named constructor argument' => [ 'class User extends \Illuminate\Database\Eloquent\Model {} new User(attributes: $attributes);' ];

        yield 'self' => [ 'class User extends \Illuminate\Database\Eloquent\Model { function store(): void { self::create($attributes); } }' ];

        yield 'static' => [ 'class User extends \Illuminate\Database\Eloquent\Model { function store(): void { static::create($attributes); } }' ];

        yield 'parent' => [ 'class User extends \Illuminate\Database\Eloquent\Model { function store(): void { parent::create($attributes); } }' ];

        yield 'this' => [ 'class User extends \Illuminate\Database\Eloquent\Model { function store(): void { $this->fill($attributes); } }' ];

        yield 'typed property' => [ 'class User extends \Illuminate\Database\Eloquent\Model {} class Store { private User $user; function store(): void { $this->user->fill($attributes); } }' ];

        yield 'nullable parameter' => [ 'class User extends \Illuminate\Database\Eloquent\Model {} function store(?User $user): void { $user?->fill($attributes); }' ];

        yield 'local return type' => [ 'class User extends \Illuminate\Database\Eloquent\Model {} class Store { function user(): User {} function store(): void { $this->user()->fill($attributes); } }' ];

        yield 'relationship method' => [ 'class User extends \Illuminate\Database\Eloquent\Model { function posts(): \Illuminate\Database\Eloquent\Relations\HasMany {} function store(): void { $this->posts()->create($attributes); } }' ];

        yield 'unguarded attribute' => [ '#[\Illuminate\Database\Eloquent\Attributes\Unguarded] class User extends \Illuminate\Database\Eloquent\Model {}' ];

        yield 'fillable attribute' => [ 'use Illuminate\Database\Eloquent\Attributes\Fillable as Writable; #[Writable(["name"])] class User extends \Illuminate\Database\Eloquent\Model {}' ];

        yield 'guarded attribute' => [ '#[\Illuminate\Database\Eloquent\Attributes\Guarded([])] class User extends \Illuminate\Database\Eloquent\Model {}' ];

        yield 'relationship builder' => [ 'class User extends \Illuminate\Database\Eloquent\Model { function store(): void { $this->hasMany(Post::class)->create($attributes); } }' ];

        yield 'union parameter' => [ 'class User extends \Illuminate\Database\Eloquent\Model {} function store(User|false $user): void { $user->fill($attributes); }' ];

        yield 'intersection parameter' => [ 'class User extends \Illuminate\Database\Eloquent\Model {} function store(User&Contract $user): void { $user->fill($attributes); }' ];

        yield 'promoted property' => [ 'class User extends \Illuminate\Database\Eloquent\Model {} class Store { function __construct(private User $user) {} function store(): void { $this->user->fill($attributes); } }' ];

        yield 'model collection loop' => [ 'class User extends \Illuminate\Database\Eloquent\Model {} foreach (User::all() as $user) { $user->fill($attributes); }' ];

        yield 'assigned model collection loop' => [ 'class User extends \Illuminate\Database\Eloquent\Model {} $users = User::query()->get(); foreach ($users as $user) { $user->fill($attributes); }' ];

        yield 'first collection model' => [ 'class User extends \Illuminate\Database\Eloquent\Model {} User::all()->first()->fill($attributes);' ];
    }

    #[DataProvider('provideAllowedCode')]
    public function test_accepts_explicit_assignment_and_unrelated_apis(string $source): void
    {
        $this->assertSame([], $this->processCode("<?php\n" . $source));
    }

    public static function provideAllowedCode(): iterable
    {
        yield 'explicit assignment' => [ 'class User extends \Illuminate\Database\Eloquent\Model {} $user = new User(); $user->name = "Ada"; $user->save();' ];

        yield 'strict configuration' => [ 'class User extends \Illuminate\Database\Eloquent\Model { protected $guarded = ["*"]; protected $fillable = []; protected static $unguarded = false; }' ];

        yield 'other model properties' => [ 'class User extends \Illuminate\Database\Eloquent\Model { protected $hidden = ["password"]; protected $casts = ["active" => "bool"]; }' ];

        yield 'strict attributes' => [ '#[\Illuminate\Database\Eloquent\Attributes\Guarded(["*"])] #[\Illuminate\Database\Eloquent\Attributes\Fillable([])] class User extends \Illuminate\Database\Eloquent\Model {}' ];

        yield 'non model properties' => [ 'class Settings { protected $guarded = []; protected $fillable = ["name"]; }' ];

        yield 'non model create and update' => [ 'class Client { function create(array $data): void {} function update(array $data): void {} } $client = new Client(); $client->create($data); $client->update($data);' ];

        yield 'schema create' => [ '\Illuminate\Support\Facades\Schema::create("users", $callback);' ];

        yield 'factory create' => [ 'class User extends \Illuminate\Database\Eloquent\Model {} User::factory()->create();' ];

        yield 'collection update' => [ 'class User extends \Illuminate\Database\Eloquent\Model {} User::all()->update($values);' ];

        yield 'raw query builder' => [ 'function store(\Illuminate\Database\Query\Builder $query): void { $query->update($values); }' ];

        yield 'reassigned receiver' => [ 'class User extends \Illuminate\Database\Eloquent\Model {} $user = new User(); $user = new Client(); $user->update($values);' ];

        yield 'separate function scopes' => [ 'class User extends \Illuminate\Database\Eloquent\Model {} function first(User $item): void {} function second(Client $item): void { $item->update($values); }' ];

        yield 'literal source' => [ '$source = \'$user->fill($data); protected $guarded = [];\';' ];

        yield 'comments' => [ '// User::create($data); protected $guarded = [];' ];

        yield 'empty file' => [ '' ];

        yield 'unresolved property' => [ 'function store(Client $client): void { $client->user->update($data); }' ];

        yield 'dynamic constructor' => [ '$instance = new $class($data);' ];

        yield 'unrelated union' => [ 'function store(Client|false $client): void { $client->update($data); }' ];

        yield 'cyclic inheritance' => [ 'class First extends Second {} class Second extends First {} First::create($data);' ];

        yield 'destructuring' => [ '[, $client] = $clients; $client->update($data);' ];

        yield 'unrelated loop' => [ 'foreach ($clients as $client) { $client->update($data); }' ];

        yield 'non promoted constructor parameter' => [ 'class Store { private Client $client; function __construct(Client $client) {} function store(): void { $this->client->update($data); } }' ];
    }

    public function test_does_not_apply_outside_laravel_projects(): void
    {
        $this->assertSame([], $this->processCode(
            source: '<?php class User extends \Illuminate\Database\Eloquent\Model { protected $guarded = []; } User::create($data);',
            laravelProject: false,
        ));
    }

    public function test_fix_mode_reports_violations_without_changing_the_source(): void
    {
        $source = '<?php class User extends \Illuminate\Database\Eloquent\Model { protected $guarded = []; } User::create($data);';

        $this->assertCount(2, $this->processCode($source, fix: true));
    }

    #[RunInSeparateProcess]
    public function test_resolves_autoloaded_models_and_reflected_types(): void
    {
        $autoload = static function (string $class): void {

            if ($class === 'Milewski\\ECS\\Tests\\Support\\AutoloadedRecord') {
                eval(<<<'PHP'
                namespace Illuminate\Database\Eloquent;
                class Model {}
                namespace Milewski\ECS\Tests\Support;
                class AutoloadedRecord extends \Illuminate\Database\Eloquent\Model {
                    public \Illuminate\Database\Eloquent\Model $model;
                    public $untyped;
                    public function model(): \Illuminate\Database\Eloquent\Model {}
                    public function fluent(): static {}
                    public function untyped() {}
                }
                PHP);
            }

        };

        spl_autoload_register($autoload);

        try {

            $errors = $this->processCode(<<<'PHP'
            <?php
            use Milewski\ECS\Tests\Support\AutoloadedRecord;
            AutoloadedRecord::create($data);
            function store(AutoloadedRecord $record): void {
                $record->model->fill($data);
                $record->model()->fill($data);
                $record->fluent()->fill($data);
                $record->untyped()->fill($data);
                $record->untyped->update($data);
                $record->missing->update($data);
            }
            PHP);

            $this->assertCount(5, $errors);

        } finally {
            spl_autoload_unregister($autoload);
        }
    }

    private function processCode(string $source, bool $laravelProject = true, bool $fix = false): array
    {
        $directory = sys_get_temp_dir() . '/ecs-mass-assignment-' . bin2hex(random_bytes(8));

        mkdir($directory);

        $filename = $directory . '/fixture.php';

        file_put_contents($filename, $source);

        if ($laravelProject) {

            mkdir($directory . '/bootstrap');
            file_put_contents($directory . '/artisan', '');
            file_put_contents($directory . '/bootstrap/app.php', '<?php');

        }

        try {

            $processor = $this->make(SniffFileProcessor::class);
            $result = $processor->processFile($filename, new Configuration(isFixer: $fix));

            $this->assertSame($source, file_get_contents($filename));

            return $result[ Bridge::CODING_STANDARD_ERRORS ] ?? [];

        } finally {

            unlink($filename);

            if ($laravelProject) {

                unlink($directory . '/artisan');
                unlink($directory . '/bootstrap/app.php');
                rmdir($directory . '/bootstrap');

            }

            rmdir($directory);

        }
    }
}
