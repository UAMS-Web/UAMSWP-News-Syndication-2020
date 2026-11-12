<?php

/**
 * Characterization tests for the plugin entry file, uamswp-news-syndication.php.
 *
 * Every expected value is taken from the file as it stands (the `tests` skill,
 * "Pinning legacy behavior"). The file holds the direct-access guard on WPINC,
 * three constants and the include of includes/news-syndicate.php.
 *
 * Two facts shape these tests:
 *
 * - `define()` is permanent, and tests/bootstrap.php has already loaded the
 *   file once. Loading it again in a test makes each `define()` raise "Constant
 *   ... already defined", which is what these tests count and name to pin the
 *   constant names and their order. The values the constants hold are the ones
 *   bootstrap's mocks returned, so they are asserted against those mocks.
 * - The guard calls `exit`, which ends the PHP process. The branch where WPINC
 *   is undefined can only run in its own process, so a child PHP process runs it.
 */

declare(strict_types=1);

use Brain\Monkey\Functions;

const PLUGIN_FILE = __DIR__.'/../../uamswp-news-syndication.php';

/**
 * Include the entry file with the three WordPress functions it calls mocked.
 *
 * @param  array<string, string>  $header  What get_file_data() returns for the header read.
 * @return list<string> The warnings the repeated define() calls raised, in order.
 */
function loadPluginFile(array $header = ['version' => 'mocked-version']): array
{
    $file = realpath(PLUGIN_FILE);

    Functions\expect('plugin_dir_url')->once()->with($file)->andReturn('mocked-url');
    Functions\expect('plugin_dir_path')->once()->with($file)->andReturn('mocked-path');
    Functions\expect('get_file_data')->once()->with($file, ['version' => 'Version'])->andReturn($header);

    $warnings = [];
    set_error_handler(static function (int $level, string $message) use (&$warnings): bool {
        $warnings[] = $message;

        return true;
    });

    try {
        include $file;
    } finally {
        restore_error_handler();
    }

    return $warnings;
}

it('defines the three constants in order, each once', function (): void {
    expect(loadPluginFile())->toBe([
        'Constant UAMS_NEWS_ROOT_URL already defined',
        'Constant UAMS_NEWS_PATH already defined',
        'Constant UAMS_NEWS_VERSION already defined',
    ]);
});

it('takes the root URL and the path from the file itself', function (): void {
    loadPluginFile();

    expect(UAMS_NEWS_ROOT_URL)->toBe('https://example.test/wp-content/plugins/'.basename(dirname(__DIR__, 2)).'/')
        ->and(UAMS_NEWS_PATH)->toBe(dirname(__DIR__, 2).'/');
});

it('takes the version from the get_file_data() read of its own Version header', function (): void {
    loadPluginFile();

    expect(UAMS_NEWS_VERSION)->toBe('test');
});

it('includes only includes/news-syndicate.php, once', function (): void {
    $before = get_included_files();

    loadPluginFile();

    expect(get_included_files())->toBe($before)
        ->and($before)->toContain(realpath(dirname(PLUGIN_FILE).'/includes/news-syndicate.php'))
        ->and($before)->not->toContain(realpath(dirname(PLUGIN_FILE)));
});

it('declares the Version header that get_file_data() reads', function (): void {
    expect(file_get_contents(PLUGIN_FILE))->toMatch('/^Version: \d+\.\d+\.\d+$/m');
});

/**
 * Run the entry file in a child PHP process and report what it printed.
 *
 * @param  string  $prelude  PHP run before the file is required.
 */
function runPluginFileInChild(string $prelude): string
{
    $script = $prelude.'
        function plugin_dir_url($file) { return "url/"; }
        function plugin_dir_path($file) { return "path/"; }
        function get_file_data($file, $headers) { return ["version" => "child-version"]; }
        function add_action($hook, $callback) { echo "add_action:$hook\n"; }
        require '.var_export(realpath(PLUGIN_FILE), true).';
        echo "constants:", UAMS_NEWS_ROOT_URL, UAMS_NEWS_PATH, UAMS_NEWS_VERSION, "\n";
        echo "included:", in_array('.var_export(realpath(dirname(PLUGIN_FILE).'/includes/news-syndicate.php'), true).', get_included_files(), true) ? "yes" : "no", "\n";
    ';

    $process = proc_open([PHP_BINARY, '-n', '-r', $script], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    $output = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    proc_close($process);

    return $output;
}

it('includes includes/news-syndicate.php and defines the constants when WPINC is defined', function (): void {
    expect(runPluginFileInChild('define("WPINC", "wp-includes");'))->toBe(
        "add_action:plugins_loaded\n"
        ."constants:url/path/child-version\n"
        ."included:yes\n"
    );
});

it('exits silently when WPINC is not defined, before any constant or include', function (): void {
    expect(runPluginFileInChild(''))->toBe('');
});
