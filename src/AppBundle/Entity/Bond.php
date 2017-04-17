<?php
namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="bonds")
 */
class Bond extends Entity
{
    const BOND_URL = 'http://finanza.repubblica.it/Obbligazioni_TLX_Scheda.aspx?addCode=%s';

    const YEARS_LEFT_PRECISION = 3;

    const RATE_EFFECTIVE_PRECISION = 2;

    const RATE_YEARLY_PRECISION = 3;

    const RATIO_TIME_PROFIT_PRECISION = 5;

    /**
     * @ORM\Column(type="string", length=200)
     * @var string
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=200)
     * @var string
     */
    private $code;

    /**
     * @ORM\Column(type="float")
     * @var float
     */
    private $price;

    /**
     * @ORM\Column(type="float", nullable=true)
     * @var float
     */
    private $variation;

    /**
     * @ORM\Column(type="datetime")
     * @var \DateTime
     */
    private $date;

    /**
     * @ORM\Column(type="float", nullable=true)
     * @var float
     */
    private $open;

    /**
     * @ORM\Column(type="float", nullable=true)
     * @var float
     */
    private $min;

    /**
     * @ORM\Column(type="float", nullable=true)
     * @var float
     */
    private $max;

    /**
     * @ORM\Column(type="boolean")
     * @var boolean
     */
    private $highlight = false;

    /**
     * @return string
     */
    public function __toString()
    {
        return (string) $this->name;
    }

    public function echoUrl()
    {
        return sprintf(self::BOND_URL, $this->code);
    }

    /**
     * @return string
     */
    public function echoDate()
    {
        if (!$this->date) {
            return '';
        }

        return $this->date->format('d-m-y H:i');
    }

    /**
     * Clean name from unwanted chars
     *
     * @return mixed
     */
    private function echoCleanName()
    {
        return str_replace('?', '', $this->name);
    }

    /**
     * Fetch deadline
     *
     * @return \DateTime|null
     */
    public function fetchDeadline()
    {
        $monthCodes = ['Ge', 'Fb', 'Mz', 'Ap', 'Mg', 'Gn', 'Lg', 'Ag', 'St', 'Ot', 'Nv', 'Dc'];
        $monthCodesForRegex = implode('|', $monthCodes);

        preg_match('/(\d{2})(['.$monthCodesForRegex.']{2})(\d{2})/i', $this->echoCleanName(), $matches);

        if (!isset($matches[0])) {
            return null;
        }

        $day = (int) $matches[1];

        $month = array_search($matches[2], $monthCodes) + 1;

        $year = (int) ("20" . $matches[3]);

        $date = new \DateTime();
        $date->setDate($year, $month, $day);
        $date->setTime(0, 0, 0);

        return $date;
    }

    /**
     * Echo deadline in a specific string format
     *
     * @return null|string
     */
    public function echoDeadline()
    {
        $deadline = $this->fetchDeadline();

        if (!$deadline) {
            return null;
        }

        return $deadline->format('d-m-Y');
    }

    /**
     * Days left until deadline
     *
     * @return int
     */
    public function fetchDaysLeft()
    {
        $now = new \DateTime();
        $interval = $now->diff($this->fetchDeadline());
        $days = (int) $interval->format('%a');

        return $days == 0 ? 1 : $days;
    }

    /**
     * Years left until deadline
     *
     * @return float
     */
    public function fetchYearsLeft()
    {
        return round($this->fetchDaysLeft() / 365, self::YEARS_LEFT_PRECISION);
    }

    /**
     * Calculate coupon
     *
     * @return float|int
     */
    public function fetchCoupon()
    {
        preg_match('/([0-9]+\.?[0-9]*)%/', $this->echoCleanName(), $matches);

        if (!isset($matches[0])) {
            return null;
        }

        return (float) $matches[1];
    }

    /**
     * Echo coupon
     *
     * @return string
     */
    public function echoCoupon()
    {
        return $this->fetchCoupon() . '%';
    }

    /**
     * Effective rate (based on years left)
     *
     * @return float|int
     */
    public function fetchRateEffective()
    {
        if ($this->price > 0) {
            return
                round(
                    ((100 / $this->price * 100) - 100)
                    +
                    ($this->fetchCoupon() * $this->fetchYearsLeft())
                    , self::RATE_EFFECTIVE_PRECISION);
        }

        return 0;
    }

    /**
     * Echo effective rate
     *
     * @return string
     */
    public function echoRateEffective()
    {
        return $this->fetchRateEffective() . '%';
    }

    /**
     * Yearly rate
     *
     * @return float
     */
    public function fetchRatePerYear()
    {
        return round($this->fetchRateEffective() / $this->fetchYearsLeft(), self::RATE_YEARLY_PRECISION);
    }

    /**
     * Echo yearly rate
     *
     * @return string
     */
    public function echoRatePerYear()
    {
        return $this->fetchRatePerYear() . '%';
    }

    /**
     * Profit based on capital
     *
     * @param int $capital
     *
     * @return float|int
     */
    public function fetchProfit($capital)
    {
        return $capital / 100 * $this->fetchRateEffective();
    }

    /**
     * Echo profit
     *
     * @param int $capital
     *
     * @return string
     */
    public function echoProfit($capital)
    {
        return $this->fetchRatePerYear() . ' €';
    }

    /**
     * Ratio based on time and profit
     *
     * @return float
     */
    public function fetchRatioTimeProfit()
    {
        return round($this->fetchRateEffective() / $this->fetchDaysLeft(), self::RATIO_TIME_PROFIT_PRECISION) * 100;
    }

    public function echoVariation()
    {
        if (empty($this->variation)) {
            return null;
        }

        return $this->variation . '%';
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Bond
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set price
     *
     * @param float $price
     *
     * @return Bond
     */
    public function setPrice($price)
    {
        $this->price = $price;

        return $this;
    }

    /**
     * Get price
     *
     * @return float
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * Set open
     *
     * @param float $open
     *
     * @return Bond
     */
    public function setOpen($open)
    {
        if ($open === '---') {
            $open = null;
        }

        $this->open = $open;

        return $this;
    }

    /**
     * Get open
     *
     * @return float
     */
    public function getOpen()
    {
        return $this->open;
    }

    /**
     * Set min
     *
     * @param float $min
     *
     * @return Bond
     */
    public function setMin($min)
    {
        $this->min = $min;

        return $this;
    }

    /**
     * Get min
     *
     * @return float
     */
    public function getMin()
    {
        return $this->min;
    }

    /**
     * Set max
     *
     * @param float $max
     *
     * @return Bond
     */
    public function setMax($max)
    {
        $this->max = $max;

        return $this;
    }

    /**
     * Get max
     *
     * @return float
     */
    public function getMax()
    {
        return $this->max;
    }

    /**
     * Set variation
     *
     * @param float $variation
     *
     * @return Bond
     */
    public function setVariation($variation)
    {
        $this->variation = $variation;

        return $this;
    }

    /**
     * Get variation
     *
     * @return float
     */
    public function getVariation()
    {
        return $this->variation;
    }

    /**
     * Set date
     *
     * @param \DateTime $date
     *
     * @return Bond
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date
     *
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set code
     *
     * @param string $code
     *
     * @return Bond
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @return bool
     */
    public function isHighlight()
    {
        return $this->highlight;
    }

    /**
     * @param bool $highlight
     */
    public function setHighlight($highlight)
    {
        $this->highlight = $highlight;
    }
}
