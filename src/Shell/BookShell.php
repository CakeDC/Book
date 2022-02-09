<?php

/**
 * Copyright 2020 - 2022, Cake Development Corporation (https://www.cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2013 - 2018, Cake Development Corporation (https://www.cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace CakeDC\Book\Shell;

use Cake\Console\Shell;
use Cake\Core\Configure;
use Cake\Http\Client;

/**
 * Book shell command.
 */
class BookShell extends Shell
{
    /**
     * Manage the available sub-commands along with their arguments and help
     *
     * @see http://book.cakephp.org/3.0/en/console-and-shells.html#configuring-options-and-generating-help
     * @return \Cake\Console\ConsoleOptionParser
     */
    public function getOptionParser()
    {
        $parser = parent::getOptionParser();

        $parser->addArgument('parameter', ['help' => 'Search parameter', 'required' => true]);

        return $parser;
    }

    /**
     * main() method.
     *
     * @return bool|int|null Success or error code.
     */
    public function main()
    {
        $query = urlencode(implode(' ', $this->args));
        $version = $this->getVersion();
        $this->client = new Client();
        $results = json_decode((string)$this->client->get("https://search.cakephp.org/search?lang=en&q=$query&version={$version}")->getBody(), true);
        //$results = Cache::read('book.' . $query, 'book');

        if ($this->isFifoOutput()) {
            $this->echoAllResults($results['data'] ?? []);

            return 0;
        }

        $count = count($results['data']);
        if ($count == 0) {
            $this->out(__('  No results found!'));

            return 1;
        }

        $options = [];
        foreach ($results['data'] as $index => $result) {
            $options[$index] = $index + 1;
            $this->success("[{$options[$index]}]", false);
            $this->info(__(' {1}:', $options[$index], $result['title']), false);
            $this->out(str_replace("\n", '. ', $result['contents'][0] ?? 'N/A'));
            $this->out(__('   ' . $this->getUrl($result)));
        }

        do {
            $topic = $this->in(__('Please select the topic that you want to read [1-{0}]', $count));
        } while (!is_numeric($topic) || (is_numeric($topic) && empty($results['data'][$topic - 1]['url'])));

        $result = $results['data'][$topic - 1];
        $this->out($this->getContent($result));
        $this->out($this->getUrl($result));
    }

    protected function getContent($result): string
    {
        $version = $this->getVersion();
        $baseGithubUrl = "https://raw.githubusercontent.com/cakephp/docs{0}";

        $githubUrl = __($baseGithubUrl, substr(str_replace("/$version", "/$version.x", $result['url']), 0, -4) . 'rst');

        return (string)$this->client->get($githubUrl)->getBody();
    }

    protected function getVersion(): string
    {
        return substr(Configure::version(), 0, 1);
    }

    protected function echoAllResults($results): void
    {
        $limit = 10;
        $i = 0;
        try {
            for ($i = 0; $i < count($results) && $i < $limit; $i++) {
                $this->out(__('From page: ' . $this->getUrl($results[$i])));
                $this->out($this->getContent($results[$i]));
            }
        } catch (\Exception $ex) {
            // ignore broken pipes
        }
    }

    protected function getUrl(array $result): string
    {
        $version = $this->getVersion();
        $baseUrl = "https://book.cakephp.org{0}";
        return __($baseUrl, $result['url']);
    }

    protected function isFifoOutput(): bool
    {
        // code from https://stackoverflow.com/questions/11327367/detect-if-a-php-script-is-being-run-interactively-or-not
        $stat = fstat(STDOUT);
        $mode = $stat['mode'] & 0170000; // S_IFMT

        return $mode == 0010000;
    }
}
