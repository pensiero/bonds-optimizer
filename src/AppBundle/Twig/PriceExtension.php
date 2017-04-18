<?php
namespace AppBundle\Twig;

class PriceExtension extends \Twig_Extension
{
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('price', array($this, 'price')),
        );
    }

    public function price($number, $decimals = 2, $decPoint = ',', $thousandsSep = '.')
    {
        return number_format($number, $decimals, $decPoint, $thousandsSep) . ' €';
    }
}