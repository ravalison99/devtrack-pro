<?php

namespace Tests\Feature;

use App\Models\Attachment;
use App\Models\AuditLog;
use App\Models\Comment;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\JournalEntry;
use App\Models\Project;
use App\Models\Stage;
use App\Models\Task;
use App\Models\User;
use App\Models\WeeklyReport;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ModelRelationsTest extends TestCase
{
    public static function relations(): array
    {
        return [
            [Attachment::class, 'task', BelongsTo::class, Task::class],
            [AuditLog::class, 'utilisateur', BelongsTo::class, User::class],
            [Comment::class, 'task', BelongsTo::class, Task::class],
            [Comment::class, 'utilisateur', BelongsTo::class, User::class],
            [Document::class, 'utilisateur', BelongsTo::class, User::class],
            [DocumentVersion::class, 'document', BelongsTo::class, Document::class],
            [JournalEntry::class, 'stagiaire', BelongsTo::class, User::class],
            [Project::class, 'tasks', HasMany::class, Task::class],
            [User::class, 'stagesEnTantQueMentor', HasMany::class, Stage::class],
            [User::class, 'journalEntries', HasMany::class, JournalEntry::class],
            [User::class, 'weeklyReports', HasMany::class, WeeklyReport::class],
            [User::class, 'documents', HasMany::class, Document::class],
        ];
    }

    #[DataProvider('relations')]
    public function test_les_relations_pointent_vers_le_bon_modele(string $modele, string $relation, string $type, string $cible): void
    {
        $instance = (new $modele)->{$relation}();

        $this->assertInstanceOf($type, $instance);
        $this->assertInstanceOf($cible, $instance->getRelated());
    }
}
