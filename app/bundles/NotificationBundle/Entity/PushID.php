<?php

namespace Mautic\NotificationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\LeadBundle\Entity\Lead;

class PushID
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var \Mautic\LeadBundle\Entity\Lead
     */
    private $lead;

    /**
     * @var string
     */
    private $pushID;

    /**
     * @var bool
     */
    private $enabled;

    /**
     * @var bool
     */
    private $mobile;

    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('push_ids')
            ->setCustomRepositoryClass('Mautic\NotificationBundle\Entity\PushIDRepository');

        $builder->createField('id', 'integer')
            ->makePrimaryKey()
            ->generatedValue()
            ->build();

        $builder->createField('pushID', 'string')
            ->columnName('push_id')
            ->nullable(false)
            ->build();

        $builder->createManyToOne('lead', 'Mautic\LeadBundle\Entity\Lead')
            ->addJoinColumn('lead_id', 'id', true, false, 'SET NULL')
            ->inversedBy('pushIds')
            ->build();

        $builder->createField('enabled', 'boolean')->build();
        $builder->createField('mobile', 'boolean')->build();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return \Mautic\LeadBundle\Entity\Lead
     */
    public function getLead()
    {
        return $this->lead;
    }

    /**
     * @return $this
     */
    public function setLead(Lead $lead)
    {
        $this->lead = $lead;

        return $this;
    }

    /**
     * @return string
     */
    public function getPushID()
    {
        return $this->pushID;
    }

    /**
     * @param string $pushID
     *
     * @return $this
     */
    public function setPushID($pushID)
    {
        $this->pushID = $pushID;

        return $this;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * @param $enabled
     *
     * @return $this
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * @return bool
     */
    public function isMobile()
    {
        return $this->mobile;
    }

    /**
     * @param bool $mobile
     *
     * @return $this
     */
    public function setMobile($mobile)
    {
        $this->mobile = $mobile;

        return $this;
    }
}
