<?php

namespace App\Commands;

use Carbon\Carbon;
use Exception;
use Generator;
use Github\ResultPager;
use GrahamCampbell\GitHub\Facades\GitHub;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use LaravelZero\Framework\Commands\Command;
use SplFileInfo;

class DownloadPullRequests extends Command
{
    protected $signature   = 'dl-prs {author=ziadoz} {--skip-meta-json} {--skip-full-json} {--zip}';
    protected $description = 'Download GitHub pull requests as JSON';

    protected string $author;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->author = $this->argument('author');

        if (! env('GITHUB_TOKEN')) {
            $this->components->error('Missing environment variable: GITHUB_TOKEN');

            return self::FAILURE;
        }

        if (! $this->option('skip-meta-json')) {
            $this->downloadPullRequestMetaJson();
        }

        if (! $this->option('skip-full-json')) {
            $this->downloadPullRequestJson();
        }

        if ($this->option('zip')) {
            $this->zipPullRequests();
        }

        return self::SUCCESS;
    }

    protected function downloadPullRequestMetaJson(): void
    {
        $this->components->info('Downloading pull request meta JSON...');

        $getPullRequests = function (array $opts = []) {
            $this->components->bulletList([implode(' ', $opts)]);

            foreach ($this->getPullRequests(...$opts) as $pr) {
                if (! $pr) {
                    throw new Exception('No more pull requests to fetch');
                }

                $this->savePullRequestMetaJson($pr);
            }

            if (! isset($pr)) {
                throw new Exception('No more pull requests to fetch');
            }

            return collect($opts)
                ->filter(function ($value) {
                    return ! str_starts_with($value, 'created:');
                })
                ->when(isset($pr['created_at']), function ($opts) use ($pr) {
                    return $opts->merge('created:<' . Carbon::parse($pr['created_at'])->format('Y-m-d'));
                })
                ->toArray();
        };

        $this->repeatWithOpts($getPullRequests, ['archived:false']);
        $this->repeatWithOpts($getPullRequests, ['archived:true']);
    }

    // @see: https://github.com/KnpLabs/php-github-api/tree/master/doc
    // @see: https://github.com/GrahamCampbell/Laravel-GitHub/issues/79
    public function downloadPullRequestJson(): void
    {
        $this->components->info('Downloading pull request full JSON...');

        $files = collect((new Filesystem)->allFiles(storage_path('prs')))
            ->filter(fn (SplFileInfo $file): bool => ! str_contains($file->getFilename(), '-full.json'))
            ->toArray();

        $progress = $this->output->createProgressBar(count($files));
        $progress->start();

        foreach ($files as $file) {
            $this->savePullRequestJson(
                $this->getPullRequest(json_decode($file->getContents(), true))
            );

            $progress->advance();
        }

        $progress->finish();
    }

    protected function zipPullRequests(): void
    {
        $this->components->info('Zipping pull request JSON...');

        Process::run(sprintf(
            '(cd %s && zip -r ./prs-%s.zip ./prs/%s/ && cd %s)',
            storage_path(),
            Carbon::now()->format('Ymdhis'),
            $this->author,
            base_path(),
        ))->throw();
    }

    protected function getPullRequests(string ...$opts): Generator
    {
        $opts = collect(['is:pr', 'author:' . $this->author])
            ->merge($opts)
            ->filter()
            ->implode(' ');

        return (new ResultPager(GitHub::connection()))
            ->fetchAllLazy(
                GitHub::connection()->search(),
                'issues',
                [$opts, 'created', 'desc'],
            );
    }

    protected function repeatWithOpts(callable $do, array $opts = []): void
    {
        while (true) {
            try {
                $opts = $do($opts);
            } catch (Exception $e) {
                break;
            }
        }
    }

    protected function savePullRequestMetaJson(array $pr): void
    {
        $repo   = Str::of($pr['repository_url'])->replace(['https://api.github.com/repos/', '/'], ['', '-'])->toString();
        $number = Str::of($pr['number'])->toInteger();
        $path   = storage_path(sprintf('prs/%s/%s', $this->author, $repo));

        (new Filesystem)->makeDirectory($path, 0744, true, true);

        file_put_contents(
            sprintf('%s/%d-meta.json', $path, $number),
            json_encode($pr, JSON_PRETTY_PRINT),
        );
    }

    protected function getPullRequest(array $pr): array
    {
        [$author, $repo] = Str::of($pr['repository_url'])->replace('https://api.github.com/repos/', '')->explode('/');

        return GitHub::pullRequest()->show(
            $author,
            $repo,
            Str::of($pr['number'])->toInteger(),
        );
    }

    protected function savePullRequestJson(array $pr): void
    {
        $repo   = Str::of($pr['base']['repo']['full_name'])->replace(['https://api.github.com/repos/', '/'], ['', '-'])->toString();
        $number = Str::of($pr['number'])->toInteger();
        $path   = storage_path(sprintf('prs/%s/%s', $this->author, $repo));

        (new Filesystem)->makeDirectory($path, 0744, true, true);

        file_put_contents(
            sprintf('%s/%d-full.json', $path, $number),
            json_encode($pr, JSON_PRETTY_PRINT),
        );
    }
}
