<?php
namespace CakeDC\Book\Shell;

use Cake\Cache\Cache;
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
     *
     * @return \Cake\Console\ConsoleOptionParser
     */
    public function getOptionParser()
    {
        $parser = parent::getOptionParser();

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
        $version = substr(Configure::version(), 0, 1);
        $Client = new Client();
        $results = json_decode($Client->get("https://search.cakephp.org/search?lang=en&q=$query&version={$version}0")->getStringBody(), true);
        //$results = Cache::read('book.' . $query, 'book');
        $options = [];
        $baseUrl = "https://book.cakephp.org/$version/{0}";
        foreach ($results['data'] as $index => $result) {
            $options[$index] = $index + 1;
            $this->success("[{$options[$index]}]", false);
            $this->info(__(' {1}:', $options[$index], $result['title']), false);
            $this->out(str_replace("\n", ". ", $result['contents'][0]));
            $this->out(__('   ' . $baseUrl, $result['url']));

        }
        $count = count($results['data']);
        do {
            $topic = $this->in(__('Please select the topic that you want to read [1-{0}]', $count));
        } while (!is_numeric($topic) || (is_numeric($topic) && empty($results['data'][$topic - 1]['url'])));



        $url = __($baseUrl, $results['data'][$topic - 1]['url']);

        $html = $Client->get($url)->getStringBody();
        $this->params['force'] = true;
        $html = substr($html,strpos($html, '<div class="document-body">'));
        $html = substr($html, 0, strpos($html, '<nav>
                    <ul class="pagination">'));

        $html2TextConverter = new \Html2Text\Html2Text($html);
        if (file_exists(TMP . 'query.txt')) {
            unlink(TMP . 'query.txt');
        }
        file_put_contents(TMP . 'query.txt', $html2TextConverter->getText());
        $dspec = array(
            array('pipe', 'r'), // pipe to child process's stdin
            array('pipe', 'w'), // pipe from child process's stdout
            array('file', 'error_log', 'a'), // stderr dumped to file
        );
        // run the external command
        $proc = proc_open('less ' . TMP . 'query.txt', $dspec, $pipes, null, null);
        $init = true;
        if (is_resource($proc)) {
            while ($init || ($cmd = readline('')) != 'q') {
                if ($init) {
                    $init = false;
                } else {
                    fwrite($pipes[0], $cmd);
                }
                // if the external command expects input, it will get it from us here

                fflush($pipes[0]);
                // we can get the response from the external command here
                $next = fread($pipes[1], 1024);
                if (!empty($next)) {
                    $this->out($next);
                } else {
                    $this->info("(END) Press q to exit");
                }

            }
            fclose($pipes[0]);
            fclose($pipes[1]);
            proc_close($proc);
        }

        if (file_exists(TMP . 'query.txt')) {
            //unlink(TMP . 'query.txt');
        }

    }
}
