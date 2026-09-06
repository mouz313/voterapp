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
            'gender' => $this->gender,
            'gender_ur' => $this->gender_label_ur,
            'silsala_no' => $this->silsala_no,
            'gharana_no' => $this->gharana_no,
            'block_code' => $this->blockCode?->code,
            'polling_station' => $this->pollingStation
                ? [
                    'id' => $this->pollingStation->id,
                    'station_no' => $this->pollingStation->station_no,
                    'name' => $this->pollingStation->name,
                    'gender' => $this->pollingStation->gender,
                    'gender_ur' => $this->pollingStation->gender_label_ur,
                    'address' => $this->pollingStation->address,
                    'booths' => $this->pollingStation->total_booths,
                ]
                : null,
            'uc' => $this->uc?->name,
        ];
    }
}
