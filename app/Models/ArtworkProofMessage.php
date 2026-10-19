<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['artwork_proof_id', 'sender_role', 'message'])]
class ArtworkProofMessage extends Model
{
    public function proof(): BelongsTo
    {
        return $this->belongsTo(ArtworkProof::class, 'artwork_proof_id');
    }
}
