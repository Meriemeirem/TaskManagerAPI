<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Task;
use App\Models\User;
use App\Models\Role;

class TaskControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Créer un utilisateur pour chaque test
        $this->user = User::factory()->create();
    }

    /** @test */
    public function user_can_create_task()
    {
        $this->actingAs($this->user, 'sanctum');

        $response = $this->postJson('/api/tasks', [
            'title' => 'Test Task',
            'description' => 'Test Description',
            'status' => 'pending',
            'due_date' => now()->addDays(5),
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'title',
                'description',
                'status',
                'due_date',
                'created_at',
                'updated_at',
            ]);
    }

    /** @test */
    public function user_can_update_task()
    {
        $this->actingAs($this->user, 'sanctum');

        $task = Task::factory()->for($this->user)->create();

        $response = $this->putJson("/api/tasks/{$task->id}", [
            'title' => 'Updated Task',
            'description' => 'Updated Description',
            'status' => 'completed',
            'due_date' => now()->addDays(3),
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'title' => 'Updated Task',
                'description' => 'Updated Description',
                'status' => 'completed',
            ]);
    }

    /** @test */
    public function user_can_soft_delete_task()
    {
        $this->actingAs($this->user, 'sanctum');

        $task = Task::factory()->for($this->user)->create();

        $response = $this->deleteJson("/api/tasks/{$task->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Task deleted']);

        $this->assertSoftDeleted('tasks', [
            'id' => $task->id,
        ]);
    }

    /** @test */
    public function admin_can_view_all_tasks_including_deleted()
    {
        // Créer un administrateur
        $admin = User::factory()->create(['role' => 'admin']);

        // Créer des utilisateurs normaux
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // Créer des tâches pour chaque utilisateur
        $tasks = Task::factory()->count(4)->create(['user_id' => $user1->id]);
        
        // Supprimer la première tâche
        $tasks[0]->delete();

        // Authentifier en tant qu'administrateur
        $this->actingAs($admin, 'sanctum');

        // Faire une requête GET pour récupérer toutes les tâches
        $response = $this->getJson('/api/tasks');

        // Vérifier le statut et la structure de la réponse
        $response->assertStatus(200)
                ->assertJsonCount(4); // Vérifiez que toutes les tâches, y compris celles supprimées, sont retournées
    }
    /** @test */
    public function user_can_view_only_their_tasks()
    {
        $otherUser = User::factory()->create();

        Task::factory()->for($this->user)->create();
        Task::factory()->for($otherUser)->create();

        $this->actingAs($this->user, 'sanctum');

        $response = $this->getJson('/api/tasks');

        $response->assertStatus(200)
            ->assertJsonCount(1); // Vérifiez que seule la tâche de l'utilisateur authentifié est retournée
    }
}
