<?php
namespace AppBundle\Command;

use AppBundle\Entity\Bond;
use Doctrine\ORM\EntityManager;
use HtmlParser\Elements\TagNode;
use HtmlParser\Parser;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CrawlCommand extends ContainerAwareCommand
{
    const API_URL = 'http://finanza.repubblica.it/Obbligazioni_TLX.aspx?letter=%s';

    /**
     * @var EntityManager
     */
    protected $em;

    protected function configure()
    {
        $this
            ->setName('app:crawl')
            ->setDescription('Crawl the bonds from the web')
            ->setHelp('This command crawl and update all the bonds from the web...')
        ;
    }

    private function initDatabase()
    {
        /** @var EntityManager $em */
        $this->em = $this->getContainer()->get('doctrine')->getManager();

        // clear table
        $this->em->createQuery('DELETE FROM AppBundle:Bond')->execute();

        $this->em->flush();
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->initDatabase();

        $letters = range('a', 'z');
        foreach ($letters as $letter) {

            $url = sprintf(self::API_URL, strtoupper($letter));
            //$url = __DIR__ . '/../../../config/seeds/test.html';

            $parser = new Parser(file_get_contents($url));
            $root = $parser->parse();

            $rows = $root->find('.page-body tbody tr');

            /** @var TagNode $row */
            foreach ($rows as $row) {

                $parts = $row->find('td');

                // name
                $name = $parts->nth(0)->find('a')->first()->getText();

                // code
                $code = $parts->nth(0)->find('a')->first()->getAttribute('href');
                preg_match('/(.*)addCode=(.*)/', $code, $matches);
                $code = isset($matches[2]) ? $matches[2] : null;

                // price
                $price = (float) str_replace(',', '.', trim($parts->nth(1)->getText()));

                // variation
                $variation = $parts->nth(2)->find('span')->first()->getText();
                $variation = $variation === 'UNC.'
                    ? null
                    : (float) str_replace(',', '.', trim($variation));

                // date
                $dateString = $parts->nth(3)->getText();
                $date = \DateTime::createFromFormat('d/m/Y', $dateString);

                // date is of today
                if (!$date) {
                    $date = \DateTime::createFromFormat('H.i', $dateString);
                }
                else {
                    $date->setTime(0, 0, 0);
                }

                // open
                $open = str_replace(',', '.', trim($parts->nth(4)->getText()));
                $open = $open === '---' ? null : (float) $open;

                // min
                $min = str_replace(',', '.', trim($parts->nth(5)->getText()));
                $min = $min === '---' ? null : (float) $min;

                // max
                $max = str_replace(',', '.', trim($parts->nth(6)->getText()));
                $max = $max === '---' ? null : (float) $max;

                // create the bond
                $this->createBond($name, $code, $price, $variation, $date, $open, $min, $max);
            }
        }
    }

    /**
     * @param string     $name
     * @param string     $code
     * @param float      $price
     * @param float|null $variation
     * @param \DateTime  $date
     * @param float      $open
     * @param float      $min
     * @param float      $max
     */
    private function createBond($name, $code, $price, $variation, $date, $open, $min, $max)
    {
        $bond = new Bond();

        $bond->setName($name);
        $bond->setCode($code);
        $bond->setPrice($price);
        $bond->setVariation($variation);
        $bond->setDate($date);
        $bond->setOpen($open);
        $bond->setMin($min);
        $bond->setMax($max);

        $this->em->persist($bond);
        $this->em->flush();
        $this->em->clear();
    }
}