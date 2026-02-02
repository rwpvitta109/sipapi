<?php

namespace App\Http\Resources;

class AccreditationResource extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'status' => $this->status,
            'notes' => $this->notes,
            'predicate' => $this->predicate,
            'type' => $this->type,
            'created_at' => $this->created_at,
            'accredited_at' => $this->accredited_at,
            'appealed_at' => $this->appealed_at,
            'user_id' => $this->user_id,
            'certificate_file' => $this->certificate_file,
            'recommendation_file' => $this->recommendation_file,
            'certificate_download_url' => $this->certificate_file
            ? route(
                'admin.certifications.download.certificate',
                $this->id
              )
            : null,
            'recommendation_download_url' => $this->recommendation_file
            ? route(
                'admin.certifications.download.recommendation',
                $this->id
              )
            : null,
            'certificate_status' => $this->certificate_status,
            'certificate_sent_at' => $this->certificate_sent_at,
            'meeting_date' => $this->meeting_date,
            'institution' => new InstitutionResource($this->whenLoaded('institution')),
            'evaluation' => new EvaluationResource($this->whenLoaded('evaluation')),
            'contents' => new AccreditationContentCollection($this->whenLoaded('contents')),
            'assignments' => new EvaluationAssignmentCollection($this->whenLoaded('evaluationAssignments')),
            $this->mergeWhen(isset($this->resource->result), [
                'results' => $this->resource->results(),
            ]),
            $this->mergeWhen(isset($this->resource->result) && isset($this->resource->finalResult), [
                'finalResult' => $this->resource->finalResult(),
            ]),
        ];
    }
}
