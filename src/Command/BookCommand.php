<?php
declare(strict_types=1);

/**
 * Copyright 2020 - 2022, Cake Development Corporation (https://www.cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2020 - 2022, Cake Development Corporation (https://www.cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace CakeDC\Book\Command;

use Cake\Console\Arguments;
use Cake\Console\Command;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Configure;
use Cake\Http\Client;

/**
 * Book command.
 */
class BookCommand extends Command
{
    protected const PAGE_LIMT = 25;
    protected const OPTION_QUIT = ['q', 'Q'];
    protected const OPTION_NEXT_PAGE = ['n', 'N'];
    protected const OPTION_PREVIOUS_PAGE = ['p', 'P'];

    protected const LANG_EN = 'en';

    protected const URL_API_SEARCH = 'https://search.cakephp.org/search?%s';
    protected const URL_BASE_TOPIC = 'https://book.cakephp.org%s';
    protected const URL_BASE_GITHUB = 'https://raw.githubusercontent.com/cakephp/docs%s';

    /**
     * Hook method for defining this command's option parser.
     *
     * @see https://book.cakephp.org/4/en/console-commands/option-parsers.html
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to be defined
     * @return \Cake\Console\ConsoleOptionParser The built parser.
     */
    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser->addArgument('parameter', [
            'help' => __('Parameter to search'),
            'required' => true,
        ]);

        $parser->addOption('cakephp-version', [
            'help' => __('CakePHP version to search'),
            'short' => 'c',
            'choices' => ['3', '4', '5'],
            'default' => $this->getVersion(),
        ]);

        return $parser;
    }

    /**
     * Implement this method with your command's logic.
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return null|int The exit code or null for success
     */
    public function execute(Arguments $args, ConsoleIo $io)
    {
        $query = $args->getArgument('parameter');
        $cakeVersion = $args->getOption('cakephp-version') ?? $this->getVersion();
        $page = 1;
        $results = [];
        $topic = 0;

        do {
            $results = $this->getIndex($query, $cakeVersion, $page);

            $count = count($results['data']);
            if ($count == 0) {
                $io->err(__('  No results found!'));

                return 1;
            }

            if ($this->isFifoOutput()) {
                $this->echoAllResults($results['data'] ?? [], $io);

                return 0;
            }

            $this->showIndex($results, $io);

            if ($this->hasPreviousPage($results)) {
                $io->success(__('[{0}]', self::OPTION_PREVIOUS_PAGE[0]), 0);
                $io->info(__(' Go to page {0}', $page - 1));
            }

            if ($this->hasNextPage($results, self::PAGE_LIMT)) {
                $io->success(__('[{0}]', self::OPTION_NEXT_PAGE[0]), 0);
                $io->info(__(' Go to page {0}', $page + 1));
            }

            $io->success(__('[{0}]', self::OPTION_QUIT[0]), 0);
            $io->info(__(' Quit'));

            do {
                $topic = $io->ask(__('Please select the topic that you want to read [1-{0}]', $count));

                if (in_array($topic, self::OPTION_PREVIOUS_PAGE) && $this->hasPreviousPage($results)) {
                    $page--;
                    break;
                }

                if (in_array($topic, self::OPTION_NEXT_PAGE) && $this->hasNextPage($results, self::PAGE_LIMT)) {
                    $page++;
                    break;
                }

                if (in_array($topic, self::OPTION_QUIT)) {
                    return 0;
                }

                if (is_numeric($topic) && !empty($results['data'][$topic - 1]['url'])) {
                    break 2;
                }
            } while (true);
        } while (true);

        $result = $results['data'][$topic - 1];
        $io->out($this->getContent($result));
        $io->out($this->getUrl($result));

        return 0;
    }

    /**
     * @param string $query
     * @param string $cakeVersion
     * @param int $page
     * @return array|null
     */
    protected function getIndex(string $query, string $cakeVersion, int $page = 1): ?array
    {
        $this->client = new Client();
        $url = sprintf(
            self::URL_API_SEARCH,
            http_build_query([
                'q' => $query,
                'version' => $cakeVersion,
                'page' => $page,
                'lang' => self::LANG_EN,
            ])
        );
        $results = json_decode((string)$this->client->get($url)->getBody(), true);

        return $results;
    }

    /**
     * @param array $results
     * @param \Cake\Console\ConsoleIo $io
     * @return void
     */
    protected function showIndex(array $results, ConsoleIo $io): void
    {
        $io->info(__('Page {0}', $results['page']));
        $options = [];
        foreach ($results['data'] as $index => $result) {
            $options[$index] = $index + 1;
            $io->success("[{$options[$index]}]", 0);
            $io->info(__(' {1}: ', $options[$index], $result['hierarchy'][count($result['hierarchy']) - 1]), 0);
            $io->out(str_replace("\n", '. ', $result['contents'] ?? 'N/A'));
            $io->out(__('   ' . $this->getUrl($result)));
        }
    }

    /**
     * @param array $results
     * @param int $limitPage
     * @return bool
     */
    protected function hasNextPage(array $results, int $limitPage): bool
    {
        $currentPage = $results['page'];
        $totalRecords = $results['total'];
        $totalPages = ceil($totalRecords / $limitPage);

        return $currentPage < $totalPages;
    }

    protected function hasPreviousPage(array $results): bool
    {
        $currentPage = $results['page'];

        return $currentPage > 1;
    }

    /**
     * @param array $result
     * @return string
     */
    protected function getContent(array $result): string
    {
        $version = $this->getVersion();

        $githubUrl = sprintf(
            self::URL_BASE_GITHUB,
            str_replace(["/$version", '.html'], ["/$version.x", '.rst'], $result['url'])
        );
        $body = (string)$this->client->get($githubUrl)->getBody();

        return $body;
    }

    /**
     * @return string
     */
    protected function getVersion(): string
    {
        return substr(Configure::version(), 0, 1);
    }

    /**
     * @param array $results
     * @param \Cake\Console\ConsoleIo $io
     * @return void
     */
    protected function echoAllResults(array $results, ConsoleIo $io): void
    {
        $limit = 10;
        $i = 0;
        try {
            for ($i = 0; $i < count($results) && $i < $limit; $i++) {
                $io->out(__('From page: ' . $this->getUrl($results[$i])));
                $io->out($this->getContent($results[$i]));
            }
        } catch (\Exception $ex) {
            // ignore broken pipes
        }
    }

    /**
     * @param array $result
     * @return string
     */
    protected function getUrl(array $result): string
    {
        if (empty($result['url'])) {
            return '';
        }

        return sprintf(self::URL_BASE_TOPIC, $result['url']);
    }

    /**
     * @return bool
     */
    protected function isFifoOutput(): bool
    {
        // code from https://stackoverflow.com/questions/11327367/detect-if-a-php-script-is-being-run-interactively-or-not
        $stat = fstat(STDOUT);
        $mode = $stat['mode'] & 0170000; // S_IFMT

        return $mode == 0010000;
    }
}
