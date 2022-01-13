<?php

namespace App\Services\Tasks;

use App\Entity\Task;
use App\Entity\User;
use Carbon\Carbon;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Translation\Exception\NotFoundResourceException;

class Handler
{
    private const MODEL = Task::class;

    private ManagerRegistry $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    public function listFor(User $user): array
    {
        $builder = $this->doctrine->getRepository(self::MODEL)
            ->createQueryBuilder('t')
            ->where('t.user = :user')
            ->setParameter('user', $user->getId())
            ->orderBy('t.created_at', 'ASC');

        $query = $builder->getQuery();
        return $query->execute();
    }

    public function find(int $id): Task
    {
        if (!$task = $this->doctrine->getRepository(self::MODEL)->find($id)) {
            throw new NotFoundResourceException('Task was not found');
        }

        return $task;
    }

    public function makeFrom(array $attributes, ?Task $task = null): Task
    {
        $task ??= new Task();

        $task->setTitle($attributes['title']);
        $task->setDescription($attributes['description'] ?? null);
        $task->setStatus($attributes['status']);
        $task->setCreatedAt($task->getCreatedAt() ?? new Carbon());
        $task->setUpdatedAt(Carbon::now());
        $task->setUserId($task->getUserId() ?? $attributes['user']);

        return $task;
    }

    public function persist(Task $task): void
    {
        $manager = $this->doctrine->getManager();
        $manager->persist($task);
        $manager->flush();
    }

    public function delete(int $id): void
    {
        $manager = $this->doctrine->getManager();
        $manager->remove($this->find($id));
        $manager->flush();
    }
}