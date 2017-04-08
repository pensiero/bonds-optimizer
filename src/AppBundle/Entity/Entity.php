<?php
namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks
 */
abstract class Entity {

    const TIMEZONE = 'Europe/Rome';

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @var int
     */
    protected $id;

    /**
     * @ORM\Column(type="integer")
     * @var int
     */
    protected $version = 1;

    /**
     * @ORM\Column(type="datetime")
     * @var \DateTime
     */
    protected $inserted;

    /**
     * @ORM\Column(type="datetime")
     * @var \DateTime
     */
    protected $modified;

    public function getId()
    {
        return $this->id;
    }

    /**
     * Disables setting of id
     *
     * @param int $id
     */
    public function setId($id)
    {
        //throw new \Exception('Trying to set non-writeable property \'id\'');
    }

    /**
     * @return int
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Disables setting of version
     *
     * @param int $version
     *
     * @throws \Exception
     */
    public function setVersion($version)
    {
        throw new \Exception('Trying to set non-writeable property \'version\'');
    }

    /**
     * @return \DateTime
     */
    public function getInserted()
    {
        return $this->inserted;
    }

    /**
     * @param \DateTime $inserted
     */
    public function setInserted(\DateTime $inserted)
    {
        $this->inserted = $inserted;
    }

    /**
     * @return \DateTime
     */
    public function getModified()
    {
        return $this->modified;
    }

    /**
     * @param \DateTime $modified
     */
    public function setModified($modified)
    {
        $this->modified = $modified;
    }

    /**
     * Empty default constructor to allow parent constructor invocation
     */
    public function __construct()
    {
        $date = new \DateTime();
        $this->setInserted($date);
        $this->setModified($date);
    }

    /**
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        if ($this->inserted === null) {
            $this->setInserted(new \DateTime());
        }
        if ($this->modified === null) {
            $this->setModified(new \DateTime());
        }
    }

    /**
     * @ORM\PreUpdate
     */
    public function preUpdate()
    {
        $this->setModified(new \DateTime());
        $this->version++;
    }
}
