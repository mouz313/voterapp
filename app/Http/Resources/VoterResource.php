<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class VoterResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'father_name' => $this->father_name,
            'cnic' => $this->cnic,
            'cnic_formatted' => $this->formatted_cnic,
            'age' => $this->age,
            'silsala_no' => $this->silsala_no,
            'gharana_no' => $this->gharana_no,
            'block_code' => $this->blockCode?->code,
            'polling_station' => $this->pollingStation
                ? ['name' => $this->pollingStation->name, 'address' => $this->pollingStation->address]
                : null,
            'uc' => $this->uc?->name,
        ];
    }
}
