<?php

namespace App\Utils\Entity;

class Entity
{
    const COMMENTARY_ENTITY = 'Commentary';
    const USER_ENTITY = 'User';
    const AGENDA_ENTITY = 'Agenda';
    const DONATION_ENTITY = 'Donation';
    const DONATION_PAYMENT_ENTITY = 'DonationPayment';
    const DONATION_PHYSICAL_ENTITY = 'DonationPhysical';
    const MEDIA_ENTITY = 'Media';
    const MEETING_ENTITY = 'Meeting';
    const PROJECT_ENTITY = 'Project';
    const QUESTION_ENTITY = 'Question';
    const RESPONSE_ENTITY = 'Response';
    const SURVEY_ENTITY = 'Survey';

    /**
     *
     * @var string
     */
    public $name;
    /**
     *
     * @var string
     */
    public $table;
    /**
     *
     * @var int | string
     */
    public $id;
    /**
     *
     * @var \stdClass[]
     */
    public $columns;
    /**
     *
     * @var SoftDelete
     */
    public $softDelete;
    /**
     *
     * @var callable[][]
     */
    public $eventListener;

    public function __construct()
    {
        $this->softDelete = new SoftDelete();
        $this->eventListener = [];
    }

    public function __clone()
    {
        $this->softDelete = clone $this->softDelete;
    }
}
