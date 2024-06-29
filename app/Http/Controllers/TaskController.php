<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaskController extends Controller
{
    // Récupération de toutes les tâches
    public function index()
    {
        if (Auth::user()->hasRole('Administrateur')) {
            $tasks = Task::withTrashed()->get();
        } else {
            $tasks = Auth::user()->tasks()->get();
        }

        return response()->json($tasks);
    }

    // Création d'une nouvelle tâche
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|string|max:255',
            'due_date' => 'nullable|date',
        ]);

        $task = Auth::user()->tasks()->create($validated);
        return response()->json($task, 201);
    }

    // Récupération des détails d'une tâche spécifique
    public function show($id)
    {
        $task = Task::withTrashed()->findOrFail($id);

        if (Auth::user()->hasRole('Administrateur') || Auth::user()->id == $task->user_id) {
            return response()->json($task);
        }

        return response()->json(['message' => 'Forbidden'], 403);
    }

    // Mise à jour d'une tâche
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|string|max:255',
            'due_date' => 'nullable|date',
        ]);

        $task = Task::withTrashed()->findOrFail($id);

        if (Auth::user()->hasRole('Administrateur') || Auth::user()->id == $task->user_id) {
            $task->update($validated);
            return response()->json($task);
        }

        return response()->json(['message' => 'Forbidden'], 403);
    }

    // Soft delete d'une tâche
    public function destroy($id)
    {
        $task = Task::findOrFail($id);

        if (Auth::user()->hasRole('Administrateur') || Auth::user()->id == $task->user_id) {
            $task->delete();
            return response()->json(['message' => 'Task deleted']);
        }

        return response()->json(['message' => 'Forbidden'], 403);
    }

    // Récupération des tâches supprimées
    public function deletedTasks()
    {
        if (Auth::user()->hasRole('Administrateur')) {
            $tasks = Task::onlyTrashed()->get();
            return response()->json($tasks);
        }

        return response()->json(['message' => 'Forbidden'], 403);
    }
}
