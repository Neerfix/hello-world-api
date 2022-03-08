<?php

namespace App\Services;

use App\Entity\Album;
use App\Entity\Travel;
use App\Repository\AlbumRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Ramsey\Uuid\Uuid;

class AlbumService
{
    // ------------------------ >

    public function __construct(
        private EntityManagerInterface $em,
        private AlbumRepository $albumRepository
    ) {
    }

    // ------------------------ >

    /**
     * @throws Exception
     */
    public function create(
        string $title,
        string $description,
        Travel $travel
    ): Album {
        $title = trim($title);
        $description = trim($description);

        $album = (new Album())
            ->setTitle($title)
            ->setDescription($description)
            ->setTravelId($travel)
            ->setCreatedAt(new DateTime())
            ->setUpdatedAt(new DateTime())
            ->setUuid(Uuid::uuid4());

        $this->em->persist($album);
        $this->em->flush();

        return $album;
    }

    public function getAll(): array
    {
        return $this->albumRepository->findAll();
    }
}
