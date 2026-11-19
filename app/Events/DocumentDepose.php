<?php

namespace App\Events;

use App\Models\Document;
use App\Models\DocumentVersion;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DocumentDepose
{
    use Dispatchable, SerializesModels;

    public function __construct(public Document $document, public DocumentVersion $version) {}
}
