<?php

namespace App\Services;

use App\Entity\Place;
use App\Entity\Travel;
use App\Entity\User;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Ramsey\Uuid\Uuid;
use RuntimeException;

class TravelService
{
    // ------------------------ >

    private const DESCRIPTION_MINIMAL_WORD = 5;

    // ------------------------ >

    public function __construct(
        private EntityManagerInterface $em,
        private UserService $userService,
    ) {
    }

    // ------------------------ >

    /**
     * @throws Exception
     */
    public function create(
        User $user,
        Place $place,
        string $name,
        float $budget,
        ?DateTime $startedAt = null,
        ?DateTime $endedAt = null,
        ?string $description = null,
        ?bool $isShared = true
    ): Travel {
        if (null !== $description) {
            preg_match_all('/([a-zA-Z][-\'0-9a-zÀ-ÿ]+)/m', $description, $words, PREG_SET_ORDER, 0);

            // Explanation is too short
            if (count($words) < static::DESCRIPTION_MINIMAL_WORD) {
                throw new RuntimeException(sprintf('La description ne peut pas être inférieur à %s mots', static::DESCRIPTION_MINIMAL_WORD));
            }
        }

        $name = trim($name);

        $travel = (new Travel())
            ->setName($name)
            ->setBudget($budget)
            ->setStartedAt($startedAt)
            ->setEndedAt($endedAt)
            ->setDescription($description)
            ->setUserId($user)
            ->setPlaceId($place)
            ->setIsShared($isShared)
            ->setUuid(Uuid::uuid4())
            ->setStatus(Travel::STATUS_ACTIVE);

        $this->em->persist($travel);

        try {
            $this->em->flush();
        } catch (Exception $e) {
            dd($e);
        }

        return $travel;
    }

    public function update(
        Travel $travel,
        Place $place,
        User $user,
        string $name,
        float $budget,
        ?DateTime $startedAt = null,
        ?DateTime $endedAt = null,
        ?string $description = null,
        ?bool $isSharable = true
    ): Travel {
        if (null !== $description) {
            preg_match_all('/([a-zA-Z][-\'0-9a-zÀ-ÿ]+)/m', $description, $words, PREG_SET_ORDER, 0);

            // Explanation is too short
            if (count($words) < static::DESCRIPTION_MINIMAL_WORD) {
                throw new RuntimeException(sprintf('La description ne peut pas être inférieur à %s mots', static::DESCRIPTION_MINIMAL_WORD));
            }
        }

        $name = trim($name);

        $travel
            ->setName($name)
            ->setBudget($budget)
            ->setStartedAt($startedAt)
            ->setEndedAt($endedAt)
            ->setDescription($description)
            ->setUserId($user)
            ->setUuid(Uuid::uuid4())
            ->setPlaceId($place)
            ->setIsShared($isSharable);

        $this->em->persist($travel);
        $this->em->flush();

        return $travel;
    }

    /**
     * @throws Exception
     */
    public function delete(
        Travel $travel,
        User $user
    ): Travel {
        if (Travel::STATUS_DELETED === $travel->getStatus()) {
            throw new RuntimeException('Le voyage est déjà supprimé');
        }

        if (!in_array(User::ROLE_ADMIN, $user->getRoles(), true) || $travel->getUserId() !== $user->getId()) {
            throw new RuntimeException('Vous n\'avez pas l\'autorisation de supprimer ce voyage');
        }

        $travel
            ->setStatus(Travel::STATUS_DELETED)
            ->setUpdatedAt(new DateTime());

        $this->em->persist($travel);
        $this->em->flush();

        return $travel;
    }
}
