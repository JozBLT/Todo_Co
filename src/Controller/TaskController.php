<?php

namespace App\Controller;

use App\Entity\Task;
use App\Form\TaskType;
use App\Service\TaskService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/tasks')]
class TaskController extends AbstractController
{
    #[Route('', name: 'task_list', methods: ['GET'])]
    public function list(Request $request, TaskService $taskService): Response
    {
        $status = $request->query->getString('status', 'todo');
        $tasks = $taskService->getTasksByStatus($status === 'done');

        return $this->render('task/list.html.twig', [
            'tasks' => $tasks,
            'status' => $status,
        ]);
    }

    #[Route('/create', name: 'task_create', methods: ['GET', 'POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function create(Request $request, EntityManagerInterface $em): Response
    {
        $task = new Task();
        $task->setAuthor($this->getUser());
        $form = $this->createForm(TaskType::class, $task);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($task);
            $em->flush();

            $this->addFlash('success', 'La tâche a bien été ajoutée.');
            return $this->redirectToRoute('task_list');
        }

        return $this->render('task/create.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'task_edit', methods: ['GET', 'POST'])]
    public function edit(Task $task, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(TaskType::class, $task);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'La tâche a bien été modifiée.');
            return $this->redirectToRoute('task_list');
        }

        return $this->render('task/edit.html.twig', [
            'form' => $form,
            'task' => $task,
        ]);
    }

    #[Route('/{id}/toggle', name: 'task_toggle', methods: ['POST'])]
    public function toggle(Task $task, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('toggle'.$task->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('task_list');
        }
        $task->toggle();
        $em->flush();

        $this->addFlash('success', sprintf('La tâche "%s" a bien été marquée comme %s.',
                $task->getTitle(),
                $task->isDone() ? 'faite' : 'non terminée')
        );

        return $this->redirectToRoute('task_list');
    }

    #[Route('/{id}/delete', name: 'task_delete', methods: ['POST'])]
    public function delete(Task $task, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('TASK_DELETE', $task);
        $em->remove($task);
        $em->flush();
        $this->addFlash('success', 'La tâche a bien été supprimée.');

        return $this->redirectToRoute('task_list');
    }
}
