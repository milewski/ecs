<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Rules;

use Milewski\ECS\Fixers\MultilineNamedArgumentsFixer;
use Milewski\ECS\Tests\Support\FixerTestCase;
use PhpCsFixer\Tokenizer\Tokens;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use SplFileInfo;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class SessionDriverArgumentsTest extends FixerTestCase
{
    #[DataProvider('provideDriverCalls')]
    public function test_session_driver_put_arguments_are_named(string $driverCall): void
    {
        $this->declareManager();

        $input = $this->source($driverCall);
        $expected = str_replace(
            search: [
                "        'crm.pending',",
                "        [ 'user_id' => 7 ],",
                "        sprintf('crm.pending.%s', \$key),",
                '        $value,',
            ],
            replace: [
                "        key: 'crm.pending',",
                "        value: [ 'user_id' => 7 ],",
                "        key: sprintf('crm.pending.%s', \$key),",
                '        value: $value,',
            ],
            subject: $input,
        );

        $expected = str_replace("driver(driver: 'array')", "driver('array')", $expected);

        $this->assertSame($expected, $this->fix($input));
        $this->assertSame($expected, $this->fix($expected));
    }

    public static function provideDriverCalls(): array
    {
        return [
            'default driver assigned in Pest closure' => [ '$sessionManager->driver()' ],
            'explicit array driver' => [ '$sessionManager->driver(\'array\')' ],
            'named driver selection' => [ '$sessionManager->driver(driver: \'array\')' ],
            'nullsafe driver selection' => [ '$sessionManager?->driver()' ],
            'case insensitive method' => [ '$sessionManager->DRIVER()' ],
            'chained container resolution' => [ 'app(SessionManager::class)->driver()' ],
        ];
    }

    public function test_mixin_does_not_determine_other_mixed_method_results(): void
    {
        $this->declareManager();

        $input = $this->source('$sessionManager->ambiguous()');

        $this->assertSame($input, $this->fix($input));
    }

    public function test_unrelated_manager_driver_results_are_not_guessed(): void
    {
        $this->declareManager();

        $input = str_replace(
            search: 'use Illuminate\\Session\\SessionManager;',
            replace: 'use Illuminate\\Session\\OtherManager as SessionManager;',
            subject: $this->source('$sessionManager->driver()'),
        );

        $this->assertSame($input, $this->fix($input));
    }

    public function test_overridden_mixed_driver_is_not_assumed_to_use_build_session(): void
    {
        $this->declareManager('public function driver($driver = null): mixed { return null; }');

        $input = $this->source('$sessionManager->driver()');

        $this->assertSame($input, $this->fix($input));
    }

    public function test_unknown_build_session_return_type_is_not_guessed(): void
    {
        $this->declareManager(returnType: 'mixed');

        $input = $this->source('$sessionManager->driver()');

        $this->assertSame($input, $this->fix($input));
    }

    public function test_declared_driver_return_type_takes_precedence(): void
    {
        $this->declareManager('public function driver($driver = null): AlternateStore { return new AlternateStore(); }');

        $input = $this->source('$sessionManager->driver()');
        $expected = str_replace(
            search: [
                "        'crm.pending',",
                "        [ 'user_id' => 7 ],",
                "        sprintf('crm.pending.%s', \$key),",
                '        $value,',
            ],
            replace: [
                "        name: 'crm.pending',",
                "        payload: [ 'user_id' => 7 ],",
                "        name: sprintf('crm.pending.%s', \$key),",
                '        payload: $value,',
            ],
            subject: $input,
        );

        $this->assertSame($expected, $this->fix($input));
    }

    private function declareManager(string $driverMethod = '', string $returnType = '\\Illuminate\\Session\\Store'): void
    {
        // Isolated declarations reproduce Laravel's reflection contract without a framework dependency.
        $declarations = <<<'PHP'
        namespace Illuminate\Support;

        class Manager
        {
            /** @return mixed */
            public function driver($driver = null) { return null; }
        }

        namespace Illuminate\Session;

        class Store
        {
            public function put($key, $value = null): void {}
        }

        class AlternateStore
        {
            public function put($name, $payload = null): void {}
        }

        /** @mixin \Illuminate\Session\Store */
        class SessionManager extends \Illuminate\Support\Manager
        {
            __DRIVER_METHOD__

            /** @return __RETURN_TYPE__ */
            protected function buildSession($handler) { return new Store(); }

            /** @return mixed */
            public function ambiguous() { return null; }
        }

        /** @mixin \Illuminate\Session\Store */
        class OtherManager extends \Illuminate\Support\Manager
        {
            protected function buildSession($handler): Store { return new Store(); }
        }
        PHP;

        eval(str_replace([ '__DRIVER_METHOD__', '__RETURN_TYPE__' ], [ $driverMethod, $returnType ], $declarations));
    }

    private function source(string $driverCall): string
    {
        $source = <<<'PHP'
        <?php
        use Illuminate\Session\SessionManager;
        use function Milewski\ECS\Tests\Support\complex_return_app as app;

        test('malformed stored challenge', function (string $key, mixed $value): void {
            $sessionManager = app(SessionManager::class);
            $store = __DRIVER_CALL__;
            $store->start();
            $store->put(
                'crm.pending',
                [ 'user_id' => 7 ],
            );

            $store->put(
                sprintf('crm.pending.%s', $key),
                $value,
            );
        })->with([ [ 'user_id', '7' ] ]);
        PHP;

        return str_replace('__DRIVER_CALL__', $driverCall, $source);
    }

    private function fix(string $input): string
    {
        $tokens = Tokens::fromCode($input);
        $fixer = new MultilineNamedArgumentsFixer();
        $fixer->configure([ 'max_line_length' => 1000 ]);
        $fixer->fix(new SplFileInfo('fixture.php'), $tokens);

        return $tokens->generateCode();
    }
}
