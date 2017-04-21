<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Bond;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    const DEFAULT_CAPITAL = 5000;

    const DEFAULT_ORDER_DIRECTION = 'ASC';


    private function orderBonds($bonds, $order, $orderDirection = 'asc')
    {
        switch ($order) {
            case 'years': $method = 'fetchYearsLeft'; break;
            case 'coupon': $method = 'fetchCoupon'; break;
            case 'rate_effective': $method = 'fetchRateEffective'; break;
            case 'rate_per_year': $method = 'fetchRatePerYear'; break;
            case 'ratio': $method = 'fetchRatioTimeProfit'; break;
        }

        // check if order is whitelisted
        if (!isset($method)) {
            throw new \Exception('Order "'.$order.'" is not in the accepted order list');
        }

        // check if bond class has the method what will be called
        if (!method_exists(new Bond(), $method)) {
            throw new \Exception('Bond class has no "'.$method.'" method');
        }

        // order by key (asc or desc)
        usort($bonds, function($a, $b) use ($method, $orderDirection) {

            $value = $a->{$method}() <=> $b->{$method}();

            return strtoupper($orderDirection) === 'ASC'
                ? $value : $value * -1;
        });

        return $bonds;
    }

    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {
        $nameRequest = $request->query->get('name');
        $ratioRequest = $request->query->get('ratio');
        $profitRequest = $request->query->get('profit');
        $lastDateRequest = $request->query->get('lastDate');
        $yearsStartRequest = $request->query->get('years_start');
        $yearsEndRequest = $request->query->get('years_end');
        $orderRequest = $request->query->get('order');
        $orderDirectionRequest = $request->query->get('order_direction');
        $highlightRequest = $request->query->get('highlight');
        $capitalRequest = $request->query->get('capital');

        $bonds = $this
            ->getDoctrine()
            ->getRepository('AppBundle:Bond')
            ->createQueryBuilder('n');

        // highlight
        if ($highlightRequest) {
            $bonds
                ->andWhere('n.highlight = :highlight')
                ->setParameter('highlight', true);
        }

        // name
        if ($nameRequest) {
            $bonds
                ->andWhere('n.name LIKE :name')
                ->setParameter('name', '%' . $nameRequest . '%');
        }

        // name
        if ($lastDateRequest) {
            $bonds
                ->andWhere('n.date >= :lastDate')
                ->setParameter('lastDate', new \DateTime($lastDateRequest), \Doctrine\DBAL\Types\Type::DATETIME);
        }

        $bonds = $bonds
            ->orderBy('n.name', strtoupper($orderDirectionRequest ? $orderDirectionRequest : self::DEFAULT_ORDER_DIRECTION))
            ->getQuery()
            ->getResult();

        // ratio filter
        if ($ratioRequest) {
            $bonds = array_filter($bonds, function($bond) use ($ratioRequest) {
                return $bond->fetchRatioTimeProfit() >= $ratioRequest;
            });
        }

        // profit filter
        if ($profitRequest) {
            $bonds = array_filter($bonds, function($bond) use ($profitRequest, $capitalRequest) {
                return $bond->fetchProfit($capitalRequest) >= $profitRequest;
            });
        }

        // years start filter
        if ($yearsStartRequest) {
            $bonds = array_filter($bonds, function($bond) use ($yearsStartRequest) {
                return $bond->fetchYearsLeft() >= $yearsStartRequest;
            });
        }

        // years end filter
        if ($yearsEndRequest) {
            $bonds = array_filter($bonds, function($bond) use ($yearsEndRequest) {
                return $bond->fetchYearsLeft() <= $yearsEndRequest;
            });
        }

        if ($orderRequest && $orderRequest != 'name') {
            $bonds = $this->orderBonds($bonds, $orderRequest, $orderDirectionRequest);
        }

        return $this->render('default/index.html.twig', [
            'bonds'    => $bonds,
            'request' => [
                'name'          => $nameRequest,
                'ratio'          => $ratioRequest,
                'profit'         => $profitRequest,
                'lastDate'       => $lastDateRequest,
                'yearsStart'     => $yearsStartRequest,
                'yearsEnd'       => $yearsEndRequest,
                'order'          => $orderRequest,
                'orderDirection' => $orderDirectionRequest,
                'highlight'      => $highlightRequest,
                'capital'        => $capitalRequest ? $capitalRequest : self::DEFAULT_CAPITAL,
            ],
        ]);
    }
}
