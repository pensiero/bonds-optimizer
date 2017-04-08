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
            $url = __DIR__ . '/../../../config/seeds/test.html';

            $parser = new Parser(file_get_contents($url));
            $root = $parser->parse();

            $rows = $root->find('.tlb-commontb tbody tr');

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
                $price = (float) str_replace(',', '.', $parts->nth(1)->getText());

                // variation
                $variation = $parts->nth(2)->find('span')->first()->getText();
                $variation = $variation === 'UNC.'
                    ? null
                    : (float) str_replace(',', '.', $variation);

                // date
                $date = $parts->nth(3)->getText();
                $date = \DateTime::createFromFormat('d/m/Y', $date);
                $date->setTime(0, 0, 0);

                // open
                $open = (float) str_replace(',', '.', $parts->nth(4)->getText());

                // min
                $min = (float) str_replace(',', '.', $parts->nth(5)->getText());

                // max
                $max = (float) str_replace(',', '.', $parts->nth(6)->getText());

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