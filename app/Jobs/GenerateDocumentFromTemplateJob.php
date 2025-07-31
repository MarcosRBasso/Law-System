<?php

namespace App\Jobs;

use App\Models\DocumentTemplate;
use App\Models\Lawsuit;
use App\Models\Client;
use App\Models\Document;
use App\Services\DocumentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateDocumentFromTemplateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 180; // 3 minutes
    public $tries = 2;

    public function __construct(
        private DocumentTemplate $template,
        private array $variables,
        private string $documentName,
        private ?Lawsuit $lawsuit = null,
        private ?Client $client = null,
        private ?int $userId = null
    ) {
        $this->onQueue('documents');
    }

    /**
     * Execute the job.
     */
    public function handle(DocumentService $documentService): Document
    {
        try {
            Log::info("Generating document from template", [
                'template_id' => $this->template->id,
                'template_name' => $this->template->name,
                'document_name' => $this->documentName
            ]);

            // Generate the document
            $document = $documentService->generateFromTemplate([
                'template_id' => $this->template->id,
                'variables' => $this->variables,
                'name' => $this->documentName,
                'lawsuit_id' => $this->lawsuit?->id,
                'client_id' => $this->client?->id,
                'user_id' => $this->userId ?? auth()->id()
            ]);

            Log::info("Document generated successfully", [
                'document_id' => $document->id,
                'document_name' => $document->name,
                'file_size' => $document->file_size
            ]);

            // Notify relevant users
            $this->notifyUsers($document);

            return $document;

        } catch (\Exception $e) {
            Log::error("Failed to generate document from template", [
                'template_id' => $this->template->id,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Notify relevant users about the generated document
     */
    private function notifyUsers(Document $document): void
    {
        $users = collect();

        // Add document creator
        if ($this->userId) {
            $users->push(\App\Models\User::find($this->userId));
        }

        // Add responsible lawyer if lawsuit is specified
        if ($this->lawsuit) {
            $users->push($this->lawsuit->responsibleLawyer);
        }

        // Remove duplicates and null values
        $users = $users->filter()->unique('id');

        foreach ($users as $user) {
            $user->notify(new \App\Notifications\DocumentGeneratedNotification($document));
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Document generation job failed", [
            'template_id' => $this->template->id,
            'template_name' => $this->template->name,
            'error' => $exception->getMessage()
        ]);

        // Notify the user who requested the generation
        if ($this->userId) {
            $user = \App\Models\User::find($this->userId);
            $user?->notify(new \App\Notifications\DocumentGenerationFailedNotification(
                $this->template,
                $exception->getMessage()
            ));
        }
    }
}