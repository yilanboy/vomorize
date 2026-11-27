<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Level;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Level
 */
class LevelResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->translation->name,
            'description' => $this->translation->description,
            'vocabularies_count' => $this->whenCounted('vocabularies'),
            'groups_count' => $this->whenCounted('groups'),
        ];
    }
}
