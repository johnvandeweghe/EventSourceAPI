<?php

namespace EventStreamApi\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use EventStreamApi\Repository\SubscriptionRepository;

/**
 * @ApiResource(
 *     collectionOperations={"get", "post"},
 *     itemOperations={"get", "delete"},
 *     normalizationContext={"groups"={"subscription:read"}},
 *     denormalizationContext={"groups"={"subscription:write"}},
 *     attributes={"validation_groups"={Subscription::class, "validationGroups"}}
 * )
 * @ORM\Entity(repositoryClass=SubscriptionRepository::class)
 * @ORM\Table(uniqueConstraints={@ORM\UniqueConstraint(name="uq_transport_stream_user", columns={"transport", "stream_user_id"})})
 * @UniqueEntity(fields={"transport", "streamUser"})
 */
class Subscription
{
    public const TRANSPORT_GENERIC   = 'generic';
    public const TRANSPORT_WEBHOOK  = 'webhook';

    public const TRANSPORTS = [
        self::TRANSPORT_GENERIC,
        self::TRANSPORT_WEBHOOK
    ];

    /**
     * @ORM\Id()
     * @ORM\Column(type="uuid", unique=true)
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class="Ramsey\Uuid\Doctrine\UuidGenerator")
     * @Groups({"subscription:read"})
     * @Assert\Uuid
     */
    protected UuidInterface $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"subscription:read", "subscription:write"})
     * @Assert\Choice(choices=Subscription::TRANSPORTS)
     * @Assert\NotBlank(groups={"Default", "webhook_transport"})
     * @ApiFilter(SearchFilter::class, strategy="exact")
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="string",
     *             "enum"=Subscription::TRANSPORTS,
     *             "example"=Subscription::TRANSPORT_GENERIC
     *         }
     *     }
     * )
     */
    public string $transport;

    /**
     * @ORM\Column(type="simple_array", nullable=true)
     * @Groups({"subscription:read", "subscription:write"})
     * @var string[]|null
     */
    public ?array $eventTypes;

    /**
     * @ORM\ManyToOne(targetEntity=StreamUser::class, inversedBy="subscriptions")
     * @ORM\JoinColumn(nullable=false)
     * @Groups({"subscription:read", "subscription:write"})
     */
    protected StreamUser $streamUser;

    /**
     * @ORM\OneToOne(targetEntity=WebhookSubscriptionData::class, cascade={"persist", "remove"})
     * @Groups({"subscription:read", "subscription:write"})
     * @Assert\NotBlank(groups={"webhook_transport"})
     * @Assert\IsNull()
     */
    protected ?WebhookSubscriptionData $webhookData = null;

    /**
     * @param Subscription $subscription
     * @return string[]
     */
    public static function validationGroups(self $subscription): array
    {
        if($subscription->transport === self::TRANSPORT_WEBHOOK) {
            return ["webhook_transport"];
        }
        return ["Default"];
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getStreamUser(): StreamUser
    {
        return $this->streamUser;
    }

    public function setStreamUser(StreamUser $streamUser): void
    {
        $this->streamUser = $streamUser;
    }

    public function getTransport(): string
    {
        return $this->transport;
    }

    public function setTransport(string $transport): Subscription
    {
        $this->transport = $transport;
        return $this;
    }

    public function getWebhookData(): ?WebhookSubscriptionData
    {
        return $this->webhookData;
    }

    public function setWebhookData(?WebhookSubscriptionData $webhookData): void
    {
        $this->webhookData = $webhookData;
    }
}
