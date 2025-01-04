<?php

namespace App\Entity;

use App\Controller\ApiResultsQueryController;
use App\Controller\ApiResultsQueryInterface;
use DateTime;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use JetBrains\PhpStorm\ArrayShape;
use JMS\Serializer\Annotation as Serializer;
use JsonSerializable;
use Hateoas\Configuration\Annotation as Hateoas;

#[Hateoas\Relation(
    name: "parent",
    href: "expr(constant('App\\\\Controller\\\\ApiResultsQueryInterface::RUTA_API'))"
)]
#[Hateoas\Relation(
    name: "self",
    href: "expr(constant('App\\\\Controller\\\\ApiResultsQueryInterface::RUTA_API') ~ '/' ~ object.getId())"
)]
#[ORM\Entity, ORM\Table(name: 'results')]
#[Serializer\XmlNamespace(uri: "http://www.w3.org/2005/Atom", prefix: "atom")]
#[Serializer\AccessorOrder(order: 'custom', custom: ["id", "result", "time", "user", "_links"])]
class Result implements JsonSerializable
{
    public final const RESULT_ATTR = "result";
    public final const USER_ATTR = "user";
    public final const TIME_ATTR = "time";

    #[ORM\Column(
        name: 'id',
        type: 'integer',
        nullable: false
    )]
    #[ORM\Id, ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[Serializer\XmlAttribute]
    protected ?int $id = null;


    #[ORM\Column(
        name:"result",
        type: "integer",
        nullable: false
    )]
    #[Serializer\SerializedName(Result::RESULT_ATTR), Serializer\XmlElement(cdata: false)]
    private int $result;

    #[ORM\Column(
        name:"time",
        type: Types::DATETIME_MUTABLE,
        nullable: false
    )]

    #[Serializer\Type("DateTime<'Y-m-d H:i:s'>")]
    private ?DateTimeInterface $time;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(
        name: "user_id",
        referencedColumnName: "id",
        onDelete: "CASCADE"
    )]
    private ?User $user;

    /**
     * @param int            $result
     * @param DateTimeInterface|null $time
     * @param User|null      $user
     */
    public function __construct(int                $result = 0,
                                ?DateTimeInterface $time = null,
                                User               $user=null)
    {
        $this->id = 0;
        $this->result = $result;
        $this->time = $time;
        $this->user = $user;
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param int|null $id
     */
    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getResult(): int
    {
        return $this->result;
    }

    /**
     * @param int $result
     */
    public function setResult(int $result): void
    {
        $this->result = $result;
    }

    /**
     * @return DateTimeInterface|null
     */
    public function getTime(): ?DateTimeInterface
    {
        return $this->time;
    }

    /**
     * @param DateTimeInterface|null $time
     */
    public function setTime(?DateTimeInterface $time): void
    {
        $this->time = $time;
    }

    /**
     * @return User|null
     */
    public function getUser(): User|null
    {
        return $this->user;
    }

    /**
     * @param User|null $user
     */
    public function setUser(?User $user): void
    {
        $this->user = $user;
    }


    /**
     * Implements __toString()
     *
     * @return string
     */
    public function __toString(): string
    {
        return sprintf(
            '%3d - %3d - %22s - %s',
            $this->id,
            $this->result,
            $this->user->getId(),
            $this->time->format('Y-m-d H:i:s')
        );
    }

    /**
     * @inheritDoc
     *
     * @return array<string, User|string|int|null>.
     */
    #[ArrayShape([
        'Id' => "int|null",
        self::USER_ATTR => "User",
        self::RESULT_ATTR => "int",
        self::TIME_ATTR => "string"
    ])]
    public function jsonSerialize(): array
    {
        return [
            'Id'=>$this-> getId(),
            self::USER_ATTR=>$this->getUser(),
            self::RESULT_ATTR=>$this->getResult(),
            self::TIME_ATTR=>$this->time->format('Y-m-d H:i:s')
        ];
    }
}