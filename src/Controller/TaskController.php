<?php

namespace App\Controller;

use App\Services\Tasks\Handler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\Exception\NotFoundResourceException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class TaskController extends AbstractController
{
    private Handler $tasksHandler;

    public function __construct(Handler $tasksHandler)
    {
        $this->tasksHandler = $tasksHandler;
    }

    #[Route('/tasks', name: 'tasks.index', methods: ['get'])]
    public function index(): Response
    {
        return $this->render('tasks/index.html.twig', ['tasks' => $this->tasksHandler->listFor($this->getUser())]);
    }

    #[Route('/tasks/create', name: 'tasks.create', methods: ['get'])]
    public function create(): Response
    {
        return $this->render('tasks/create.html.twig');
    }

    #[Route('/tasks', name: 'tasks.store', methods: ['post'])]
    public function store(Request $request, ValidatorInterface $validator): Response
    {
        try {
            $data = array_merge($request->request->all(), ['user' => $this->getUser()]);
            $task = $this->tasksHandler->makeFrom($data);

            $errors = $validator->validate($task);
            if (count($errors) > 0) {
                return new Response((string) $errors, 400);
            }

            $this->tasksHandler->persist($task);

            $this->addFlash('success', 'Task has been created');
            return $this->redirectToRoute('tasks.index');
        } catch (NotFoundResourceException $e) {
            $this->addFlash('error', $e->getMessage());
            return $this->redirectToRoute('tasks.index');
        }
    }

    #[Route('/tasks/{id}', name: 'tasks.edit', methods: ['get'])]
    public function edit(int $id): Response
    {
        try {
            return $this->render('tasks/edit.html.twig', ['task' => $this->tasksHandler->find($id), 'error' => null]);
        } catch (NotFoundResourceException $e) {
            $this->addFlash('error', $e->getMessage());
            return $this->redirectToRoute('tasks.index');
        }
    }

    #[Route('/tasks/{id}', name: 'tasks.update', methods: ['put'])]
    public function update(int $id, Request $request, ValidatorInterface $validator): Response
    {
        $payload = $request->request->all();

        try {
            $task = $this->tasksHandler->makeFrom($payload, $this->tasksHandler->find($id));

            $errors = $validator->validate($task);
            if (count($errors)) {
                return $this->renderForm('tasks/edit.html.twig', ['task' => $task, 'error' => (string) $errors]);
            }

            $this->tasksHandler->persist($task);
            $this->addFlash('success', 'Task has been updated');
            return $this->redirectToRoute('tasks.index');
        } catch (NotFoundResourceException $e) {
            $this->addFlash('error', $e->getMessage());
            return $this->redirectToRoute('tasks.index');
        }
    }

    #[Route('/tasks/{id}', name: 'tasks.delete', methods: ['delete'])]
    public function delete(int $id): Response
    {
        try {
            $this->tasksHandler->delete($id);
            $this->addFlash('success', 'Task has been deleted');
        } catch (NotFoundResourceException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('tasks.index');
    }
}