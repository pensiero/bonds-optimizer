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
    private const API_URLS = [
        'TLX' => 'https://finanza.repubblica.it/Obbligazioni/TLX/%s',
        'MOT' => 'https://finanza.repubblica.it/Obbligazioni/MOT/%s',
    ];

    /**
     * @var EntityManager
     */
    private $em;

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
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->initDatabase();

        $letters = range('a', 'z');
        foreach ($letters as $letter) {

            foreach (self::API_URLS as $market => $apiUrl) {

                $url = sprintf($apiUrl, strtoupper($letter));
                //$url = __DIR__ . '/../../../config/seeds/test.html';

                $parser = new Parser(file_get_contents($url));
                $root = $parser->parse();

                $rows = $root->find('.table-rounded tr');

                $count = 0;

                /** @var TagNode $row */
                foreach ($rows as $row) {

                    // skin first line
                    if ($count++ === 0) {
                        continue;
                    }

                    $parts = $row->find('td');

                    // name
                    $name = $this->cleanHtmlText($parts->nth(0)->find('a')->first()->getText());

                    // code
                    $code = $this->cleanHtmlText($parts->nth(0)->find('a')->first()->getAttribute('rel'));

                    // price
                    $price = $this->cleanHtmlText($parts->nth(1)->getText());
                    $price = str_replace(['.', ','], ['', '.'], $price);
                    $price = (float) $price;

                    // variation
                    $variation = $this->cleanHtmlText($parts->nth(2)->find('span')->first()->getText());
                    $variation = \in_array($variation, ['UNC.', 'INV.'], false)
                        ? null
                        : (float) str_replace(',', '.', trim($variation));

                    // date
                    $dateString = $this->cleanHtmlText($parts->nth(3)->getText());
                    $date = \DateTime::createFromFormat('d/m/Y', $dateString);

                    // date is of today
                    if (!$date) {
                        $date = \DateTime::createFromFormat('H.i', $dateString);
                    }
                    else {
                        $date->setTime(0, 0, 0);
                    }

                    // open
                    $open = str_replace(',', '.', $this->cleanHtmlText($parts->nth(4)->getText()));
                    $open = $open === '---' ? null : (float) $open;

                    // min
                    $min = str_replace(',', '.', $this->cleanHtmlText($parts->nth(5)->getText()));
                    $min = $min === '---' ? null : (float) $min;

                    // max
                    $max = str_replace(',', '.', $this->cleanHtmlText($parts->nth(6)->getText()));
                    $max = $max === '---' ? null : (float) $max;

                    // create the bond
                    $this->createBond($market, $name, $code, $price, $variation, $date, $open, $min, $max);
                }
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
     *
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function createBond($market, $name, $code, $price, $variation, $date, $open, $min, $max)
    {
        $bond = new Bond();

        $bond
            ->setMarket($market)
            ->setName($name)
            ->setCode($code)
            ->setPrice($price)
            ->setVariation($variation)
            ->setDate($date)
            ->setOpen($open)
            ->setMin($min)
            ->setMax($max);

        $this->em->persist($bond);
        $this->em->flush();
        $this->em->clear();
    }

    /**
     * @param $text
     *
     * @return string
     */
    private function cleanHtmlText($text)
    {
        return trim(str_replace(['\n', '\r'], '', $text));
    }
}