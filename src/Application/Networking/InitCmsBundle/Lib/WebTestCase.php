<?php
/**
 * This file is part of the init_cms_sandbox package.
 *
 * (c) net working AG <info@networking.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Application\Networking\InitCmsBundle\Lib;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase as BaseWebTestCase;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\StreamOutput;

/**
 * Class WebTestCase
 * @package Sandbox\InitCmsBundle\Lib
 * @author Yorkie Chadwick <y.chadwick@networking.ch>
 */
abstract class WebTestCase extends BaseWebTestCase
{

    public function setUp()
    {
        $client = static::createClient();

        $this->deleteDatabase();
        $this->runCommand($client, 'doctrine:schema:create');
        $this->runCommand(
            $client,
            'doctrine:fixtures:load --append --fixtures="' . dirname(__FILE__) . '/../Tests/Fixtures"'
        );
        $this->backupDatabase();
    }


    /**
     * Finds the directory where the phpunit.xml(.dist) is stored.
     *
     * If you run tests with the PHPUnit CLI tool, everything will work as expected.
     * If not, override this method in your test classes.
     *
     * @return string
     * @throws \RuntimeException
     */
    protected static function getPhpUnitXmlDir()
    {

        if (!isset($_SERVER['argv']) || false === strpos($_SERVER['argv'][0], 'phpunit')) {
            throw new \RuntimeException('You must override the WebTestCase::createKernel() method.');
        }

        $dir = static::getPhpUnitCliConfigArgument();
        if ($dir === null &&
            (is_file(getcwd() . DIRECTORY_SEPARATOR . 'phpunit.xml') ||
                is_file(getcwd() . DIRECTORY_SEPARATOR . 'phpunit.xml.dist'))
        ) {
            $dir = getcwd();
        }

        // Can't continue
        if ($dir === null) {
            throw new \RuntimeException('Unable to guess the Kernel directory.');
        }

        if (!is_dir($dir)) {
            $dir = dirname($dir);
        }

        return $dir;
    }

    /**
     * Finds the value of the CLI configuration option.
     *
     * PHPUnit will use the last configuration argument on the command line, so this only returns
     * the last configuration argument.
     *
     * @return string The value of the PHPUnit cli configuration option
     */
    private static function getPhpUnitCliConfigArgument()
    {

        return dirname(__FILE__) . '/../../../../../app';
    }

    /**
     * Runs a command and returns it output
     */
    public function runCommand(Client $client, $command)
    {

        $application = new Application($client->getKernel());
        $application->setAutoExit(false);

        $fp = tmpfile();
        $input = new StringInput($command);
        $output = new StreamOutput($fp);

        $application->run($input, $output);

        fseek($fp, 0);
        $output = '';
        while (!feof($fp)) {
            $output = fread($fp, 4096);
        }
        fclose($fp);

        return $output;
    }

    public function deleteDatabase()
    {
        $folder = $this->getPhpUnitCliConfigArgument() . '/cache/test/';
        foreach (array('test.db', 'test.db.bk') AS $file) {
            if (file_exists($folder . $file)) {
                unlink($folder . $file);
            }
        }
    }

    public function backupDatabase()
    {
        copy(
            $this->getPhpUnitCliConfigArgument() . '/cache/test/test.db',
            $this->getPhpUnitCliConfigArgument() . '/cache/test/test.db.bk'
        );
    }

    public function restoreDatabase()
    {
        copy(
            $this->getPhpUnitCliConfigArgument() . '/cache/test/test.db.bk',
            $this->getPhpUnitCliConfigArgument() . '/cache/test/test.db'
        );
    }
}
