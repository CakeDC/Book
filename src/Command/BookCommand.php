<?php
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
    /**
     * Hook method for defining this command's option parser.
     *
     * @see https://book.cakephp.org/3.0/en/console-and-shells/commands.html#defining-arguments-and-options
     *
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to be defined
     * @return \Cake\Console\ConsoleOptionParser The built parser.
     */
    public function buildOptionParser(ConsoleOptionParser $parser)
    {
        $parser = parent::buildOptionParser($parser);

        $parser->addArgument('parameter', ['help' => 'Search parameter', 'required' => true]);

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
        $version = $this->getVersion();
        $this->client = new Client();
        $results = json_decode((string)$this->client->get("https://search.cakephp.org/search?lang=en&q=$query&version={$version}")->getBody(), true);
        //$results = Cache::read('book.' . $query, 'book');

        if ($this->isFifoOutput()) {
            $this->echoAllResults($results['data'] ?? [], $io);

            return 0;
        }

        $count = count($results['data']);
        if ($count == 0) {
            $io->err(__('  No results found!'));

            return 1;
        }

        $options = [];
        foreach ($results['data'] as $index => $result) {
            $options[$index] = $index + 1;
            $io->success("[{$options[$index]}]", false);
            $io->info(__(' {1}:', $options[$index], $result['title']), false);
            $io->out(str_replace("\n", '. ', $result['contents'][0] ?? 'N/A'));
            $io->out(__('   ' . $this->getUrl($result)));
        }

        do {
            $topic = $io->ask(__('Please select the topic that you want to read [1-{0}]', $count));
        } while (!is_numeric($topic) || (is_numeric($topic) && empty($results['data'][$topic - 1]['url'])));

        $result = $results['data'][$topic - 1];
        $io->out($this->getContent($result));
        $io->out($this->getUrl($result));
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

    protected function echoAllResults($results, ConsoleIo $io): void
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
